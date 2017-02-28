<?php

namespace App\Mail\Messages\Order\Payment;

use App\Mail\Messages\BaseMessage;
use App\Model\Entity\Order;

class ErrorPayment extends BaseMessage
{

	protected function beforeSend()
	{
		$this->setSubject($this->translator->translate('mail.subject.changePaymentOrder', ['id' => $this->order->id]));
		parent::beforeSend();
	}
	
	public function setOrder(Order $order)
	{
		$order->payment->origin->setCurrentLocale($this->translator->getLocale());
		return parent::setOrder($order);
	}

}

interface IErrorPaymentFactory
{

	/**
	 * @return ErrorPayment
	 */
	public function create();
}
