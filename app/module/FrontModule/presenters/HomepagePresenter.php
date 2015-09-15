<?php

namespace App\FrontModule\Presenters;

class HomepagePresenter extends BasePresenter
{

	public function actionDefault($slider = TRUE, $brands = TRUE)
	{
		$this->showSlider = (bool) $slider;
		$this->showBrands = (bool) $brands;
//		$this->showSteps = FALSE;
	}

	public function renderDefault()
	{
		
	}

}
