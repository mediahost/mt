<?php

namespace App\FrontModule\Presenters;

class HomepagePresenter extends BasePresenter
{

	public function actionDefault()
	{
	}

	public function renderDefault()
	{
		$this->template->newStocks = $this->stockFacade->getNews();
		$this->template->saleStocks = $this->stockFacade->getSales();
		$this->template->topStocks = $this->stockFacade->getTops();
//		$this->template->bestsellerStocks = $this->stockFacade->getBestSellers();

		$titleText = $this->translator->translate('shopTitle');
		$this->changePageInfo(self::PAGE_INFO_TITLE, $titleText);
	}

}
