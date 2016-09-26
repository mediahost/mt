<?php

namespace App\FrontModule\Presenters;

class HomepagePresenter extends BasePresenter
{

	public function actionDefault()
	{
	}

	public function renderDefault()
	{
		$this->stockComponentClasses = [];
		$this->stockComponentLabels = TRUE;
		$this->stockComponentSecondImage = FALSE;

		$this->template->newStocks = $this->stockFacade->getNews(3);
		$this->template->saleStocks = $this->stockFacade->getSales(3);
		$this->template->topStocks = $this->stockFacade->getTops(3);
		$this->template->lastStocks = $this->user->getStorage()->getVisited(3);

		$titleText = $this->translator->translate('shopTitle');
		$this->changePageInfo(self::PAGE_INFO_TITLE, $titleText);
	}

}
