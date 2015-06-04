<?php

namespace App\FrontModule\Presenters;

use App\Extensions\Products\ProductList;

class CategoryPresenter extends BasePresenter
{

	public function actionDefault($url)
	{
		$category = $this->categoryRepo->findOneByUrl($url);
		$category->setCurrentLocale($this->lang);
		if ($category->url !== $url) {
			$this->redirect('Category:', ['url' => $category->url]);
		}
		$this->activeCategory = $category;
		$this->template->category = $category;
		
		/* @var $products ProductList */
		$products = $this['products'];
		$products->sort = [
			'name' => ProductList::ORDER_ASC,
		];
		$products->filter = [
			'category' => $category->childrenArray,
		];
	}

}
