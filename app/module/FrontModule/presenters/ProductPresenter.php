<?php

namespace App\FrontModule\Presenters;

class ProductPresenter extends BasePresenter
{

	public function actionDefault($url)
	{
		$product = $this->productRepo->findOneByUrl($url);
		if (!$product) {
			$this->flashMessage('Requested product isn\'t exist. Try to choose another from list.', 'warning');
			$this->redirect('Homepage:');
		}
		$product->setCurrentLocale($this->lang);
		if ($product->url !== $url) {
			$this->redirect('Product:', ['url' => $product->url]);
		}
		$this->activeCategory = $product->mainCategory;
		$this->template->product = $product;
	}

}
