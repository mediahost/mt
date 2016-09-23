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
		$this->template->saleStocks = $this->stockFacade->getSales(25);
		$this->template->topStocks = $this->stockFacade->getTops(25);
		$this->template->lastStocks = $this->user->getStorage()->getVisited(25);

		$titleText = $this->translator->translate('shopTitle');
		$this->changePageInfo(self::PAGE_INFO_TITLE, $titleText);
	}

}
