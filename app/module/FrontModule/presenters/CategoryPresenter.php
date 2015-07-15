<?php

namespace App\FrontModule\Presenters;

use App\Extensions\Products\ProductList;
use App\Model\Entity\ProducerModel;

class CategoryPresenter extends BasePresenter
{

	public function actionDefault($url)
	{
		$category = $this->categoryRepo->findOneByUrl($url);
		if (!$category) {
			$this->flashMessage('Requested category isn\'t exist. Try to choose another from list.', 'warning');
			$this->redirect('Homepage:');
		}
		$category->setCurrentLocale($this->lang);
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
			$this->flashMessage('This model wasn\'t found.', 'warning');
			$this->redirect('Homepage:');
		}
	}

}
