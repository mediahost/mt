<?php

namespace App\Model\Facade;

use App\Model\Entity\Producer;
use App\Model\Entity\ProducerLine;
use App\Model\Entity\ProducerModel;
use App\Model\Repository\ProducerRepository;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Kdyby\Translation\Translator;
use Nette\Caching\IStorage;
use Nette\Object;

class ProducerFacade extends Object
{

	const TAG_ALL_PRODUCERS = 'all-producers';
	const TAG_ALL_LINES = 'all-lines';
	const TAG_ALL_MODELS = 'all-models';
	const TAG_PRODUCER = 'producer_';
	const TAG_LINE = 'producer-line_';
	const TAG_MODEL = 'producer-model_';

	/** @var EntityManager @inject */
	public $em;

	/** @var Translator @inject */
	public $translator;

	/** @var IStorage @inject */
	public $cacheStorage;

	/** @var ProducerRepository */
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

	public function getProducers($onlyWithChildren = FALSE, $onlyWithProducts = FALSE)
	{
		return $this->getProducersList($onlyWithChildren, $onlyWithProducts, FALSE);
	}

	public function getProducersList($onlyWithChildren = FALSE, $onlyWithProducts = FALSE, $toString = TRUE)
	{
		$producers = [];
		foreach ($this->producerRepo->findAllWithPriority() as $producer) {
			if ((!$onlyWithChildren || $producer->hasLines(TRUE)) && (!$onlyWithProducts || $producer->hasProducts())) {
				$producers[$producer->id] = $toString ? (string)$producer : $producer;
			}
		}
		return $producers;
	}

	public function getLinesList(Producer $producer = NULL, $fullPath = FALSE, $onlyWithChildren = FALSE, $onlyWithProducts = FALSE)
	{
		$conditions = [];
		if ($producer) {
			$conditions['producer'] = $producer;
		}

		$lines = [];
		foreach ($this->lineRepo->findBy($conditions, ['priority' => 'ASC']) as $line) {
			if ((!$onlyWithChildren || $line->hasModels()) && (!$onlyWithProducts || $line->hasProducts())) {
				$lines[$line->id] = $fullPath ? $line->getFullName() : (string)$line;
			}
		}
		return $lines;
	}

	public function getModelsList(ProducerLine $line = NULL, $fullPath = FALSE, $onlyWithProducts = FALSE)
	{
		$conditions = [];
		if ($line) {
			$conditions['line'] = $line;
		}

		$models = [];
		foreach ($this->modelRepo->findBy($conditions, ['priority' => 'ASC']) as $model) {
			if (!$onlyWithProducts || $model->hasProducts()) {
				$models[$model->id] = $fullPath ? $model->getFullName() : (string)$model;
			}
		}

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
