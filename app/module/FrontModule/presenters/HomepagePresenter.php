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
		$signSettings = $this->moduleService->getModuleSettings('signs');
		if ($signSettings) {
			$signRepo = $this->em->getRepository(Sign::getClassName());
			$newSign = $signRepo->find($signSettings->new);
			$newSign->setCurrentLocale($this->lang);
			$saleSign = $signRepo->find($signSettings->sale);
			$saleSign->setCurrentLocale($this->lang);

			$this->template->newSign = $newSign;
			$this->template->saleSign = $saleSign;
		}
	}

}
