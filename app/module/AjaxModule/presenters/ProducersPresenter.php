<?php

namespace App\AjaxModule\Presenters;

use App\Model\Entity\Producer;
use App\Model\Entity\ProducerLine;
use App\Model\Entity\ProducerModel;
use Doctrine\ORM\ORMException;
use Kdyby\Doctrine\DBALException;

class ProducersPresenter extends BasePresenter
{

	public function actionGetProducers($parent)
	{
		$parentId = Producer::getItemId($parent, $parentType);

		switch ($parentType) {
			case ProducerLine::ID:
				$modelRepo = $this->em->getRepository(ProducerModel::getClassName());
				$items = $modelRepo->findBy(['line' => $parentId]);
				break;
			case Producer::ID:
				$lineRepo = $this->em->getRepository(ProducerLine::getClassName());
				$items = $lineRepo->findBy(['producer' => $parentId]);
				break;
			default:
				$producerRepo = $this->em->getRepository(Producer::getClassName());
				$items = $producerRepo->findAll();
		}

		if (count($items)) {
			foreach ($items as $item) {
				if ($item instanceof Producer) {
					$id = Producer::ID . Producer::SEPARATOR . $item->id;
					$name = $item->name;
					$hasChildren = $item->hasLines;
					$type = 'producer';
				} else if ($item instanceof ProducerLine) {
					$id = ProducerLine::ID . Producer::SEPARATOR . $item->id;
					$name = $item->name;
					$hasChildren = $item->hasModels;
					$type = 'line';
				} else if ($item instanceof ProducerModel) {
					$id = ProducerModel::ID . Producer::SEPARATOR . $item->id;
					$name = $item->name;
					$hasChildren = FALSE;
					$type = 'model';
				} else {
					continue;
				}
				$item = [];
				$item['id'] = $id;
				$item['text'] = $name;
				$item['children'] = $hasChildren;
				$item['type'] = $type;
				$this->addRawData(NULL, $item);
			}
		} else {
			$message = $this->translator->translate('There are no child for this selection.');
			$this->setError($message);
		}
	}

	public function actionGetLinesList($producer)
	{
		$producerRepo = $this->em->getRepository(Producer::getClassName());
		$findedProducer = $producerRepo->find($producer);

		if ($findedProducer) {
			$lines = $this->producerFacade->getLinesList($findedProducer);
			$this->addRawData('items', $lines);
		} else {
			$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('Producer')]);
			$this->setError($message);
		}
	}

	public function actionGetModelsList($line)
	{
		$lineRepo = $this->em->getRepository(ProducerLine::getClassName());
		$findedLine = $lineRepo->find($line);

		if ($findedLine) {
			$models = $this->producerFacade->getModelsList($findedLine);
			$this->addRawData('items', $models);
		} else {
			$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('Producer')]);
			$this->setError($message);
		}
	}

	/**
	 * @secured
	 * @resource('producers')
	 * @privilege('create')
	 */
	public function actionCreateProducer($name, $parent)
	{
		if (empty($name)) {
			$message = $this->translator->translate('cantBeEmpty', NULL, ['name' => $this->translator->translate('Name')]);
			$this->setError($message);
			return;
		}

		$parentId = Producer::getItemId($parent, $parentType);
		$createdId = NULL;
		switch ($parentType) {
			case Producer::ID:
				$createdId = $this->createLine($name, $parentId);
				$type = ProducerLine::ID;
				break;
			case ProducerLine::ID:
				$createdId = $this->createModel($name, $parentId);
				$type = ProducerModel::ID;
				break;
			default:
				return;
		}

		if (empty($createdId)) {
			$message = $this->translator->translate('Error while saving producer.');
			$this->setError($message);
			return;
		}

		$newId = $type . Producer::SEPARATOR . $createdId;
		$this->addData('id', $newId);
	}

	private function createLine($name, $producerId)
	{
		$producerRepo = $this->em->getRepository(Producer::getClassName());

		if ($producerId) {
			$producer = $producerRepo->find($producerId);
			if ($producer) {
				$line = new ProducerLine($name);
				$producer->addLine($line);
				$producerRepo->save($producer);
				return $line->id;
			}
		}
	}

	private function createModel($name, $lineId)
	{
		$lineRepo = $this->em->getRepository(ProducerLine::getClassName());

		if ($lineId) {
			$line = $lineRepo->find($lineId);
			if ($line) {
				$model = new ProducerModel($name);
				$line->addModel($model);
				$lineRepo->save($line);
				return $model->id;
			}
		}
	}

	/**
	 * @secured
	 * @resource('producers')
	 * @privilege('rename')
	 */
	public function actionRenameProducer($id, $name)
	{
		if (empty($name)) {
			$message = $this->translator->translate('cantBeEmpty', NULL, ['name' => $this->translator->translate('Name')]);
			$this->setError($message);
			return;
		}

		$itemId = Producer::getItemId($id, $type);
		switch ($type) {
			case Producer::ID:
				$repo = $this->em->getRepository(Producer::getClassName());
				break;
			case ProducerLine::ID:
				$repo = $this->em->getRepository(ProducerLine::getClassName());
				break;
			case ProducerModel::ID:
				$repo = $this->em->getRepository(ProducerModel::getClassName());
				break;
		}

		try {
			$entity = $repo->find($itemId);
			$entity->name = $name;
			$repo->save($entity);

			$this->addData('name', $entity->name);
		} catch (ORMException $e) {
			$message = $this->translator->translate('cantBeEmpty', NULL, ['name' => $this->translator->translate('ID')]);
			$this->setError($message);
		}
	}

	/**
	 * @secured
	 * @resource('producers')
	 * @privilege('delete')
	 */
	public function actionDeleteProducer($id)
	{
		$itemId = Producer::getItemId($id, $type);
		switch ($type) {
			case Producer::ID:
				$repo = $this->em->getRepository(Producer::getClassName());
				break;
			case ProducerLine::ID:
				$repo = $this->em->getRepository(ProducerLine::getClassName());
				break;
			case ProducerModel::ID:
				$repo = $this->em->getRepository(ProducerModel::getClassName());
				break;
			default:
				$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('Entity type')]);
				$this->setError($message);
				return;
		}

		try {
			$entity = $repo->find($itemId);
			if (!$entity) {
				$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('Entity')]);
				$this->setError($message);
				return;
			}
			$repo->delete($entity);
			$this->addData('id', $entity->id);
		} catch (ORMException $e) {
			$message = $this->translator->translate('cantBeEmpty', NULL, ['name' => $this->translator->translate('ID')]);
			$this->setError($message);
		} catch (DBALException $e) {
			$message = $this->translator->translate('cantBeEmpty', NULL, ['name' => $this->translator->translate('Entity')]);
			$this->setError($message);
		}
	}

}
