<?php

namespace App\Model\Facade;

use App\Extensions\Settings\Model\Service\LanguageService;
use App\Model\Entity\Producer;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Nette\Object;

class ProducerFacade extends Object
{

	/** @var EntityManager @inject */
	public $em;

	/** @var LanguageService @inject */
	public $languageService;

	/** @var EntityRepository */
	private $producerRepo;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->producerRepo = $this->em->getRepository(Producer::getClassName());
	}

	public function getProducersList($lang = NULL)
	{
		if ($lang === NULL) {
			$lang = $this->languageService->defaultLanguage;
		}
		$producers = [];
		foreach ($this->producerRepo->findAll() as $producer) {
			/* @var $producer Producer */
			$producers[$producer->id] = (string) $producer;
		}
		@uasort($producers, 'strcoll');
		return $producers;
	}

}
