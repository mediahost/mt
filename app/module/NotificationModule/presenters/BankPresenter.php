<?php

namespace App\NotificationModule\Presenters;

use App\Model\Entity\Order;
use App\Model\Facade\OrderFacade;
use App\Service\PaymentNotification\Payment;
use App\Service\PaymentNotification\PaymentNotificationParser;
use h4kuna\Exchange\Exchange;

class BankPresenter extends BasePresenter
{

	/** @var PaymentNotificationParser @inject */
	public $paymentNotificationParser;

	/** @var OrderFacade @inject */
	public $orderFacade;

	/** @var Exchange @inject */
	public $exchange;

	public function actionDefault($email)
	{
		$this->exchange->setWeb('eur');
		$this->paymentNotificationParser->onResolve[] = [$this, 'processPayment'];
		$this->paymentNotificationParser->parseMail($email);
		$this->terminate();
	}

	public function processPayment(Payment $payment)
	{
		$orderRepo = $this->em->getRepository(Order::getClassName());
		$order = $orderRepo->find($payment->vs);
		if (!$order) {
		    return;
		}
		if ($order->getTotalPrice($this->exchange) == $payment->price) {
			$this->orderFacade->payOrder($order, $payment->type);
		}
	}

}
