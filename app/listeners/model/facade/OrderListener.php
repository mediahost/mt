<?php

namespace App\Listeners\Model\Facade;

use App\Extensions\Settings\SettingsStorage;
use App\Mail\Messages\Order\ChangeState\IDoneFactory;
use App\Mail\Messages\Order\ChangeState\IExpededFactory;
use App\Mail\Messages\Order\ChangeState\IStornoFactory;
use App\Mail\Messages\Order\ICreateOrderToCustomerFactory;
use App\Mail\Messages\Order\ICreateOrderToShopFactory;
use App\Model\Entity\Order;
use App\Model\Entity\OrderState;
use App\Model\Entity\OrderStateType;
use App\Model\Facade\OrderFacade;
use App\Model\Facade\UserFacade;
use Kdyby\Events\Subscriber;
use Nette\Object;

class OrderListener extends Object implements Subscriber
{

	/** @var OrderFacade @inject */
	public $orderFacade;

	/** @var UserFacade @inject */
	public $userFacade;

	/** @var SettingsStorage @inject */
	public $settings;

	/** @var ICreateOrderToCustomerFactory @inject */
	public $createOrderToCustomerMessage;

	/** @var ICreateOrderToShopFactory @inject */
	public $createOrderToShopMessage;

	/** @var IExpededFactory @inject */
	public $changeStateOrderExpededMessage;

	/** @var IDoneFactory @inject */
	public $changeStateOrderDoneMessage;

	/** @var IStornoFactory @inject */
	public $changeStateOrderStornoMessage;

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
		$messageForCustomer = $this->createOrderToCustomerMessage->create();
		$messageForCustomer->setOrder($order);
		$messageForCustomer->addTo($order->mail);
		$messageForCustomer->send();

		if ($this->settings->mails->createOrder) {
			$messageForShop = $this->createOrderToShopMessage->create();
			$messageForShop->setOrder($order);
			$messageForShop->addTo($this->settings->mails->createOrder);
			$messageForShop->send();
		}

		$this->orderFacade->relockAndRequantityProducts($order);
	}

	public function onChangeState(Order $order, OrderState $oldState)
	{
		if ($oldState->type !== $order->state->type) {
			switch ($order->state->type->id) {
				case OrderStateType::EXPEDED:
					$messageForCustomer = $this->changeStateOrderExpededMessage->create();
					break;
				case OrderStateType::DONE:
					$messageForCustomer = $this->changeStateOrderDoneMessage->create();
					break;
				case OrderStateType::STORNO:
					$messageForCustomer = $this->changeStateOrderStornoMessage->create();
					break;
				default:
					$messageForCustomer = NULL;
					break;
			}
			if ($messageForCustomer) {
				$messageForCustomer->setOrder($order);
				$messageForCustomer->addTo($order->mail);
				$messageForCustomer->send();
			}
		}

		$this->orderFacade->relockAndRequantityProducts($order, $oldState);

		$user = $order->user ? $order->user : $this->userFacade->findByMail($order->mail);
		if ($user) {
			$this->userFacade->recountBonus($user);
		}
	}

	public function onChangeProducts(Order $order, array $oldOrderItems)
	{
		$this->orderFacade->relockProducts($order, $oldOrderItems);
	}

}
