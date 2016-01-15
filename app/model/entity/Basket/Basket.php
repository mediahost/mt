<?php

namespace App\Model\Entity;

use App\Model\Facade\Exception\InsufficientQuantityException;
use App\Model\Facade\Exception\MissingItemException;
use App\Model\Repository\BasketRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use h4kuna\Exchange\Exchange;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Knp\DoctrineBehaviors\Model;
use Nette\Utils\DateTime;
use Nette\Utils\Random;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\BasketRepository")
 *
 * @property string $accessHash
 * @property ArrayCollection $items
 * @property int $itemsCount
 * @property ArrayCollection $vouchers
 * @property Shipping $shipping
 * @property Payment $payment
 * @property string $mail
 * @property string $phone
 * @property Address $billingAddress
 * @property Address $shippingAddress
 * @property bool $isCompany
 * @property DateTime $sendedMailAt
 */
class Basket extends BaseEntity
{

	use Identifier;
	use Model\Timestampable\Timestampable;

	/** @ORM\Column(type="string", nullable=true) */
	protected $accessHash;

	/** @ORM\OneToOne(targetEntity="User", inversedBy="basket", fetch="LAZY") */
	protected $user;

	/** @ORM\OneToMany(targetEntity="BasketItem", mappedBy="basket", cascade={"persist", "remove"}, orphanRemoval=true) */
	protected $items;

	/** @ORM\ManyToMany(targetEntity="Voucher") */
	protected $vouchers;

	/** @ORM\Column(type="datetime") */
    private $changeItemsAt;

	/** @ORM\ManyToOne(targetEntity="Shipping") */
	protected $shipping;

	/** @ORM\ManyToOne(targetEntity="Payment") */
	protected $payment;

	/** @ORM\Column(type="string", nullable=true) */
	protected $mail;

	/** @ORM\OneToOne(targetEntity="Address") */
	protected $billingAddress;

	/** @ORM\OneToOne(targetEntity="Address") */
	protected $shippingAddress;

	/** @ORM\Column(type="datetime", nullable=true) */
    protected $sendedMailAt;

	public function __construct(User $user = NULL)
	{
		if ($user) {
			$this->setUser($user);
		}
		$this->items = new ArrayCollection();
		$this->vouchers = new ArrayCollection();
		$this->changeItemsAt = new DateTime();
		parent::__construct();
	}

	public function setUser(User $user)
	{
		$this->user = $user;
		$user->basket = $this;
		$this->resetChangeItemsAt();
		return $this;
	}

