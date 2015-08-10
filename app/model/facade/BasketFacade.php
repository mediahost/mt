<?php

namespace App\Model\Facade;

use Kdyby\Doctrine\EntityManager;
use Nette\Object;

class BasketFacade extends Object
{

	/** @var EntityManager @inject */
	public $em;

}
