<?php

namespace App\Listeners\Model\Facade;

use App\Model\Entity\Order;
use App\Model\Entity\OrderState;
use App\Model\Facade\OrderFacade;
use Kdyby\Events\Subscriber;
use Nette\Object;

class OrderListener extends Object implements Subscriber
{

	/** @var OrderFacade @inject */
	public $orderFacade;

	const CHANGE_STATE_OK_TO_NOK = 1;
	const CHANGE_STATE_NOK_TO_OK = 2;
	const CHANGE_STATE_NO_CHANGE = 3;

	public function getSubscribedEvents()
	{
		return [
			'App\Model\Facade\OrderFacade::onOrderCreate' => 'onCreate',
			'App\Model\Facade\OrderFacade::onOrderChangeState' => 'onChangeState',
			'App\Model\Facade\OrderFacade::onOrderChangeProducts' => 'onChangeProducts',
		];
	}

	public function onCreate(Order $order)
	{
		// SEND MAIL
		$this->orderFacade->relockAndRequantityProducts($order);
	}

	public function onChangeState(Order $order, OrderState $oldState)
	{
		// SEND MAIL
		$this->orderFacade->relockAndRequantityProducts($order, $oldState);
	}

	public function onChangeProducts(Order $order, array $oldOrderItems)
	{
		// SEND MAIL
		$this->orderFacade->relockProducts($order, $oldOrderItems);
	}

}
