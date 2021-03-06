<?php

namespace App\FrontModule\Presenters;

use App\Extensions\Products\ProductList;
use App\Model\Entity\Category;
use App\Model\Entity\Producer;
use App\Model\Entity\ProducerLine;
use App\Model\Entity\ProducerModel;
use App\Model\Entity\Searched;
use App\Model\Entity\Stock;
use Nette\Utils\Strings;

class CategoryPresenter extends ProductCategoryBasePresenter
{
	
	/** @var Category */
	private $category;
	
	/** @var array */
	private $subcategories = [];

	public function actionDefault($c, $slug = NULL)
	{
		$this->category = $this->categoryRepo->find($c);
		if (!$this->category) {
			$message = $this->translator->translate('Requested category doesn\'t exist. Try to choose another from list.');
			$this->flashMessage($message, 'warning');
			$this->redirect('Homepage:');
		}

		$this->category->setCurrentLocale($this->locale);

		if ($slug !== $this->category->getUrl()) {
			$this->redirect('this', ['slug' => $this->category->getUrl()]);
		}

		$this->setActiveCategory($this->category);
		$this->subcategories = $this->category->children;
	}
	
	public function renderDefault()
	{
		/* @var $products ProductList */
		$products = $this['products'];
		
		$title = NULL;
		$keywords = $description = [];
		if ($this->category) {
			$products->addFilterCategory($this->category);
			$this->template->category = $this->category;
			$this->template->subcategories = $this->subcategories;
			$title = $this->category->getTreeName(' | ', TRUE);
			$keywords = $this->category->getTreeName(', ', TRUE);
			$description = $this->category->getTreeName(' - ');
		}
		if ($this->searched) {
			$products->addFilterFulltext($this->searched);
			$this->template->searched = $this->searched;
			$title = $keywords = $description = $this->searched;
		}
		
		$this->changePageInfo(self::PAGE_INFO_TITLE, $title);
		$this->changePageInfo(self::PAGE_INFO_KEYWORDS, $keywords);
		$this->changePageInfo(self::PAGE_INFO_DESCRIPTION, $description);
	}

	public function actionSearch($text)
	{
		if ($text) {
			$searchedRepo = $this->em->getRepository(Searched::getClassName());
			$searched = new Searched();
			$searched->text = $text;
			$searched->ip = $this->getHttpRequest()->getRemoteAddress();
			$searchedRepo->save($searched);

			$this->searched = $text;
			$this->setView('default');
		} else {
			$this->redirect('Homepage:');
		}
	}

	public function actionSearchJson($text, $page = 1, $perPage = 10)
	{
		/* @var $list ProductList */
		$list = $this['products'];
		$list->setPage($page);
		$list->setItemsPerPage($perPage);
		$list->addFilterFulltext($text);
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
			$item['url'] = $this->link('//:Front:Product:', ['id' => $stock->getUrlId(), 'slug' => $stock->getSlug(), 'searched' => $text]);
			$item['image_original'] = $this->link('//:Foto:Foto:', ['name' => $product->image]);
			$item['image_thumbnail_100'] = $this->link('//:Foto:Foto:', ['size' => '100-0', 'name' => $product->image]);
			$items[] = $item;
		}
		$data = [
			'items' => $items,
			'total_count' => $list->getCount(),
		];
		$this->sendJson($data);
	}

	public function actionAccessories($id)
	{
		$modelRepo = $this->em->getRepository(ProducerModel::getClassName());
		if ($id) {
			$modelEntity = $modelRepo->find($id);
		}
		if (isset($modelEntity) && $modelEntity) {
			/* @var $products ProductList */
			$products = $this['products'];
			$products->addFilterAccessoriesFor($modelEntity);

			$this['modelSelector']->setAccessories();
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
			if ($model) {
				$line = $model->line;
				$producer = $line->producer;
			} else {
				$producer = NULL;
			}
		} else if ($line) {
			$lineRepo = $this->em->getRepository(ProducerLine::getClassName());
			$line = $lineRepo->findOneBySlug($line);
			if ($line) {
				$producer = $line->producer;
			} else {
				$producer = NULL;
			}
		} else if ($producer) {
			$producerRepo = $this->em->getRepository(Producer::getClassName());
			$producer = $producerRepo->findOneBySlug($producer);
		}

		if ($producer) {
			/* @var $products ProductList */
			$products = $this['products'];
			$products->addFilterProducer($producer);
			if ($line) {
				$products->addFilterLine($line);
			}
			if ($model) {
				$products->addFilterModel($model);
			}

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
