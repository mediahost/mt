<?php

namespace App\Mail\Messages\Order\Payment;

use App\Mail\Messages\BaseMessage;

class SuccessPayment extends BaseMessage
{

	protected function beforeSend()
	{
		$this->setSubject($this->translator->translate('mail.subject.changePaymentOrder', ['id' => $this->order->id]));
		parent::beforeSend();
	}

}

interface ISuccessPaymentFactory
{

	/**
	 * @return SuccessPayment
	 */
	public function create();
}
