<?php

namespace App\Model\Facade;

use App\Model\Entity\Producer;
use App\Model\Entity\ProducerLine;
use App\Model\Entity\ProducerModel;
use App\Model\Entity\Product;
use App\Model\Repository\ProducerLineRepository;
use App\Model\Repository\ProducerModelRepository;
use App\Model\Repository\ProducerRepository;
use App\Model\Repository\ProductRepository;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Translation\Translator;
use Nette\Object;

class ProducerFacade extends Object
{

	const ORDER_DIR_UP = 'up';
	const ORDER_DIR_DOWN = 'down';
	const TAG_PRODUCER = 'producer_';
	const TAG_LINE = 'producer-line_';
	const TAG_MODEL = 'producer-model_';

	/** @var EntityManager @inject */
	public $em;

	/** @var Translator @inject */
	public $translator;

	/** @var ProductRepository */
	private $productRepo;

	/** @var ProducerRepository */
	private $producerRepo;

	/** @var ProducerLineRepository */
	private $lineRepo;

	/** @var ProducerModelRepository */
	private $modelRepo;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->productRepo = $this->em->getRepository(Product::getClassName());
		$this->producerRepo = $this->em->getRepository(Producer::getClassName());
		$this->lineRepo = $this->em->getRepository(ProducerLine::getClassName());
		$this->modelRepo = $this->em->getRepository(ProducerModel::getClassName());
	}

	public function getProducers($onlyWithChildren = FALSE, $onlyWithProducts = FALSE)
	{
		return $this->getProducersList($onlyWithChildren, $onlyWithProducts, FALSE, FALSE);
	}

	public function getProducersList($onlyWithChildren = FALSE, $onlyWithProducts = FALSE, $onlyWithAccProducers = FALSE, $toString = TRUE)
	{
		$criteria = [];
		if ($onlyWithProducts) {
			$criteria['id IN'] = $this->productRepo->getProducersIds();
		}
		if ($onlyWithAccProducers !== FALSE) {
			$productIds = is_array($onlyWithAccProducers) && count($onlyWithAccProducers) ? $onlyWithAccProducers : [];
			$ids = $this->productRepo->getAccessoriesProducersIds($productIds);
			$criteria['id IN'] = isset($criteria['id IN']) ? array_diff($criteria['id IN'], $ids) : $ids;
		}

		$lines = [];
		if ($onlyWithChildren) {
			$lines = $this->lineRepo->findPairs('id', 'producerId');
		}

		$producers = [];
		foreach ($this->producerRepo->findAllWithPriority($criteria) as $producer) {
			$isIn = TRUE;
			if ($onlyWithChildren) {
				$isIn = array_key_exists($producer->id, $lines);
			}
			if ($isIn) {
				$producers[$producer->id] = $toString ? (string)$producer : $producer;
			}
		}
		return $producers;
	}

	public function getLinesList(Producer $producer = NULL, $fullPath = FALSE, $onlyWithChildren = FALSE, $onlyWithProducts = FALSE, $onlyWithAccLines = FALSE)
	{
		$models = [];
		if ($onlyWithChildren) {
			$models = $this->modelRepo->findPairs('id', 'lineId');
		}
		$producersIdsInProducts = [];
		if ($onlyWithProducts) {
			$producersIdsInProducts = $this->productRepo->getProducersIds();
		}

		$criteria = [];
		if ($producer) {
			$criteria['producer'] = $producer;
		}
		if ($onlyWithAccLines !== FALSE) {
			$productIds = is_array($onlyWithAccLines) && count($onlyWithAccLines) ? $onlyWithAccLines : [];
			$ids = $this->productRepo->getAccessoriesLinesIds($productIds);
			$criteria['id IN'] = isset($criteria['id IN']) ? array_diff($criteria['id IN'], $ids) : $ids;
		}

		$lines = [];
		foreach ($this->lineRepo->findBy($criteria, ['priority' => 'ASC']) as $line) {
			$isIn = TRUE;
			if ($isIn && $onlyWithChildren) {
				$isIn = array_key_exists($line->id, $models);
			}
			if ($isIn && $onlyWithProducts) {
				$isIn = in_array($line->producer->id, $producersIdsInProducts);
			}
			if ($isIn) {
				$lines[$line->id] = $fullPath ? $line->getFullName() : (string)$line;
			}
		}
		return $lines;
	}

	public function getModelsList(ProducerLine $line = NULL, $fullPath = FALSE, $onlyWithProducts = FALSE, $onlyWithAccModels = FALSE)
	{
		$criteria = [];
		if ($line) {
			$criteria['line'] = $line;
		}
		if ($onlyWithProducts) {
			$criteria['line.producer'] = $this->productRepo->getProducersIds();
		}
		if ($onlyWithAccModels !== FALSE) {
			$productIds = is_array($onlyWithAccModels) && count($onlyWithAccModels) ? $onlyWithAccModels : [];
			$ids = $this->productRepo->getAccessoriesModelsIds($productIds);
			$criteria['id IN'] = isset($criteria['id IN']) ? array_diff($criteria['id IN'], $ids) : $ids;
		}

		$models = [];
		foreach ($this->modelRepo->findBy($criteria, ['priority' => 'ASC']) as $model) {
			$models[$model->id] = $fullPath ? $model->getFullName() : (string)$model;
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
