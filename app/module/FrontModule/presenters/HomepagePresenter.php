<?php

namespace App\FrontModule\Presenters;

class HomepagePresenter extends BasePresenter
{

	public function actionDefault()
	{
		$this->showSlider = FALSE;
		$this->showBrands = TRUE;
//		$this->showSteps = FALSE;
	}

}
