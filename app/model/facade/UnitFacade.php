<?php

namespace App\Model\Facade;

use App\Model\Entity\Unit;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Kdyby\Translation\Translator;
use Nette\Object;

class UnitFacade extends Object
{

	/** @var EntityManager @inject */
	public $em;

	/** @var Translator @inject */
	public $translator;

	/** @var EntityRepository */
	private $unitRepo;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->unitRepo = $this->em->getRepository(Unit::getClassName());
	}

	public function getUnitsList($lang = NULL)
	{
		if ($lang === NULL) {
			$lang = $this->translator->getDefaultLocale();
		}
		$vats = [];
		foreach ($this->unitRepo->findAll() as $unit) {
			/* @var $unit Unit */
			$unit->setCurrentLocale($lang);
			$vats[$unit->id] = (string) $unit;
		}
		return $vats;
	}
	
	public function __toString()
	{
		return $this->name;
	}

}
