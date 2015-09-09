<?php

namespace App\Model\Facade;

use App\Model\Entity\Basket;
use App\Model\Entity\Order;
use App\Model\Entity\OrderItem;
use App\Model\Entity\OrderState;
use App\Model\Entity\OrderStateType;
use App\Model\Entity\Stock;
use App\Model\Entity\User;
use App\Model\Facade\Exception\FacadeException;
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

	/** @var array */
	public $onOrderChangeProducts = [];

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

		if (!$basket->isAllItemsInStore()) {
			throw new Exception\ItemsIsntOnStockException();
		}
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
		if (!$newState) {
			throw new FacadeException('State does not exists.');
		}
		
		$order->state = $newState;
		$this->orderRepo->save($order);

		$this->onOrderChangeState($order, $oldState);

		return $order;
	}

	/** @return Order */
	public function changeStateByOrderId($orderId, $newStateId)
	{
		if ($orderId) {
			$orderRepo = $this->em->getRepository(Order::getClassName());
			$order = $orderRepo->find($orderId);
		}
		
		if (!isset($order) || !$order) {
			throw new FacadeException('Order does not exists.');
		}

		return $this->changeState($order, $newStateId);
	}
	
	/**
	 * Změní pouze locky produktů. Nelze provést při změně stavu
	 * @param Order $order
	 * @param array $oldOrderItems
	 */
	public function relockProducts(Order $order, array $oldOrderItems)
	{
		$this->solveOrderItemsLocking($oldOrderItems, FALSE); // unlock
		$this->solveOrderItemsLocking($order->items, TRUE); // lock
	}

	/**
	 * Změní locky i počet kusů produktů. Očekává změnu stavu, beze změny nic neprovede
	 * @param Order $order
	 * @param OrderState $oldState
	 */
	public function relockAndRequantityProducts(Order $order, OrderState $oldState = NULL)
	{
		if (!$oldState) {
			$stateRepo = $this->em->getRepository(OrderState::getClassName());
			$oldState = $stateRepo->find(OrderState::NO_STATE);
		}
		$newState = $order->state;

		// Order -> Done => unlock + decrease
		// Order -> Storno => unlock
		// Done -> Order => increase + lock
		// Done -> Storno => increase
		// Storno -> Order => lock
		// Storno -> Done => decrease

		$increase = NULL;
		$lock = NULL;
		if ($oldState->type->isLocking(OrderStateType::LOCK_ORDER)) {
			if ($newState->type->isLocking(OrderStateType::LOCK_DONE)) { // Order -> Done => unlock + decrease
				$lock = FALSE;
				$increase = FALSE;
			} else if ($newState->type->isLocking(OrderStateType::LOCK_STORNO)) { // Order -> Storno => unlock
				$lock = FALSE;
			}
		} else if ($oldState->type->isLocking(OrderStateType::LOCK_DONE)) {
			if ($newState->type->isLocking(OrderStateType::LOCK_ORDER)) { // Done -> Order => increase + lock
				$lock = TRUE;
				$increase = TRUE;
			} else if ($newState->type->isLocking(OrderStateType::LOCK_STORNO)) { // Done -> Storno => increase
				$increase = TRUE;
			}
		} else if ($oldState->type->isLocking(OrderStateType::LOCK_STORNO)) {
			if ($newState->type->isLocking(OrderStateType::LOCK_ORDER)) { // Storno -> Order => lock
				$lock = TRUE;
			} else if ($newState->type->isLocking(OrderStateType::LOCK_DONE)) { // Storno -> Done => decrease
				$increase = FALSE;
			}
		}
		$this->solveOrderItemsLocking($order->items, $lock, $increase);
	}

	private function solveOrderItemsLocking($items, $lock = NULL, $increase = NULL)
	{
		$stockRepo = $this->em->getRepository(Stock::getClassName());
		/* @var $item OrderItem */
		foreach ($items as $item) {
			if ($lock === TRUE) {
				$item->stock->addLock($item->quantity);
			} else if ($lock === FALSE) {
				$item->stock->removeLock($item->quantity);
			}
			if ($increase === TRUE) {
				$item->stock->increaseQuantity($item->quantity);
			} else if ($increase === FALSE) {
				$item->stock->decreaseQuantity($item->quantity);
			}
			$stockRepo->save($item->stock);
		}
	}

}
