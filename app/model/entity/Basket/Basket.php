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
 * @ORM\Entity(repositoryClass="App\Model\Repository\BasketRepository")
 *
 * @property ArrayCollection $items
 * @property int $itemsCount
 * @property Shipping $shipping
 * @property Payment $payment
 */
class Basket extends BaseEntity
{

	use Identifier;

use Model\Timestampable\Timestampable;

	/** @ORM\OneToOne(targetEntity="User", inversedBy="basket") */
	protected $user;

	/** @ORM\OneToMany(targetEntity="BasketItem", mappedBy="basket", cascade={"persist", "remove"}, orphanRemoval=true) */
	protected $items;

	/** @ORM\ManyToOne(targetEntity="Shipping") */
	protected $shipping;

	/** @ORM\ManyToOne(targetEntity="Payment") */
	protected $payment;

	public function __construct(User $user = NULL)
	{
		if ($user) {
			$this->setUser($user);
		}
		$this->items = new ArrayCollection();
		parent::__construct();
	}

	public function setUser(User $user)
	{
		$this->user = $user;
		$user->basket = $this;
		return $this;
	}

	public function setItem(Stock $stock, $quantity)
	{
		if ($quantity > $stock->inStore) {
			throw new InsufficientQuantityException();
		}

		$isInItems = function ($key, BasketItem $item) use ($stock) {
			return $stock->id === $item->stock->id;
		};
		$changeQuantity = function ($key, BasketItem $item) use ($stock, $quantity) {
			if ($stock->id === $item->stock->id) {
				if ($quantity > 0) {
					$item->quantity = $quantity;
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
			$item = new BasketItem();
			$item->basket = $this;
			$item->stock = $stock;
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

	/** @return float */
	public function getItemsTotalPrice(Exchange $exchange, $level = NULL, $withVat = TRUE)
	{
		$totalPrice = 0;
		foreach ($this->items as $item) {
			$totalPrice += $item->getTotalPrice($exchange, $level, $withVat);
		}
		return $totalPrice;
	}

	/** @return float */
	public function getItemsVatSum(Exchange $exchange, $level = NULL)
	{
		$withVat = $this->getItemsTotalPrice($exchange, $level, TRUE);
		$withoutVat = $this->getItemsTotalPrice($exchange, $level, FALSE);
		return $withVat - $withoutVat;
	}

	/** @return float */
	public function getPaymentsPrice($withVat = TRUE)
	{
		$totalPrice = 0;
		if ($this->shipping) {
			$shippingPrice = $this->shipping->getPrice($this);
			$totalPrice += $withVat ? $shippingPrice->withVat : $shippingPrice->withoutVat;
		}
		if ($this->payment) {
			$paymentPrice = $this->payment->getPrice($this);
			$totalPrice += $withVat ? $paymentPrice->withVat : $paymentPrice->withoutVat;
		}

		return $totalPrice;
	}

	public function import(Basket $basket, $skipException = FALSE)
	{
		if ($basket->itemsCount) {
			$this->items->clear();
		}
		/* @var $item BasketItem */
		foreach ($basket->items as $item) {
			try {
				$this->setItem($item->stock, $item->quantity);
			} catch (InsufficientQuantityException $exc) {
				if (!$skipException) {
					throw $exc;
				}
			}
		}
		return $this;
	}

	public function __toString()
	{
		return (string) $this->id;
	}

}
