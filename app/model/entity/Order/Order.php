<?php

namespace App\Model\Entity;

use App\ExchangeHelper;
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
 * @property-read float $rate
 * @property string $locale
 * @property bool $isEditable
 * @property bool $isDeletable
 * @property string $mail
 * @property Address $billingAddress
 * @property Address $shippingAddress
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

	/** @ORM\OneToOne(targetEntity="OrderShipping", inversedBy="order", cascade={"all"}) */
	protected $shipping;

	/** @ORM\OneToOne(targetEntity="OrderPayment", inversedBy="order", cascade={"all"}) */
	protected $payment;

	/** @ORM\Column(type="string", nullable=true) */
	protected $mail;

	/** @ORM\OneToOne(targetEntity="Address", cascade={"persist", "remove"}) */
	protected $shippingAddress;

	/** @ORM\OneToOne(targetEntity="Address", cascade={"persist", "remove"}) */
	protected $billingAddress;

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

	public function getRate()
	{
		return $this->rate;
	}
	
	public function getIsCompany()
	{
		return $this->shippingAddress && $this->shippingAddress->isCompany();
	}

	public function setItem(Stock $stock, Price $price, $quantity, $locale)
	{
		$oldQuantity = $this->getItemCount($stock, FALSE);
		if (($quantity - $oldQuantity) > $stock->inStore) {
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

	public function setShipping(Shipping $shipping)
	{
		if (!$this->shipping) {
			$this->shipping = new OrderShipping();
			$this->shipping->order = $this;
		}
		$this->shipping->import($shipping);
		return $this;
	}

	public function setPayment(Payment $payment)
	{
		if (!$this->payment) {
			$this->payment = new OrderPayment();
			$this->payment->order = $this;
		}
		$this->payment->import($payment);
		return $this;
	}

	/** @return int */
	public function getItemCount(Stock $stock, $throwException = TRUE)
	{
		foreach ($this->items as $item) {
			if ($item->stock->id === $stock->id) {
				return $item->quantity;
			}
		}
		if ($throwException) {
			throw new MissingItemException();
		} else {
			return 0;
		}
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
	public function getItemsTotalPrice(Exchange $exchange = NULL, $withVat = TRUE)
	{
		if ($exchange) {
			$this->setExchangeRate($exchange);
		}
		$totalPrice = 0;
		foreach ($this->items as $item) {
			$totalPrice += $item->getTotalPrice($exchange, $withVat);
		}
		return $totalPrice;
	}

	/** @return float */
	public function getPaymentsTotalPrice(Exchange $exchange = NULL, $withVat = TRUE)
	{
		if ($exchange) {
			$this->setExchangeRate($exchange);
		}
		$totalPrice = 0;
		if ($this->shipping && $this->shipping->price) {
			$priceValue = $withVat ? $this->shipping->price->withVat : $this->shipping->price->withoutVat;
			$exchangedValue = $exchange ? $exchange->change($priceValue, NULL, NULL, Price::PRECISION) : $priceValue;
			$totalPrice += $exchangedValue;
		}
		if ($this->payment && $this->payment->price) {
			$priceValue = $withVat ? $this->payment->price->withVat : $this->payment->price->withoutVat;
			$exchangedValue = $exchange ? $exchange->change($priceValue, NULL, NULL, Price::PRECISION) : $priceValue;
			$totalPrice += $exchangedValue;
		}
		
		return $totalPrice;
	}

	/** @return float */
	public function getTotalPrice(Exchange $exchange = NULL, $withVat = TRUE)
	{
		$itemsTotal = $this->getItemsTotalPrice($exchange, $withVat);
		$paymentsTotal = $this->getPaymentsTotalPrice($exchange, $withVat);
		return $itemsTotal + $paymentsTotal;
	}

	/** @return float */
	public function getVatSum(Exchange $exchange)
	{
		$withVat = $this->getTotalPrice($exchange, TRUE);
		$withoutVat = $this->getTotalPrice($exchange, FALSE);
		return $withVat - $withoutVat;
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
		if ($basket->shipping) {
			$this->setShipping($basket->shipping);
		}
		if ($basket->payment) {
			$this->setPayment($basket->payment);
		}
		$this->mail = $basket->mail;
		$this->billingAddress = $basket->billingAddress;
		$this->shippingAddress = $basket->shippingAddress;
		
		return $this;
	}

	public function __toString()
	{
		return (string) $this->id;
	}

	private function setExchangeRate(Exchange $exchange)
	{
		if ($this->rate && $this->currency && array_key_exists($this->currency, $exchange)) {
			$currency = $exchange[$this->currency];
			$rateRelated = ExchangeHelper::getRelatedRate($this->rate, $currency);
			$exchange->addRate($this->currency, $rateRelated);
		}
	}

}
