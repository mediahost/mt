<?php

namespace App\AjaxModule\Presenters;

use App\Model\Entity\Product;

class ProductsPresenter extends BasePresenter
{

	public function actionFindByName($lang, $text, $page = 1, $perPage = 10)
	{
		$offset = ($page - 1) * $perPage;
		$limit = $offset + $perPage;

		$productRepo = $this->em->getRepository(Product::getClassName());
		$products = $productRepo->findByName($text, [$lang, $this->languageService->defaultLanguage], $limit, $offset, $totalCount);

		$this->addRawData('total_count', $totalCount ? $totalCount : count($products));

		$items = [];
		foreach ($products as $product) {
			/* @var $product Product */
			$product->setCurrentLocale($lang);
			$item = [];
			$item['id'] = $product->id;
			$item['text'] = (string) $product;
			$item['description'] = $product->description;
			$item['perex'] = $product->perex;
			$item['url'] = $this->link('//:Front:Product:', ['url' => $product->url]);
			$item['image_original'] = $this->link('//:Foto:Foto:', ['name' => $product->image]);
			$item['image_thumbnail_100'] = $this->link('//:Foto:Foto:', ['size' => '100-0', 'name' => $product->image]);
			$items[] = $item;
		}
		$this->addRawData('items', $items);
	}

}
