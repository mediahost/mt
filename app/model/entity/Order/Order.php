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
use Nette\Utils\DateTime;
use Nette\Utils\Random;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\OrderRepository")
 * @ORM\Table(name="`order`")
 *
 * @property ArrayCollection $items
 * @property-read int $itemsCount
 * @property ArrayCollection $vouchers
 * @property User $user
 * @property OrderState $state
 * @property string $currency
 * @property-read float $rate
 * @property string $locale
 * @property bool $isEditable
 * @property bool $isDeletable
 * @property string $pin
 * @property string $mail
 * @property string $ip
 * @property string $phone
 * @property string $note
 * @property Address $billingAddress
 * @property Address $shippingAddress
 * @property ShopVariant $shopVariant
 * @property-read Shop $shop
 * @property DateTime $paymentDate
 * @property int $paymentBlame
 * @property-read string $paymentBlameName
 */
class Order extends BaseEntity
{

	const PAYMENT_BLAME_MANUAL = 1;
	const PAYMENT_BLAME_VUB = 2;
	const PAYMENT_BLAME_CSOB = 3;
	const PAYMENT_BLAME_CARD = 4;

	use Identifier;
	use Model\Timestampable\Timestampable;

	/** @var array */
	protected $paymentBlameNames = [
		self::PAYMENT_BLAME_MANUAL => 'admin',
		self::PAYMENT_BLAME_VUB => 'VUB',
		self::PAYMENT_BLAME_CSOB => 'CSOB',
		self::PAYMENT_BLAME_CARD => 'WebPay',
	];

	/** @ORM\Column(type="string", nullable=true) */
	protected $pin;

	/** @ORM\ManyToOne(targetEntity="User", inversedBy="orders") */
	protected $user;

	/** @ORM\ManyToOne(targetEntity="OrderState") */
	protected $state;

	/** @ORM\OneToMany(targetEntity="OrderItem", mappedBy="order", cascade={"all"}, orphanRemoval=true) */
	protected $items;

	/** @ORM\ManyToMany(targetEntity="Voucher", inversedBy="orders") */
	protected $vouchers;

	/** @ORM\OneToOne(targetEntity="OrderShipping", inversedBy="order", cascade={"all"}) */
	protected $shipping;

	/** @ORM\OneToOne(targetEntity="OrderPayment", inversedBy="order", cascade={"all"}) */
	protected $payment;

	/** @ORM\Column(type="string", nullable=true) */
	protected $mail;

	/** @ORM\Column(type="string", nullable=true) */
	protected $ip;

	/** @ORM\OneToOne(targetEntity="Address", cascade={"persist", "remove"}) */
	protected $billingAddress;

	/** @ORM\OneToOne(targetEntity="Address", cascade={"persist", "remove"}) */
	protected $shippingAddress;

	/** @ORM\Column(type="string", length=8, nullable=true) */
	protected $locale;

	/** @ORM\Column(type="string", length=8, nullable=true) */
	protected $currency;

	/** @ORM\Column(type="float", nullable=true) */
	private $rate;

	/** @ORM\Column(type="string", length=255, nullable=true) */
	protected $note;

	/** @ORM\Column(type="datetime", nullable=true) */
	protected $paymentDate;

	/** @ORM\Column(type="integer", nullable=true) */
	protected $paymentBlame;

	/** @ORM\ManyToOne(targetEntity="ShopVariant") */
	protected $shopVariant;

	/** @ORM\ManyToOne(targetEntity="Shop") */
	private $shop;

	public function __construct($locale, User $user = NULL)
	{
		if ($user) {
			$this->setUser($user);
		}
		$this->pin = Random::generate(4, '0-9');
		$this->locale = $locale;
		$this->items = new ArrayCollection();
		$this->vouchers = new ArrayCollection();
		parent::__construct();
	}

	public function getPaymentBlameName()
	{
		if (isset($this->paymentBlameNames[$this->paymentBlame])) {
			return $this->paymentBlameNames[$this->paymentBlame];
		}
		return NULL;
	}

	public function isCardPayment()
	{
		return $this->payment->origin->isCard;
	}

	public function isPayed()
	{
		return (bool)$this->paymentBlame;
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
		return $this->billingAddress && $this->billingAddress->isCompany();
	}

	public function getPhone()
	{
		if ($this->billingAddress) {
			return $this->billingAddress->phone;
		}
		return NULL;
	}

	public function getNeedPin()
	{
		return (bool)!$this->shippingAddress;
	}

