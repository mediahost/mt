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

	public function getPaymentsList(Basket $basket)
	{
		$paymentsList = [];
		$paymentsRepo = $this->em->getRepository(Payment::getClassName());
		$payments = $paymentsRepo->findAll();
		foreach ($payments as $payment) {
			if ($payment->active) {
				$paymentsList[$payment->id] = $this->getPaymentShippingFormat($payment, $basket);
			}
		}
		return $paymentsList;
	}

	public function getShippingsList(Basket $basket)
	{
		$shippingsList = [];
		$shippingsRepo = $this->em->getRepository(Shipping::getClassName());
		$shippings = $shippingsRepo->findAll();
		foreach ($shippings as $shipping) {
			if ($shipping->active) {
				$shippingsList[$shipping->id] = $this->getPaymentShippingFormat($shipping, $basket);
			}
		}
		return $shippingsList;
	}
	
	private function getPaymentShippingFormat($paymentOrShipping, Basket $basket, $withVat = TRUE)
	{
		$name = $this->translator->translate($paymentOrShipping);
		$freeName = $this->translator->translate('cart.free');
		$value = $paymentOrShipping->getPrice($basket);
		$price = $value->withVat > 0 ? $this->exchange->format($value, NULL, NULL, $withVat) : $freeName;
		return "{$name} ({$price})";
	}

}
