<?php

namespace App\Model\Facade;

use App\Model\Entity\Producer;
use App\Model\Entity\ProducerLine;
use App\Model\Entity\ProducerModel;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Kdyby\Translation\Translator;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Object;
use Tracy\Debugger;

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

	/** @var EntityRepository */
	private $producerRepo;

	/** @var EntityRepository */
	private $lineRepo;

	/** @var EntityRepository */
	private $modelRepo;

	/** @var array */
	private $ids = [];

	/** @var array */
	private $urls = [];

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->producerRepo = $this->em->getRepository(Producer::getClassName());
		$this->lineRepo = $this->em->getRepository(ProducerLine::getClassName());
		$this->modelRepo = $this->em->getRepository(ProducerModel::getClassName());
	}

	public function getProducersList($onlyWithChildren = FALSE, $onlyWithProducts = FALSE)
	{
		$producers = [];
		foreach ($this->producerRepo->findBy([], ['priority' => 'ASC']) as $producer) {
			if ((!$onlyWithChildren || $producer->hasLines(TRUE)) && (!$onlyWithProducts || $producer->hasProducts())) {
				$producers[$producer->id] = (string)$producer;
			}
		}
		return $producers;
	}

	public function getLinesList(Producer $producer = NULL, $fullPath = FALSE, $onlyWithChildren = FALSE, $onlyWithProducts = FALSE)
	{
		$lines = [];
		if ($producer) {
			$finded = $this->lineRepo->findBy(['producer' => $producer], ['priority' => 'ASC']);
		} else {
			$finded = $this->lineRepo->findBy([], ['priority' => 'ASC']);
		}
		foreach ($finded as $line) {
			if ((!$onlyWithChildren || $line->hasModels()) && (!$onlyWithProducts || $line->hasProducts())) {
				$lines[$line->id] = $fullPath ? $line->getFullName() : (string)$line;
			}
		}
		return $lines;
	}

	public function getModelsList(ProducerLine $line = NULL, $fullPath = FALSE, $onlyWithProducts = FALSE)
	{
		$filter = [];
		if ($line) {
			$filter['line'] = $line;
		}

		$finded = $this->modelRepo->findBy($filter, ['priority' => 'ASC']);

		$models = [];
		foreach ($finded as $model) {
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

	public function urlToId($uri)
	{
		$hash = $this->createCacheHash($uri);

		if (isset($this->ids[$hash])) {
			return $this->ids[$hash];
		}

		$cache = $this->getCache();
		$id = $cache->load($hash);
		if (!$id) {
			$model = $this->modelRepo->findOneByUrl($uri);
			if ($model) {
				$this->ids[$hash] = $model->id;
				$cache->save($hash, $model->id, [Cache::TAGS => $this->getModelTags($model)]);
			}
		}
		return $id;
	}

	public function idToUrl($id)
	{
		$hash = $this->createCacheHash($id);

		if (isset($this->urls[$hash])) {
			return $this->urls[$hash];
		}

		$cache = $this->getCache();
		$url = $cache->load($hash);

		if (!$url) {
			$model = $this->modelRepo->find($id);
			if ($model) {
				$url = $model->getFullPath();
				$this->urls[$hash] = $url;
				$cache->save($hash, $url, [Cache::TAGS => $this->getModelTags($model)]);
			}
		}
		return $url;
	}

	/** @return Cache */
	public function getCache()
	{
		$cache = new Cache($this->cacheStorage, get_class($this));
		return $cache;
	}

	private function createCacheHash($value)
	{
		return md5(self::TAG_PRODUCER . $value);
	}

	private function getModelTags(ProducerModel $model)
	{
		return [
			self::TAG_ALL_PRODUCERS,
			self::TAG_PRODUCER . $model->line->producer->id,
			self::TAG_ALL_LINES,
			self::TAG_LINE . $model->line->id,
			self::TAG_ALL_MODELS,
			self::TAG_MODEL . $model->id,
		];
	}

}
