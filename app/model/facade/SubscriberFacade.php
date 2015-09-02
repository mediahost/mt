<?php

namespace App\Model\Facade;

use App\Model\Entity\Newsletter\Subscriber;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Kdyby\Translation\Translator;
use Nette\Object;

class SubscriberFacade extends Object
{

	/** @var EntityManager */
	private $em;

	/** @var EntityRepository */
	private $repository;

	/** @var Translator @inject */
	public $translator;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->repository = $this->em->getRepository(Subscriber::getClassName());
	}

	public function findByType($type, $locale = NULL)
	{
		$criteria['type'] = $type;
		
		if (!empty($locale)) {
			$criteria['locale'] = $locale;
		}

		return $this->repository->findBy($criteria);
	}

}
