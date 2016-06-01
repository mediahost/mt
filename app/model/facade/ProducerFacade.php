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

	public function getProducersList($onlyWithChildren = FALSE)
	{
		$producers = [];
		foreach ($this->producerRepo->findAll() as $producer) {
			if (!$onlyWithChildren || count($producer->lines)) {
				$producers[$producer->id] = (string)$producer;
			}
		}
		@uasort($producers, 'strcoll');
		return $producers;
	}

	public function getLinesList(Producer $producer = NULL, $fullPath = FALSE, $onlyWithChildren = FALSE)
	{
		$lines = [];
		if ($producer) {
			$finded = $this->lineRepo->findBy(['producer' => $producer]);
		} else {
			$finded = $this->lineRepo->findAll();
		}
		foreach ($finded as $line) {
			if (!$onlyWithChildren || count($line->models)) {
				$lines[$line->id] = $fullPath ? $line->getFullName() : (string)$line;
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
			$models[$model->id] = $fullPath ? $model->getFullName() : (string)$model;
		}

		@uasort($models, 'strcoll');

		return $models;
	}

	public function reorder($entity, $position)
	{
		if ($entity instanceof Producer) {
			return $this->reorderProducer($entity, $position);
		} else if ($entity instanceof ProducerLine) {
			return $this->reorderLine($entity, $position);
		} else if ($entity instanceof ProducerModel) {
			return $this->reorderModel($entity, $position);
		}
		return FALSE;
	}

	private function reorderProducer(Producer $producer, $position)
	{
		$allProducers = $this->producerRepo->findBy([], ['priority' => 'ASC']);
		foreach ($allProducers as $i => $producerItem) {
			if ($producerItem->id === $producer->id) {
				$producerItem->priority = $position;
			} else if ($i < $position) {
				$producerItem->priority = $i;
			} else {
				$producerItem->priority = $i + 1;
			}
			$this->producerRepo->save($producerItem);
		}
	}

	private function reorderLine(ProducerLine $line, $position)
	{
		$lines = $this->lineRepo->findBy(['producer' => $line->producer], ['priority' => 'ASC']);
		foreach ($lines as $i => $lineItem) {
			if ($lineItem->id === $line->id) {
				$lineItem->priority = $position;
			} else if ($i < $position) {
				$lineItem->priority = $i;
			} else {
				$lineItem->priority = $i + 1;
			}
			$this->lineRepo->save($lineItem);
		}
	}

	private function reorderModel(ProducerModel $model, $position)
	{
		$models = $this->modelRepo->findBy(['line' => $model->line], ['priority' => 'ASC']);
		foreach ($models as $i => $modelItem) {
			if ($modelItem->id === $model->id) {
				$modelItem->priority = $position;
			} else if ($i < $position) {
				$modelItem->priority = $i;
			} else {
				$modelItem->priority = $i + 1;
			}
			$this->modelRepo->save($modelItem);
		}
	}

}
