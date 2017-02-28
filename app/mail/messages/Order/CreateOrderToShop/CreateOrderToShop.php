<?php

namespace App\Mail\Messages\Order;

use App\Mail\Messages\BaseMessage;

class CreateOrderToShop extends BaseMessage
{

	protected function beforeSend()
	{
		$this->setSubject($this->translator->translate('mail.subject.createOrderShop', ['id' => $this->order->id]));
		parent::beforeSend();
	}

}

interface ICreateOrderToShopFactory
{

	/**
	 * @return CreateOrderToShop
	 */
	public function create();
}
