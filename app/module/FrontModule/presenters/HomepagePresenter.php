<?php

namespace App\FrontModule\Presenters;

use App\Model\Entity\Sign;

class HomepagePresenter extends BasePresenter
{

	public function actionDefault()
	{
		$this->showSlider = TRUE;
		$this->showBrands = TRUE;
//		$this->showSteps = FALSE;
	}
	
	public function renderDefault()
	{
		$signRepo = $this->em->getRepository(Sign::getClassName());
		
		$signSettiings = $this->moduleService->getModuleSettings('signs');
		$newSign = $signRepo->find($signSettiings->new);
		$newSign->setCurrentLocale($this->lang);
		$saleSign = $signRepo->find($signSettiings->sale);
		$saleSign->setCurrentLocale($this->lang);
		
		$this->template->newSign = $newSign;
		$this->template->saleSign = $saleSign;
	}

}
