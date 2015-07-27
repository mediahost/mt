<?php

namespace App\FrontModule\Presenters;

use App\Extensions\Products\ProductList;
use App\Model\Entity\Stock;
use Nette\Application\Responses\JsonResponse;
use Nette\Utils\Strings;

class ProductPresenter extends BasePresenter
{

	public function actionDefault($url)
	{
		$product = $this->productRepo->findOneByUrl($url);
		
		if (!$product) {
			$message = $this->translator->translate('Requested product doesn\'t exist. Try to choose another from list.');
			$this->flashMessage($message, 'warning');
			$this->redirect('Homepage:');
		}
		
		$product->setCurrentLocale($this->locale);
		
		if ($product->url !== $url) {
			$this->redirect('Product:', ['url' => $product->url]);
		}
		
		$this->activeCategory = $product->mainCategory;
		$this->template->product = $product;
		$this->template->stock = $product->stock;
		
		// Last visited
		$this->user->storage->addVisited($product->stock);
	}

	public function actionViewById($id)
	{
		$product = $this->productRepo->find($id);
		if (!$product) {
			$message = $this->translator->translate('Requested product doesn\'t exist. Try to choose another from list.');
			$this->flashMessage($message, 'warning');
			$this->redirect('Homepage:');
		}
		$product->setCurrentLocale($this->locale);
		$this->redirect('Product:', ['url' => $product->url]);
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
			$item['id'] = $product->id;
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

}
