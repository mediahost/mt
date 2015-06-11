<?php

namespace App\FrontModule\Presenters;

class HomepagePresenter extends BasePresenter
{

	public function actionDefault()
	{
//		$this->showSlider = TRUE;
		$this->showBrands = TRUE;
//		$this->showSidebanners = TRUE;
//		$this->showSteps = FALSE;
	}

}
