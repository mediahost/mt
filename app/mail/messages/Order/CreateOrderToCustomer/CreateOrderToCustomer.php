<?php

namespace App\Mail\Messages\Order;

use App\Mail\Messages\BaseMessage;

class CreateOrderToCustomer extends BaseMessage
{
	
	protected function beforeSend()
	{
		$this->setSubject($this->translator->translate('mail.subject.createOrder', ['id' => $this->order->id]));
		parent::beforeSend();
	}

}

interface ICreateOrderToCustomerFactory
{

	/**
	 * @return CreateOrderToCustomer
	 */
	public function create();
}
