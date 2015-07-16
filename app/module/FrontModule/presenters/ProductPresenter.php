<?php

namespace App\FrontModule\Presenters;

class ProductPresenter extends BasePresenter
{

	public function actionDefault($url)
	{
		$product = $this->productRepo->findOneByUrl($url);
		if (!$product) {
			$message = $this->translator->translate('Requested product isn\'t exist. Try to choose another from list.');
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
	}

	public function actionViewById($id)
	{
		$product = $this->productRepo->find($id);
		if (!$product) {
			$message = $this->translator->translate('Requested product isn\'t exist. Try to choose another from list.');
			$this->flashMessage($message, 'warning');
			$this->redirect('Homepage:');
		}
		$product->setCurrentLocale($this->locale);
		$this->redirect('Product:', ['url' => $product->url]);
	}

}
