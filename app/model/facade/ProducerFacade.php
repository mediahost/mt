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

	const ORDER_DIR_UP = 'up';
	const ORDER_DIR_DOWN = 'down';
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

	public function reorder($entity, $new, $old)
	{
		if ($entity instanceof Producer) {
			return $this->reorderProducer($entity, $new, $old);
		} else if ($entity instanceof ProducerLine) {
			return $this->reorderLine($entity, $new, $old);
		} else if ($entity instanceof ProducerModel) {
			return $this->reorderModel($entity, $new, $old);
		}
		return FALSE;
	}

	private function reorderProducer(Producer $producer, $new, $old)
	{
		$allProducers = $this->producerRepo->findBy([], ['priority' => 'ASC']);
		$this->rebasePriorities($allProducers, $producer, $new, $old);
	}

	private function reorderLine(ProducerLine $line, $new, $old)
	{
		$lines = $this->lineRepo->findByProducer($line->producer, ['priority' => 'ASC']);
		$this->rebasePriorities($lines, $line, $new, $old);
	}

	private function reorderModel(ProducerModel $model, $new, $old)
	{
		$models = $this->modelRepo->findByLine($model->line, ['priority' => 'ASC']);
		$this->rebasePriorities($models, $model, $new, $old);
	}

	private function rebasePriorities($entities, $entity, $new, $old)
	{
		$dir = $new > $old ? self::ORDER_DIR_UP : self::ORDER_DIR_DOWN;
		foreach ($entities as $i => $entityItem) {
			if ($entityItem->id === $entity->id) {
				$entityItem->priority = $new;
			} else if (in_array($i, range($new, $old))) {
				$entityItem->priority = $dir == self::ORDER_DIR_UP ? $i - 1 : $i + 1;
			} else {
				$entityItem->priority = $i;
			}
			$this->producerRepo->save($entityItem);
		}
	}

}
