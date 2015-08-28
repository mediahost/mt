<?php

namespace App\Model\Entity;

use App\Model\Facade\Exception\InsufficientQuantityException;
use App\Model\Facade\Exception\MissingItemException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use h4kuna\Exchange\Exchange;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\OrderRepository")
 * @ORM\Table(name="`order`")
 *
 * @property ArrayCollection $items
 * @property-read int $itemsCount
 * @property User $user
 * @property OrderState $state
 * @property string $currency
 * @property string $locale
 * @property bool $isEditable
 * @property bool $isDeletable
 */
class Order extends BaseEntity
{

	use Identifier;
	use Model\Timestampable\Timestampable;

	/** @ORM\ManyToOne(targetEntity="User", inversedBy="orders") */
	protected $user;

	/** @ORM\ManyToOne(targetEntity="OrderState") */
	protected $state;

	/** @ORM\OneToMany(targetEntity="OrderItem", mappedBy="order", cascade={"all"}, orphanRemoval=true) */
	protected $items;

	/** @ORM\Column(type="string", length=8, nullable=true) */
	protected $locale;

	/** @ORM\Column(type="string", length=8, nullable=true) */
	protected $currency;

	/** @ORM\Column(type="float", nullable=true) */
	private $rate;

	public function __construct($locale, User $user = NULL)
	{
		if ($user) {
			$this->setUser($user);
		}
		$this->locale = $locale;
		$this->items = new ArrayCollection();
		parent::__construct();
	}

	public function setUser(User $user)
	{
		$this->user = $user;
		return $this;
	}

	public function setCurrency($currency, $rate = NULL)
	{
		$this->currency = $currency;
		$this->rate = $rate;
		return $this;
	}

	public function setItem(Stock $stock, Price $price, $quantity, $locale)
	{
		if ($quantity > $stock->inStore) {
			throw new InsufficientQuantityException();
		}

		$isInItems = function ($key, OrderItem $item) use ($stock) {
			return $stock->id === $item->stock->id;
		};
		$changeQuantity = function ($key, OrderItem $item) use ($stock, $price, $quantity) {
			if ($stock->id === $item->stock->id) {
				if ($quantity > 0) {
					$item->quantity = $quantity;
					$item->price = $price;
				} else {
					$this->items->removeElement($item);
				}
				return FALSE;
			}
			return TRUE;
		};

		if ($this->items->exists($isInItems)) {
			$this->items->forAll($changeQuantity);
		} else {
			$stock->product->setCurrentLocale($locale);
			$item = new OrderItem();
			$item->order = $this;
			$item->stock = $stock;
			$item->name = $stock->product->name;
			$item->price = $price;
			$item->quantity = $quantity;
			$this->items->add($item);
		}
		return $this;
	}

	/** @return int */
	public function getItemCount(Stock $stock)
	{
		foreach ($this->items as $item) {
			if ($item->stock->id === $stock->id) {
				return $item->quantity;
			}
		}
		throw new MissingItemException();
	}

	/** @return int */
	public function getItemsCount()
	{
		return count($this->items);
	}

	/** @return Price */
	public function getItemPrice(Stock $stock)
	{
		foreach ($this->items as $item) {
			if ($item->stock->id === $stock->id) {
				return $item->price;
			}
		}
		throw new MissingItemException();
	}

	/** @return float */
	public function getItemsTotalPrice(Exchange $exchange, $level = NULL, $withVat = TRUE)
	{
		if ($this->rate) {
			$exchange->addRate($this->currency, $this->rate);
		}
		$totalPrice = 0;
		foreach ($this->items as $item) {
			$totalPrice += $item->getTotalPrice($exchange, $level, $withVat);
		}
		return $totalPrice;
	}

	/** @return bool */
	public function getIsEditable()
	{
		return ($this->state->type->id === OrderStateType::ORDERED);
	}

	/** @return bool */
	public function getIsDeletable()
	{
		return ($this->state->type->id === OrderStateType::STORNO);
	}

	public function import(Basket $basket, $level = NULL)
	{
		$this->items->clear();
		foreach ($basket->items as $item) {
			/* @var $item BasketItem */
			$price = $item->stock->getPrice($level);
			$this->setItem($item->stock, $price, $item->quantity, $this->locale);
		}
		return $this;
	}
	
	public function __toString()
	{
		return (string) $this->id;
	}

}
