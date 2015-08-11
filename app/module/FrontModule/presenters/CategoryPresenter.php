<?php

namespace App\FrontModule\Presenters;

use App\Extensions\Products\ProductList;
use App\Model\Entity\Producer;
use App\Model\Entity\ProducerLine;
use App\Model\Entity\ProducerModel;
use App\Model\Entity\Stock;
use Nette\Application\Responses\JsonResponse;
use Nette\Utils\Strings;

class CategoryPresenter extends BasePresenter
{

	public function actionDefault($id)
	{
		$category = $this->categoryRepo->find($id);
		if (!$category) {
			$message = $this->translator->translate('Requested category doesn\'t exist. Try to choose another from list.');
			$this->flashMessage($message, 'warning');
			$this->redirect('Homepage:');
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

	public function actionSearchJson($text, $page = 1, $perPage = 10)
	{
		/* @var $list ProductList */
		$list = $this['products'];
		$list->setPage($page);
		$list->setItemsPerPage($perPage);
		$list->filter = [
			'fulltext' => $text,
		];
		$list->sorting = [
			'name' => ProductList::ORDER_ASC,
			'price' => ProductList::ORDER_DESC,
		];

		$stocks = $list->getData(TRUE, FALSE);
		$items = [];
		foreach ($stocks as $stock) {
			/* @var $stock Stock */
			$product = $stock->product;
			$price = $stock->getPrice($this->priceLevel);
			$item = [];
			$item['id'] = $stock->id;
			$item['text'] = (string) $product;
			$item['shortText'] = Strings::truncate($item['text'], 30);
			$item['description'] = $product->description;
			$item['perex'] = $product->perex;
			$item['priceNoVat'] = $price->withoutVat;
			$item['priceNoVatFormated'] = $this->exchange->format($price->withoutVat);
			$item['priceWithVat'] = $price->withVat;
			$item['priceWithVatFormated'] = $this->exchange->format($price->withVat);
			$item['url'] = $this->link('//:Front:Product:', ['url' => $product->url]);
			$item['image_original'] = $this->link('//:Foto:Foto:', ['name' => $product->image]);
			$item['image_thumbnail_100'] = $this->link('//:Foto:Foto:', ['size' => '100-0', 'name' => $product->image]);
			$items[] = $item;
		}
		$payload = [
			'items' => $items,
			'total_count' => $list->getCount(),
		];
		$response = new JsonResponse($payload, 'application/json; charset=utf-8');
		$this->sendResponse($response);
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
