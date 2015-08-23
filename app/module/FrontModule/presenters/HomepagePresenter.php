<?php

namespace App\FrontModule\Presenters;

use App\Model\Facade\PohodaFacade;

class HomepagePresenter extends BasePresenter
{
	
	/** @var PohodaFacade @inject */
	public $pohodaFacade;

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
