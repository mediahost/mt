<?php

namespace App\Model\Facade;

use App\Model\Entity\Basket;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use Nette\Security\IUserStorage;

class BasketFacade extends Object
{

	/** @var EntityManager @inject */
	public $em;

	/** @var IUserStorage @inject */
	public $userStorage;
	
	/** @var Basket */
	private $basket;
	
	public function getBasket()
	{
		if (!$this->basket) {
			$this->basket = $this->userStorage->getBasket();
		}
		return $this->basket;
	}

}
