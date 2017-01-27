<?php

namespace App\Model\Facade;

use App\ExchangeHelper;
use App\Model\Entity\Basket;
use App\Model\Entity\Payment;
use App\Model\Entity\Shipping;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Translation\Translator;
use Nette\Object;

class PaymentsFacade extends Object
{

	/** @var EntityManager @inject */
	public $em;

	/** @var Translator @inject */
	public $translator;

	/** @var ExchangeHelper @inject */
	public $exchange;

	/** @var ShopFacade @inject */
	public $shopFacade;

	public function getPaymentsList(Basket $basket, $level = NULL, $withVat = TRUE)
	{
		$paymentsList = [];
		$paymentsRepo = $this->em->getRepository(Payment::getClassName());

		$criteria = [
			'active' => TRUE,
			'shopVariant' => $this->shopFacade->getShopVariant(),
		];

		$currencyCode = $this->exchange->getExchange()->getWeb()->getCode();
		switch ($currencyCode) {
			case 'EUR':
				break;
			case 'CZK':
				$criteria['isHomecreditSk'] = FALSE;
				break;
		}

		$payments = $paymentsRepo->findBy($criteria);
		foreach ($payments as $payment) {
			$paymentsList[$payment->id] = $this->getPaymentShippingFormat($payment, $basket, $level, $withVat);
		}
		return $paymentsList;
	}

	public function getShippingsList(Basket $basket, $level = NULL, $withVat = TRUE)
	{
		$shippingsList = [];
		$shippingsRepo = $this->em->getRepository(Shipping::getClassName());
		$shippings = $shippingsRepo->findBy([
			'active' => TRUE,
			'shopVariant' => $this->shopFacade->getShopVariant(),
		]);
		foreach ($shippings as $shipping) {
			$shippingsList[$shipping->id] = $this->getPaymentShippingFormat($shipping, $basket, $level, $withVat);
		}
		return $shippingsList;
	}

	private function getPaymentShippingFormat($paymentOrShipping, Basket $basket, $level = NULL, $withVat = TRUE)
	{
		$name = $this->translator->translate($paymentOrShipping);
		$freeName = $this->translator->translate('cart.free');
		$value = $paymentOrShipping->getPrice($basket, $level);
		$price = $value->withVat > 0 ? $this->exchange->format($value, NULL, NULL, $withVat) : $freeName;
		return "{$name} ({$price})";
	}

}
