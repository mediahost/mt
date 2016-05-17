<?php

namespace App\Model\Facade;

use App\Model\Entity\Producer;
use App\Model\Entity\ProducerLine;
use App\Model\Entity\ProducerModel;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Nette\Object;

class ProducerFacade extends Object
{

	/** @var EntityManager @inject */
	public $em;

	/** @var EntityRepository */
	private $producerRepo;

	/** @var EntityRepository */
	private $lineRepo;

	/** @var EntityRepository */
	private $modelRepo;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->producerRepo = $this->em->getRepository(Producer::getClassName());
		$this->lineRepo = $this->em->getRepository(ProducerLine::getClassName());
		$this->modelRepo = $this->em->getRepository(ProducerModel::getClassName());
	}

	public function getProducersList($onlyWithChildren = TRUE)
	{
		$producers = [];
		foreach ($this->producerRepo->findAll() as $producer) {
			if (!$onlyWithChildren || count($producer->lines)) {
				$producers[$producer->id] = (string) $producer;
			}
		}
		@uasort($producers, 'strcoll');
		return $producers;
	}

	public function getLinesList(Producer $producer = NULL, $fullPath = FALSE, $onlyWithChildren = TRUE)
	{
		$lines = [];
		if ($producer) {
			$finded = $this->lineRepo->findBy(['producer' => $producer]);
		} else {
			$finded = $this->lineRepo->findAll();
		}
		foreach ($finded as $line) {
			if (!$onlyWithChildren || count($line->models)) {
				$lines[$line->id] = $fullPath ? $line->getFullName() : (string) $line;
			}
		}
		@uasort($lines, 'strcoll');
		return $lines;
	}

	public function getModelsList(ProducerLine $line = NULL, $fullPath = FALSE, $onlyBuyout = FALSE)
	{
		$filter = NULL;

		if ($onlyBuyout !== FALSE) {
			$filter['buyoutPrice >'] = '0';
		}

		if ($line) {
			$filter['line'] = $line;
		}

		if ($filter !== NULL) {
			$finded = $this->modelRepo->findBy($filter);
		} else {
			$finded = $this->modelRepo->findAll();
		}

		$models = [];
		
		foreach ($finded as $model) {
			$models[$model->id] = $fullPath ? $model->getFullName() : (string) $model;
		}
		
		@uasort($models, 'strcoll');
		
		return $models;
	}

}
