<?php

namespace App\Model\Facade;

use App\Model\Entity\Address;
use App\Model\Entity\Basket;
use App\Model\Entity\EntityException;
use App\Model\Entity\Payment;
use App\Model\Entity\Price;
use App\Model\Entity\Shipping;
use App\Model\Entity\Stock;
use App\Model\Entity\Voucher;
use App\Model\Facade\Exception\InsufficientQuantityException;
use App\Model\Facade\Exception\MissingItemException;
use App\Model\Repository\BasketRepository;
use h4kuna\Exchange\Exchange;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use Nette\Security\IUserStorage;

class BasketFacade extends Object
{

	const KEEP_EMPTY_BASKETS = '3 days';
	const KEEP_BASKETS = '3 months';

	/** @var EntityManager @inject */
	public $em;

	/** @var Exchange @inject */
	public $exchange;

	/** @var IUserStorage @inject */
	public $userStorage;

	/** @var ShopFacade @inject */
	public $shopFacade;

	/** @var BasketRepository */
	private $basketRepo;

	/** @var Basket */
	private $basket;

	public function __construct(EntityManager $em)
	{
		$this->basketRepo = $em->getRepository(Basket::getClassName());
	}

	/** @var Basket */
	public function getBasket()
	{
		if (!$this->basket) {
			$this->basket = $this->userStorage->getBasket();
		}
		$this->basket->setShopVariant($this->shopFacade->getShopVariant());

		$level = NULL;
		$identity = $this->userStorage->getIdentity();
		if ($identity && $identity->group) {
			$level = $identity->group->level;
		}
		$this->removeIncorrectVouchers($level);
		return $this->basket;
	}

	public function clearBasket()
	{
		$this->userStorage->removeBasket();
		return $this;
	}

	public function import(Basket $toImport, $checkQuantity = TRUE)
	{
		$basket = $this->getBasket();
		$basket->import($toImport, TRUE, $checkQuantity);
		$this->basketRepo->save($basket);
		return $this;
	}

	/** @var int */
	public function add(Stock $stock, $quantity = 1)
	{
		$inBasket = $this->getCountInBasket($stock);
		return $this->setQuantity($stock, $inBasket + $quantity);
	}

	/** @var int */
	public function remove(Stock $stock)
	{
		return !$this->setQuantity($stock, 0);
	}

	/** @var int */
	public function setQuantity(Stock $stock, $quantity)
	{
		$basket = $this->getBasket();
		if ($quantity <= 0) {
			$quantity = 0;
		} else if ($quantity > $stock->inStore) {
			throw new InsufficientQuantityException();
		}
		$basket->setItem($stock, $quantity);
		$this->basketRepo->save($basket);

		return $quantity;
	}

	public function addVoucher($code, $level = NULL, Exchange $exchange = NULL)
	{
		if (empty($code)) {
			throw new Exception\BasketFacadeException('cart.voucher.invalid');
		}
		$voucherRepo = $this->em->getRepository(Voucher::getClassName());
		/* @var $voucher Voucher */
		$voucher = $voucherRepo->findOneByCode($code);
		if (!$voucher) {
			throw new Exception\BasketFacadeException('cart.voucher.invalid');
		}

		try {
			$basket = $this->getBasket();
			$basket->addVoucher($voucher, $level, $exchange);
			$this->basketRepo->save($basket);
		} catch (EntityException $ex) {
			throw new Exception\BasketFacadeException($ex->getMessage());
		}
		return $this;
	}

	public function removeVoucher(Voucher $voucher)
	{
		$basket = $this->getBasket();
		$basket->removeVoucher($voucher);
		$this->basketRepo->save($basket);
		return $this;
	}

	private function removeIncorrectVouchers($level = NULL)
	{
		/** @var Voucher $voucher */
		foreach ($this->basket->vouchers as $voucher) {
			if ($this->basket->checkVoucherConditions($voucher, $level, $this->exchange, FALSE)) {
				$this->basket->removeVoucher($voucher);
			}
		}
		$this->basketRepo->save($this->basket);
	}

	/** @var BasketFacade */
	public function setShipping(Shipping $shipping, $clearPayment = FALSE)
	{
		$basket = $this->getBasket();
		$basket->shipping = $shipping;
		if ($clearPayment) {
			$basket->payment = NULL;
		}

		$this->basketRepo->save($basket);
		return $this;
	}

	/** @var Shipping */
	public function getShipping()
	{
		$basket = $this->getBasket();
		return $basket->shipping;
	}

	/** @var Price */
	public function getShippingPrice($level = NULL)
	{
		$basket = $this->getBasket();
		return $basket->shipping->getPrice($basket, $level);
	}

	/** @var BasketFacade */
	public function setPayment(Payment $payment)
	{
		$basket = $this->getBasket();
		$basket->payment = $payment;

		$this->basketRepo->save($basket);
		return $this;
	}

	/** @var Payment */
	public function getPayment()
	{
		$basket = $this->getBasket();
		return $basket->payment;
	}

	/** @var Price */
	public function getPaymentPrice($level = NULL)
	{
		$basket = $this->getBasket();
		return $basket->payment->getPrice($basket, $level);
	}

	/** @var bool */
	public function isDirectPayment()
	{
		return $this->isCardPayment();
	}

	/** @var bool */
	public function isCardPayment()
	{
		$basket = $this->getBasket();
		return $basket->payment->isCard;
	}

	/** @var bool */
	public function isHomecreditSkPayment()
	{
		$basket = $this->getBasket();
		return $basket->payment->isHomecreditSk;
	}