	public function setItem(Stock $stock, $quantity, $checkQuantity = TRUE)
	{
		if ($checkQuantity && $quantity > $stock->inStore) {
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
		
		$this->resetChangeItemsAt(FALSE);
		
		return $this;
	}
	
	public function setMail($mail)
	{
		$this->mail = $mail;
		$this->resetChangeItemsAt();
		return $this;
	}
	
	public function resetChangeItemsAt($checkItemsCount = TRUE)
	{
		if (!$checkItemsCount || ($checkItemsCount && $this->items->count())) {
			$this->changeItemsAt = new DateTime();
		}
		return $this;
	}
	
	public function addVoucher(Voucher $voucher)
	{
		if ($this->vouchers->contains($voucher)) {
			throw new EntityException('cart.voucher.alreadyInCart');
		}
		
		$this->checkVoucherConditions($voucher);
		
		$this->vouchers->add($voucher);
		return $this;
	}
	
	private function checkVoucherConditions(Voucher $voucher)
	{
		switch ($voucher->type) {
			case Voucher::MINUS_VALUE:
				if ($voucher->value >= $this->getItemsTotalPrice()) {
					throw new EntityException('cart.voucher.higherThanProducts');
				}
				break;
			case Voucher::PERCENTAGE:
				break;
			default:
				throw new EntityException('cart.voucher.notAllowed');
		}
	}
	
	public function setAccessHash()
	{
		$this->accessHash = Random::generate(32);
		return $this;
	}
	
	public function hasPayments()
	{
		return $this->shipping && $this->payment;
	}
	
	public function hasAddress()
	{
		if (!$this->hasPayments()) {
			return FALSE;
		}
		if (!$this->mail) {
			return FALSE;
		}
		if ($this->needAddress() && (!$this->billingAddress || !$this->billingAddress->isComplete())) {
			return FALSE;
		}
		return TRUE;
	}
	
	public function hasItemInSpecialCategory()
	{
		$specialCategories = Category::getSpecialCategories();
		$isInSpecialCategory = function ($key, BasketItem $item) use ($specialCategories) {
			return $item->stock->product->isInCategories($specialCategories);
		};
		return $this->items->exists($isInSpecialCategory);
	}
	
	public function getSumOfItemsInSpecialCategory($level = NULL, $withVat = FALSE)
	{
		$sum = 0;
		$specialCategories = Category::getSpecialCategories();
		$isInSpecialCategory = function ($key, BasketItem $item) use ($specialCategories, &$sum, $level, $withVat) {
			if ($item->stock->product->isInCategories($specialCategories)) {
				$price = $item->stock->getPrice($level);
				$sum += ($withVat ? $price->withVat : $price->withoutVat)  * $item->quantity;
			}
			return TRUE;
		};
		$this->items->forAll($isInSpecialCategory);
		return $sum;
	}
	
	public function needAddress()
	{
		return $this->shipping && $this->shipping->needAddress;
	}
	
	public function getShippingAddress($realShipping = FALSE)
	{
		if ($realShipping) {
			if ($this->shippingAddress && $this->shippingAddress->isComplete()) {
				return $this->shippingAddress;
			} else {
				return $this->billingAddress;
			}
		} else {
			return $this->shippingAddress;
		}
	}
	
	public function getPhone()
	{
		return $this->billingAddress ? $this->billingAddress->phone : NULL;
	}
	
	public function isAllItemsInStore()
	{
		$isOnStock = function ($key, BasketItem $item) {
			return $item->stock->inStore >= $item->quantity;
		};
		return $this->items->forAll($isOnStock);
	}
	
	public function getIsCompany()
	{
		return $this->shippingAddress && $this->shippingAddress->isCompany();
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
	public function getItemsTotalPrice(Exchange $exchange = NULL, $level = NULL, $withVat = TRUE)
	{
		$totalPrice = 0;
		foreach ($this->items as $item) {
			/* @var $item BasketItem */
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
	public function getPaymentsPrice(Exchange $exchange = NULL, $level = NULL, $withVat = TRUE)
	{
		$totalPrice = 0;
		if ($this->shipping) {
			$shippingPrice = $this->shipping->getPrice($this, $level);
			$priceValue = $withVat ? $shippingPrice->withVat : $shippingPrice->withoutVat;
			$exchangedValue = $exchange ? $exchange->change($priceValue, NULL, NULL, Price::PRECISION) : $priceValue;			
			$totalPrice += $exchangedValue;
		}
		if ($this->payment) {
			$paymentPrice = $this->payment->getPrice($this, $level);
			$priceValue = $withVat ? $paymentPrice->withVat : $paymentPrice->withoutVat;
			$exchangedValue = $exchange ? $exchange->change($priceValue, NULL, NULL, Price::PRECISION) : $priceValue;			
			$totalPrice += $exchangedValue;
		}

		return $totalPrice;
	}

	/** @return float */
	public function getTotalPrice(Exchange $exchange = NULL, $level = NULL, $withVat = TRUE)
	{
		$itemsTotal = $this->getItemsTotalPrice($exchange, $level, $withVat);
		$paymentsTotal = $this->getPaymentsPrice($exchange, $level, $withVat);
		return $itemsTotal + $paymentsTotal;
	}

	/** @return float */
	public function getVatSum(Exchange $exchange, $level = NULL)
	{
		$withVat = $this->getTotalPrice($exchange, $level, TRUE);
		$withoutVat = $this->getTotalPrice($exchange, $level, FALSE);
		return $withVat - $withoutVat;
	}

	public function import(Basket $basket, $skipException = FALSE, $checkQuantity = TRUE)
	{
		if ($basket->itemsCount) {
			$this->items->clear();
		}
		/* @var $item BasketItem */
		foreach ($basket->items as $item) {
			try {
				$this->setItem($item->stock, $item->quantity, $checkQuantity);
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
