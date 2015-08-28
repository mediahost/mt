<?php

namespace App\Model\Facade;

use App\Model\Entity\Basket;
use App\Model\Entity\Order;
use App\Model\Entity\OrderState;
use App\Model\Entity\User;
use App\Model\Repository\OrderRepository;
use h4kuna\Exchange\Exchange;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Translation\Translator;
use Nette\Object;

class OrderFacade extends Object
{

	/** @var array */
	public $onOrderCreate = [];

	/** @var array */
	public $onOrderChangeState = [];

	/** @var EntityManager @inject */
	public $em;

	/** @var Translator @inject */
	public $translator;

	/** @var Exchange @inject */
	public $exchange;

	/** @var OrderRepository */
	private $orderRepo;

	public function __construct(EntityManager $em)
	{
		$this->orderRepo = $em->getRepository(Order::getClassName());
	}

	/** @return Order */
	public function createFromBasket(Basket $basket, User $user = NULL)
	{
		$priceLevel = $user && $user->group ? $user->group->level : NULL;
		$locale = $this->translator->getLocale();
		$currency = $this->exchange->getWeb();
		$rate = (round($currency->getRate()) === (float) 1) ? NULL : $currency->getRate();
		$stateRepo = $this->em->getRepository(OrderState::getClassName());
		
		$order = new Order($locale, $user);
		$order->state = $stateRepo->find(OrderState::ORDERED_IN_SYSTEM);
		$order->setCurrency($currency->getCode(), $rate);
		$order->import($basket, $priceLevel);
		$this->orderRepo->save($order);
		
		$this->onOrderCreate($order);
		
		return $order;
	}

	/**
	 * @param Order $order
	 * @param OrderState|int $newState OrderState or OrderState ID
	 * @return Order
	 */
	public function changeState(Order $order, $newState)
	{
		$oldState = $order->state;
		if (!$newState instanceof OrderState) {
			$stateRepo = $this->em->getRepository(OrderState::getClassName());
			$newState = $stateRepo->find($newState);
		}
		$order->state = $newState;
		$this->orderRepo->save($order);
		
		$this->onOrderChangeState($order, $oldState, $newState);
		
		return $order;
	}

}