	public function setAddress($mail, Address $billing = NULL, Address $shipping = NULL, $removeNull = TRUE)
	{
		$addressRepo = $this->em->getRepository(Address::getClassName());
		$basket = $this->getBasket();

		$basket->mail = $mail;

		if ($billing) {
			if (!$basket->billingAddress) {
				$basket->billingAddress = new Address();
			}
			$basket->billingAddress->import($billing, TRUE);
			$addressRepo->save($basket->billingAddress);
		} else if ($removeNull && $basket->billingAddress) {
			$toDeleteBilling = $basket->billingAddress;
			$basket->billingAddress = NULL;
		}

		if ($shipping) {
			if (!$basket->shippingAddress) {
				$basket->shippingAddress = new Address();
			}
			$basket->shippingAddress->import($shipping, TRUE);
			$addressRepo->save($basket->shippingAddress);
		} else if ($removeNull && $basket->shippingAddress) {
			$toDeleteShipping = $basket->shippingAddress;
			$basket->shippingAddress = NULL;
		}

		$this->basketRepo->save($basket);
		if ($removeNull) {
			if (isset($toDeleteBilling)) {
				$addressRepo->delete($toDeleteBilling);
			}
			if (isset($toDeleteShipping)) {
				$addressRepo->delete($toDeleteShipping);
			}
		}

		return $this;
	}

	/** @var int */
	public function getCountInBasket(Stock $stock)
	{
		try {
			$basket = $this->getBasket();
			return $basket->getItemCount($stock);
		} catch (MissingItemException $e) {
			return 0;
		}
	}

	/** @var int */
	public function getCountAllowedToAdd(Stock $stock)
	{
		$free = $stock->inStore - $this->getCountInBasket($stock);
		return $free > 0 ? $free : 0;
	}

	/** @var bool */
	public function isEmpty()
	{
		return !$this->getProductsCount();
	}

	/** @var bool */
	public function isAllItemsInStore()
	{
		$basket = $this->getBasket();
		return $basket->isAllItemsInStore();
	}

	/** @var bool */
	public function hasPayments()
	{
		$basket = $this->getBasket();
		return $basket->hasPayments();
	}

	/** @var bool */
	public function checkPayments()
	{
		$basket = $this->getBasket();
		$currencyCode = $this->exchange->getWeb()->getCode();
		if ($basket->hasPayments()) {
			if ($basket->payment->isHomecreditSk && $currencyCode === 'CZK') {
				$basket->payment = NULL;
			}
		}
		return $this;
	}

	/** @var bool */
	public function hasAddress()
	{
		$basket = $this->getBasket();
		return $basket->hasAddress();
	}

	/** @var bool */
	public function needAddress()
	{
		$basket = $this->getBasket();
		return $basket->needAddress();
	}

	/** @var Address|NULL */
	public function getBillingAddress()
	{
		$basket = $this->getBasket();
		return $basket->billingAddress;
	}

	/** @var Address|NULL */
	public function getShippingAddress()
	{
		$basket = $this->getBasket();
		return $basket->getShippingAddress(TRUE);
	}

	/** @var int */
	public function getProductsCount()
	{
		$basket = $this->getBasket();
		return $basket->itemsCount;
	}

	/** @var float */
	public function getProductsTotalPrice($level = NULL, $withVat = TRUE)
	{
		$basket = $this->getBasket();
		return $basket->getItemsTotalPrice($this->exchange, $level, $withVat);
	}

	/** @var float */
	public function getProductsVatSum($level = NULL)
	{
		$basket = $this->getBasket();
		return $basket->getItemsVatSum($this->exchange, $level);
	}

	/** @var float */
	public function getDiscountsTotalPrice($level = NULL)
	{
		$basket = $this->getBasket();
		return $basket->getVouchersTotalPrice($this->exchange, $level);
	}

	/** @var float */
	public function getTotalPrice($level = NULL, $withVat = TRUE)
	{
		$basket = $this->getBasket();
		return $basket->getTotalPrice($this->exchange, $level, $withVat);
	}

	/** @var float */
	public function getVatSum($level = NULL)
	{
		$basket = $this->getBasket();
		return $basket->getVatSum($this->exchange, $level);
	}

	/** @var float */
	public function getProductsTotalPriceToPay($level = NULL)
	{
		$basket = $this->getBasket();
		return $basket->getItemsWithVouchersTotalPrice($this->exchange, $level);
	}

	/** @var float */
	public function getTotalPriceToPay($level = NULL)
	{
		$basket = $this->getBasket();
		return $basket->getTotalPriceToPay($this->exchange, $level);
	}

	/** @var array */
	public function getItems()
	{
		$basket = $this->getBasket();
		return $basket->items;
	}

	/** @var array */
	public function getVouchers()
	{
		$basket = $this->getBasket();
		return $basket->vouchers;
	}

	/** @var bool */
	public function hasVouchers()
	{
		$basket = $this->getBasket();
		return (bool)$basket->getVouchersCount();
	}

	public function removeOldEmptyBaskets()
	{
		$emptyBaskets = $this->basketRepo->findEmpty(self::KEEP_EMPTY_BASKETS);

		foreach ($emptyBaskets as $basket) {
			$this->em->remove($basket);
		}
		$this->em->flush();
	}

	public function removeOldBaskets()
	{
		$emptyBaskets = $this->basketRepo->findOlders(self::KEEP_BASKETS);

		foreach ($emptyBaskets as $basket) {
			$this->em->remove($basket);
		}
		$this->em->flush();
	}

}
