<?php

namespace App\FrontModule\Presenters;

use App\Extensions\Products\ProductList;
use App\Model\Entity\Producer;
use App\Model\Entity\ProducerLine;
use App\Model\Entity\ProducerModel;

class CategoryPresenter extends BasePresenter
{

	public function actionDefault($url)
	{
		$category = $this->categoryRepo->findOneByUrl($url);
		if (!$category) {
			$message = $this->translator->translate('Requested category isn\'t exist. Try to choose another from list.');
			$this->flashMessage($message, 'warning');
			$this->redirect('Homepage:');
		}
		$category->setCurrentLocale($this->locale);
		if ($category->url !== $url) {
			$this->redirect('this', ['url' => $category->url]);
		}
		$this->activeCategory = $category;
		$this->template->category = $category;

		/* @var $products ProductList */
		$products = $this['products'];
		$products->filter = [
			'category' => $category->childrenArray,
		];
	}

	public function actionSearch($text)
	{
		$this->searched = $text;

		/* @var $products ProductList */
		$products = $this['products'];
		$products->filter = [
			'fulltext' => $text,
		];

		$this->template->searched = $text;
		$this->setView('default');
	}

	public function actionAccessories($model)
	{
		$modelRepo = $this->em->getRepository(ProducerModel::getClassName());
		$modelEntity = $modelRepo->find($model);
		if ($modelEntity) {
			/* @var $products ProductList */
			$products = $this['products'];
			$products->filter = [
				'accessoriesFor' => $modelEntity,
			];

			$this['modelSelector']->setModel($modelEntity);
			$this->template->accessoriesFor = $modelEntity;
			$this->setView('default');
		} else {
			$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('Model')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('Homepage:');
		}
	}

	public function actionProducer($producer = NULL, $line = NULL, $model = NULL)
	{
		if ($model) {
			$modelRepo = $this->em->getRepository(ProducerModel::getClassName());
			$model = $modelRepo->findOneBySlug($model);
			$line = $model->line;
			$producer = $line->producer;
		} else if ($line) {
			$lineRepo = $this->em->getRepository(ProducerLine::getClassName());
			$line = $lineRepo->findOneBySlug($line);
			$model = $line->model;
		} else if ($producer) {
			$producerRepo = $this->em->getRepository(Producer::getClassName());
			$producer = $producerRepo->findOneBySlug($producer);
		}

		if ($producer) {
			/* @var $products ProductList */
			$products = $this['products'];
			$filters = [
				'producer' => $producer,
			];
			if ($line) {
				$filters['line'] = $line;
			}
			if ($model) {
				$filters['model'] = $model;
			}

			$products->filter = $filters;

			$this->template->producer = $producer;
			$this->template->line = $line;
			$this->template->model = $model;

			$this->setView('default');
		} else {
			$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('Producer')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('Homepage:');
		}
	}

}
