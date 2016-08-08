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

	/** @var bool */
	private $showFilter = TRUE;

	/** @var bool */
	private $showAccessoriesFilter = TRUE;

	public function actionDefault($c, $slug = NULL)
	{
		if ($c) {
			$this->category = $this->categoryRepo->find($c);
		}
		if (!$this->category) {
			$message = $this->translator->translate('Requested category doesn\'t exist. Try to choose another from list.');
			$this->flashMessage($message, 'warning');
			$this->redirect('Homepage:');
		}

		$this->category->setCurrentLocale($this->locale);

		if ($slug !== $this->category->getUrl()) {
			$this->redirect('this', ['slug' => $this->category->getUrl()]);
		}

		$this['products']->addFilterCategory($this->category);

		$this->setActiveCategory($this->category);
		$this->subcategories = $this->category->children;
	}

	public function renderDefault()
	{
		$title = NULL;
		$keywords = $description = [];
		if ($this->category) {
			$this->template->category = $this->category;
			$this->template->subcategories = $this->subcategories;
			$title = $this->category->getTreeName(' | ', TRUE);
			$keywords = $this->category->getTreeName(', ', TRUE);
			$description = $this->category->getTreeName(' - ');
		}
		if ($this->searched) {
			$this->template->searched = $this->searched;
			$title = $keywords = $description = $this->searched;
		}
		$this->template->showFilter = $this->showFilter;
		$this->template->showAccessoriesFilter = $this->showAccessoriesFilter;

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
			$this['products']->addFilterFulltext($text);
			$this->showFilter = FALSE;
			$this->showAccessoriesFilter = FALSE;

			$this->setView('default');
		} else {
			$this->redirect('Homepage:');
		}
	}

	public function actionSearchJson($text, $page = 1, $perPage = 10)
	{
		/* @var $list ProductList */
		$list = $this['products']
			->setPage($page, $perPage)
			->addFilterFulltext($text)
			->setSorting(ProductList::SORT_BY_NAME_ASC);

		$stocks = $list->getData();
		$items = [];
		foreach ($stocks as $stock) {
			/* @var $stock Stock */
			$product = $stock->product;
			$price = $stock->getPrice($this->priceLevel);
			$item = [];
			$item['id'] = $stock->id;
			$item['text'] = (string)$product;
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
			'more' => $this->link('//search', ['text' => $text]),
		];
		$this->sendJson($data);
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
			$filterProducer = $producer;
			if ($line) {
				$filterProducer = $line;
			}
			if ($model) {
				$filterProducer = $model;
			}
			$this['products']->addFilterProducer($filterProducer);

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

	public function actionAppropriate($producer)
	{
		$producerRepo = $this->em->getRepository(Producer::getClassName());
		$producer = $producerRepo->findOneBySlug($producer);
		if ($producer) {
			$this['products']->setProducer($producer);

			$this->template->producer = $producer;
			$this->setView('default');
		} else {
			$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('Producer')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('Homepage:');
		}
	}

}
