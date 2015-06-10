<?php

namespace App\Model\Facade;

use App\Extensions\Settings\Model\Service\LanguageService;
use App\Model\Entity\Unit;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Nette\Object;

class UnitFacade extends Object
{

	/** @var EntityManager @inject */
	public $em;

	/** @var LanguageService @inject */
	public $languageService;

	/** @var EntityRepository */
	private $unitRepo;

	public function __construct(EntityManager $em, LanguageService $languageService)
	{
		$this->em = $em;
		$this->languageService = $languageService;
		$this->unitRepo = $this->em->getRepository(Unit::getClassName());
	}

	public function getUnitsList($lang = NULL)
	{
		if ($lang === NULL) {
			$lang = $this->languageService->defaultLanguage;
		}
		$vats = [];
		foreach ($this->unitRepo->findAll() as $unit) {
			/* @var $unit Unit */
			$unit->setCurrentLocale($lang);
			$vats[$unit->id] = (string) $unit;
		}
		return $vats;
	}

}
