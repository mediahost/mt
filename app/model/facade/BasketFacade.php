<?php

namespace App\Model\Facade;

use App\Model\Entity\Basket;
use App\Model\Entity\Stock;
use App\Model\Facade\Exception\InsufficientQuantityException;
use App\Model\Facade\Exception\MissingItemException;
use App\Model\Repository\BasketRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use Nette\Security\IUserStorage;

class BasketFacade extends Object
{

	/** @var EntityManager @inject */
	public $em;

	/** @var IUserStorage @inject */
	public $userStorage;

	/** @var BasketRepository */
	private $basketRepo;

	/** @var Basket */
	private $basket;

	public function __construct(EntityManager $em)
	{
		$this->basketRepo = $em->getRepository(Basket::getClassName());
	}

	/** @var Basket */
	public function getBasket()
	{
		if (!$this->basket) {
			$this->basket = $this->userStorage->getBasket();
		}
		return $this->basket;
	}

	/** @var int */
	public function add(Stock $stock, $quantity = 1)
	{
		$inBasket = $this->getCountInBasket($stock);
		return $this->setQuantity($stock, $inBasket + $quantity);
	}

	/** @var int */
	public function remove(Stock $stock)
	{
		return !$this->setQuantity($stock, 0);
	}

	/** @var int */
	public function setQuantity(Stock $stock, $quantity)
	{
		$basket = $this->getBasket();
		if ($quantity <= 0) {
			$quantity = 0;
		} else if ($quantity > $stock->inStore) {
			throw new InsufficientQuantityException();
		}
		$basket->setItem($stock, $quantity);
		$this->basketRepo->save($basket);

		return $quantity;
	}

	/** @var int */
	public function getCountInBasket(Stock $stock)
	{
		try {
			$basket = $this->getBasket();
			return $basket->getItemCount($stock);
		} catch (MissingItemException $e) {
			return 0;
		}
	}

	/** @var int */
	public function getCountAllowedToAdd(Stock $stock)
	{
		$free = $stock->inStore - $this->getCountInBasket($stock);
		return $free > 0 ? $free : 0;
	}

}
