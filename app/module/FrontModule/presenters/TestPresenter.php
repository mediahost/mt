<?php

namespace App\FrontModule\Presenters;

use App\Extensions\PaymentNotification\Payment;
use App\Extensions\PaymentNotification\PaymentNotificationParser;

class TestPresenter extends BasePresenter
{

	/** @var PaymentNotificationParser @inject */
	public $paymentNotificationParser;

	public function actionDefault()
	{
		$testMails = $this->paymentNotificationParser->getTestMails();

		$this->paymentNotificationParser->onResolve[] = $this->processResolve;
		$this->paymentNotificationParser->onFailed[] = $this->processFailed;

		foreach ($testMails as $name => $mail) {
			dump($name);
			$this->paymentNotificationParser->parseMail($mail);
		}
		$this->terminate();
	}

	public function processResolve(Payment $payment)
	{
		dump($payment);
	}

	public function processFailed($mail)
	{
		dump($mail);
	}

}