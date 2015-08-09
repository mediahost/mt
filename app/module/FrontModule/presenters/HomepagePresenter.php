<?php

namespace App\FrontModule\Presenters;

use App\Model\Facade\PohodaFacade;

class HomepagePresenter extends BasePresenter
{
	
	/** @var PohodaFacade @inject */
	public $pohodaFacade;

	public function actionDefault()
	{
		$this->showSlider = TRUE;
		$this->showBrands = TRUE;
//		$this->showSteps = FALSE;
	}

	public function renderDefault()
	{
		
	}

}
