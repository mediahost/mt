<?php

namespace App\Listeners\Model\Facade;

use App\Model\Entity\Order;
use App\Model\Entity\OrderState;
use Kdyby\Events\Subscriber;
use Nette\Object;

class OrderListener extends Object implements Subscriber
{

	const CHANGE_STATE_OK_TO_NOK = 1;
	const CHANGE_STATE_NOK_TO_OK = 2;
	const CHANGE_STATE_NO_CHANGE = 3;

	public function getSubscribedEvents()
	{
		return [
			'App\Model\Facade\OrderFacade::onOrderCreate' => 'onCreate',
			'App\Model\Facade\OrderFacade::onOrderChangeState' => 'onChangeState',
		];
	}

	public function onCreate(Order $order)
	{
		// SEND MAIL
	}

	public function onChangeState(Order $order, OrderState $oldState, OrderState $newState)
	{
		// SEND MAIL
		// relock products
	}

}
