<?php

namespace App\FrontModule\Presenters;

use App\Model\Entity\Category;
use Tracy\Debugger;

class HomepagePresenter extends BasePresenter
{

	public function actionDefault()
	{
	}

	public function renderDefault()
	{
		$timeName = 'renderDefault';
		Debugger::timer($timeName);

		$this->stockComponentClasses = [];
		$this->stockComponentLabels = TRUE;
		$this->stockComponentSecondImage = FALSE;

		$this->template->newStocks = $this->stockFacade->getNews(20);
		$this->template->saleStocks = $this->stockFacade->getSales(3);
		$this->template->topStocks = $this->stockFacade->getTops(3);
		$this->template->specialStocks = $this->stockFacade->getSpecials(3);
		$this->template->interestStocks = $this->stockFacade->getRandom(3);
		$this->template->lastStocks = $this->user->getStorage()->getVisited(3);

		$categoryRepo = $this->em->getRepository(Category::getClassName());
		$this->template->cat1 = $categoryRepo->find(1);
		$this->template->cat2 = $categoryRepo->find(177);
		$this->template->cat3 = $categoryRepo->find(74);

		$titleText = $this->translator->translate('shopTitle');
		$this->changePageInfo(self::PAGE_INFO_TITLE, $titleText);

		Debugger::barDump(Debugger::timer($timeName), $timeName . ' time');
	}

}
