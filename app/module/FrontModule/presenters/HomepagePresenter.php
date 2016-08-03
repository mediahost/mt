<?php

namespace App\FrontModule\Presenters;

class HomepagePresenter extends BasePresenter
{

	public function actionDefault()
	{
	}

	public function renderDefault()
	{
		$this->stockComponentClasses[] = 'product-sm owl-item-slide';
		$this->stockComponentLabels = FALSE;
		$this->stockComponentSecondImage = FALSE;
		$this->template->newStocks = $this->stockFacade->getNews(25);
//		$this->template->saleStocks = $this->stockFacade->getSales();
//		$this->template->topStocks = $this->stockFacade->getTops();
//		$this->template->bestsellerStocks = $this->stockFacade->getBestSellers();

		$titleText = $this->translator->translate('shopTitle');
		$this->changePageInfo(self::PAGE_INFO_TITLE, $titleText);
	}

}
