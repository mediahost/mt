<?php

namespace App\AjaxModule\Presenters;

use App\Model\Entity\Stock;
use Nette\Utils\Strings;

class StocksPresenter extends BasePresenter
{

	public function actionFindByFulltext($text, $page = 1, $perPage = 10)
	{
		$offset = ($page - 1) * $perPage;
		$limit = $offset + $perPage;

		$stockRepo = $this->em->getRepository(Stock::getClassName());
		$stocks = $stockRepo->findByName($text, [$this->locale, $this->languageService->defaultLanguage], $limit, $offset, $totalCount);

		$this->addRawData('total_count', $totalCount ? $totalCount : count($stocks));

		$items = [];
		foreach ($stocks as $stock) {
			/* @var $stock Stock */
			$product = $stock->product;
			$product->setCurrentLocale($this->locale);
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
		$this->addRawData('items', $items);
	}

}
