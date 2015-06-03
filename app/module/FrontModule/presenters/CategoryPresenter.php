<?php

namespace App\FrontModule\Presenters;

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
		$this->template->products = $this->productRepo->findAll();
		$this->template->itemsPerRow = $this->pageConfigService->getItemsPerRow();
	}

}
