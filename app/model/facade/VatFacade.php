<?php

namespace App\Model\Facade;

use App\Model\Entity\Vat;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Nette\Object;

class VatFacade extends Object
{

	/** @var EntityManager @inject */
	public $em;

	/** @var EntityRepository */
	private $vatRepo;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->vatRepo = $this->em->getRepository(Vat::getClassName());
	}

	public function getValues()
	{
		$vats = [];
		foreach ($this->vatRepo->findAll() as $vat) {
			$vats[$vat->id] = (string) $vat;
		}
		return $vats;
	}

}