	public function getPin($force = FALSE)
	{
		return ($force || $this->getNeedPin()) ? $this->pin : NULL;
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

	public function getMostExpensiveItem()
	{
		$mostExpensive = NULL;
		$setMostExpensive = function ($key, OrderItem $item) use (&$mostExpensive) {
			if ($mostExpensive) {
				if ($item->price > $mostExpensive->price) {
					$mostExpensive = $item;
				}
			} else {
				$mostExpensive = $item;
			}
			return TRUE;
		};
		$this->items->forAll($setMostExpensive);
		return $mostExpensive;
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

	public function setShopVariant(ShopVariant $shopVariant)
	{
		$this->shopVariant = $shopVariant;
		$this->shop = $shopVariant->shop;
		return $this;
	}

	public function getShop()
	{
		return $this->shop;
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

	/** @return int */
	public function getVouchersCount()
	{
		return count($this->vouchers);
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
		$totalPrice = 0;
		foreach ($this->items as $item) {
			$totalPrice += $item->getTotalPrice($exchange, $withVat);
		}
		return $totalPrice;
	}

	/** @return float */
	public function getVouchersTotalPrice(Exchange $exchange = NULL, $withVat = TRUE)
	{
		$totalPrice = 0;
		$itemsTotal = $this->getItemsTotalPrice($exchange, $withVat);
		$countSum = function ($key, Voucher $voucher) use (&$totalPrice, $exchange, $itemsTotal) {
			$totalPrice += $voucher->getDiscountValue($itemsTotal, $exchange);
			return TRUE;
		};
		$this->vouchers->forAll($countSum);
		return $totalPrice;
	}

	/** @return float */
	public function getItemsWithVouchersTotalPrice(Exchange $exchange = NULL, $withVat = TRUE)
	{
		$itemsTotal = $this->getItemsTotalPrice($exchange, $withVat);
		$vouchersTotal = $this->getVouchersTotalPrice($exchange);
		return $itemsTotal - $vouchersTotal;
	}

	/** @return float */
	public function getPaymentsTotalPrice(Exchange $exchange = NULL, $withVat = TRUE)
	{
		$totalPrice = 0;
		$currency = $this->currency;
		if ($this->shipping && $this->shipping->price) {
			$priceValue = $withVat ? $this->shipping->price->withVat : $this->shipping->price->withoutVat;
			$exchangedValue = $exchange ? $exchange->change($priceValue, $currency, $currency, Price::PRECISION) : $priceValue;
			$totalPrice += $exchangedValue;
		}
		if ($this->payment && $this->payment->price) {
			$priceValue = $withVat ? $this->payment->price->withVat : $this->payment->price->withoutVat;
			$exchangedValue = $exchange ? $exchange->change($priceValue, $currency, $currency, Price::PRECISION) : $priceValue;
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
	public function getTotalPriceToPay(Exchange $exchange = NULL, $withVat = TRUE)
	{
		$itemsWithVoucherTotal = $this->getItemsWithVouchersTotalPrice($exchange, $withVat);
		$paymentsTotal = $this->getPaymentsTotalPrice($exchange, $withVat);
		return $itemsWithVoucherTotal + $paymentsTotal;
	}

	/** @return float */
	public function getVatSum(Exchange $exchange = NULL)
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

	/** @var bool */
	public function hasVouchers()
	{
		return (bool)$this->vouchers->count();
	}

	public function import(Basket $basket, $level = NULL)
	{
		$this->items->clear();
		foreach ($basket->items as $item) {
			/* @var $item BasketItem */
			$price = $item->stock->getPrice($level);
			$this->setItem($item->stock, $price, $item->quantity, $this->locale);
		}
		$this->vouchers->clear();
		foreach ($basket->vouchers as $voucher) {
			/* @var $voucher Voucher */
			$this->vouchers->add($voucher);
		}
		if ($basket->shipping) {
			$shipping = $basket->shipping;
			$originalPrice = $shipping->price;
			$customPrice = $shipping->getPrice($basket, $level);
			$shipping->setPrice($customPrice->withoutVat, FALSE);
			$this->setShipping($shipping);
			$shipping->price = $originalPrice->withoutVat;
		}
		if ($basket->payment) {
			$payment = $basket->payment;
			$originalPrice = $payment->price;
			$customPrice = $payment->getPrice($basket, $level);
			$payment->setPrice($customPrice->withoutVat, FALSE);
			$this->setPayment($payment);
			$payment->price = $originalPrice->withoutVat;
		}
		$this->mail = $basket->mail;
		$this->billingAddress = $basket->billingAddress ? clone $basket->billingAddress : NULL;
		$this->shippingAddress = $basket->shippingAddress ? clone $basket->shippingAddress : NULL;

		return $this;
	}

	public function __toString()
	{
		return (string)$this->id;
	}

}
