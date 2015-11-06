<?php

namespace App\FrontModule\Presenters;

use App\Components\Basket\Form\AddToCart;
use App\Components\Basket\Form\IAddToCartFactory;
use App\Extensions\Products\ProductList;
use App\Model\Entity\Stock;
use Nette\Application\BadRequestException;
use Nette\Application\Responses\JsonResponse;
use Nette\Utils\Strings;

class ProductPresenter extends BasePresenter
{

	/** @var IAddToCartFactory @inject */
	public $iAddToCartFactory;

	/** @var Stock */
	public $stock;

	public function actionDefault($id)
	{
		if ($id) {
			$product = $this->productRepo->find($id);
		}
		if (!isset($product) || !$product) {
			$message = $this->translator->translate('Requested product doesn\'t exist. Try to choose another from list.');
			$this->flashMessage($message, 'warning');
			throw new BadRequestException;
		}

		$product->setCurrentLocale($this->locale);

		$this->stock = $product->stock;

		$this->activeCategory = $product->mainCategory;
		$this->template->product = $product;
		$this->template->stock = $this->stock;

		// Last visited
		$this->user->storage->addVisited($this->stock);
	}

	public function actionSearchJson($text, $getProductId = TRUE, $page = 1, $perPage = 10)
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
			$item['id'] = $getProductId ? $product->id : $stock->id;
			$item['text'] = (string) $product;
			$item['shortText'] = Strings::truncate($item['text'], 30);
			$item['description'] = $product->description;
			$item['perex'] = $product->perex;
			$item['inStore'] = $stock->inStore;
			$item['unit'] = $stock->product->unit ? (string) $stock->product->unit : '';
			$item['priceNoVat'] = $price->withoutVat;
			$item['priceNoVatFormated'] = $this->exchange->format($price->withoutVat);
			$item['priceWithVat'] = $price->withVat;
			$item['priceWithVatFormated'] = $this->exchange->format($price->withVat);
			$item['url'] = $this->link('//:Front:Product:', ['id' => $product->id]);
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

	// <editor-fold desc="forms">

	/** @return AddToCart */
	public function createComponentAddToCart()
	{
		$control = $this->iAddToCartFactory->create();
		$control->setStock($this->stock);
		$control->setAjax(TRUE);
		$control->onAfterAdd = function ($quantity) {
			if ($this->isAjax()) {
				$this->redrawControl();
			}
		};
		return $control;
	}

	// </editor-fold>
}
