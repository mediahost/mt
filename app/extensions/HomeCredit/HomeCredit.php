<?php

namespace App\Extensions;

use App\Model\Entity\Address;
use App\Model\Entity\Order;
use App\Model\Entity\OrderItem;
use App\Model\Entity\Payment;
use App\Model\Entity\Price;
use App\Model\Entity\ShopVariant;
use Exception;
use h4kuna\Exchange\Exchange;
use Kdyby\Translation\Translator;
use Nette\Http\Url;
use Nette\Object;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;

class HomeCredit extends Object
{

	const HC_RET_YES = 'Y';
	const HC_RET_NO = 'N';
	const HC_RET_LATE = 'L';
	const MIN_PRICE = 80;
	const ALLOWED_CURRENCY = 'EUR';

	/** @var Translator @inject */
	public $translator;

	/** @var Exchange @inject */
	public $exchange;

	/** @var string */
	private $shopId;

	/** @var string */
	private $privateKey;

	/** @var string */
	private $iShopUrl;

	/** @var string */
	private $iCalcUrl;

	/** @var ArrayHash */
	private $orderId;

	/** @var ArrayHash */
	private $product;

	/** @var Address */
	private $address;

	/** @var string */
	private $mail;

	/** @var ShopVariant */
	private $shopVariant;

	/** @var string */
	private $returnLink;

	// <editor-fold desc="setters">

	public function setShop($shopId, $key, $iShopUrl, $iCalcUrl)
	{
		$this->shopId = $shopId;
		$this->privateKey = $key;
		$this->iShopUrl = $iShopUrl;
		$this->iCalcUrl = $iCalcUrl;
		return $this;
	}

	public function setProduct($price, $name = NULL, $producer = NULL)
	{
		$this->product = ArrayHash::from([
			'price' => Price::floatToStr($price),
			'name' => $name,
			'producer' => $producer,
		]);
		return $this;
	}

	public function setShopVariant(ShopVariant $shopVariant)
	{
		$this->shopVariant = $shopVariant;
		return $this;
	}

	public function setOrder(Order $order)
	{
		/* @var $mostExpensive OrderItem */
		$mostExpensive = $order->mostExpensiveItem;
		$producer = $mostExpensive->stock->product->producer;
		$producerName = $producer ? $producer->name : $this->translator->translate('Producer');
		$totalPrice = $order->getTotalPriceToPay($this->exchange);

		$this->orderId = $order->id;
		$this->setProduct($totalPrice, $mostExpensive->name, $producerName);
		$this->address = $order->billingAddress;
		$this->mail = $order->mail;

		return $this;
	}

	public function setReturnLink($link)
	{
		$this->returnLink = $link;
	}

	// </editor-fold>

	public function getIShopLink()
	{
		if ($this->exchange->getWeb()->getCode() !== self::ALLOWED_CURRENCY) {
			return NULL;
		}
		if (!$this->orderId) {
			throw new HomeCreditException('Order ID is not set');
		}
		if (!$this->product) {
			throw new HomeCreditException('Product is not set');
		}
		if ($this->product->price < self::MIN_PRICE) {
			return NULL;
		}
		if (!$this->address) {
			throw new HomeCreditException('Address is not set');
		}
		if (!$this->returnLink) {
			throw new HomeCreditException('Return URL is not set');
		}
		$allowedPayment = FALSE;
		if ($this->shopVariant) {
			foreach ($this->shopVariant->payments as $payment) {
				/** @var Payment $payment */
				if ($allowedPayment) {
					break;
				}
				$allowedPayment = $payment->active && $payment->isHomecreditSk;
			}
		} else {
			throw new HomeCreditException('Shop variant is not set');
		}
		if (!$allowedPayment) {
			return NULL;
		}

		$time = (new DateTime())->format('d.m.Y-H:i:s');

		$plainText = $this->shopId . $this->orderId . $this->product->price .
			$this->address->firstName . $this->address->surname .
			$this->product->name . $this->product->producer .
			$time . $this->privateKey;
		$checksum = md5($plainText);

		$url = new Url($this->iShopUrl);
		$url
			->setQueryParameter('shop', $this->shopId)
			->setQueryParameter('o_code', $this->orderId)
			->setQueryParameter('o_price', $this->product->price)
			->setQueryParameter('c_name', $this->address->firstName)
			->setQueryParameter('c_surname', $this->address->surname)
			->setQueryParameter('c_mobile', $this->address->phone)
			->setQueryParameter('c_email', $this->mail)
			->setQueryParameter('c_p_street', $this->address->streetOnly)
			->setQueryParameter('c_p_num', $this->address->streetNumber)
			->setQueryParameter('c_p_city', $this->address->city)
			->setQueryParameter('c_p_zip', $this->address->zipcode)
			->setQueryParameter('g_name', $this->product->name)
			->setQueryParameter('g_producer', $this->product->producer)
			->setQueryParameter('ret_url', $this->returnLink)
			->setQueryParameter('time_request', $time)
			->setQueryParameter('sh', $checksum);

		return $url;
	}

	public function getCalcLink()
	{
		if ($this->exchange->getWeb()->getCode() !== self::ALLOWED_CURRENCY) {
			return NULL;
		}
		if (!$this->product) {
			throw new HomeCreditException('Product is not set');
		}
		if ($this->product->price < self::MIN_PRICE) {
			return NULL;
		}
		$allowedPayment = FALSE;
		if ($this->shopVariant) {
			foreach ($this->shopVariant->payments as $payment) {
				/** @var Payment $payment */
				if ($allowedPayment) {
					break;
				}
				$allowedPayment = $payment->active && $payment->isHomecreditSk;
			}
		} else {
			throw new HomeCreditException('Shop variant is not set');
		}
		if (!$allowedPayment) {
			return NULL;
		}

		$time = (new DateTime())->format('d.m.Y-H:i:s');
		$plainText = $this->shopId . $this->product->price . $time . $this->privateKey;
		$checksum = md5($plainText);

		$url = new Url($this->iCalcUrl);
		$url
			->setQueryParameter('shop', $this->shopId)
			->setQueryParameter('o_price', $this->product->price)
			->setQueryParameter('time_request', $time)
			->setQueryParameter('sh', $checksum);

		return $url;
	}

	public function isPayed($hcRet)
	{
		return $hcRet === self::HC_RET_YES;
	}

}

class HomeCreditException extends Exception
{

}
