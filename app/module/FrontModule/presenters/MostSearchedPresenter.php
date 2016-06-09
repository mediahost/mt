<?php

namespace App\FrontModule\Presenters;

use App\Model\Facade\SearchedFacade;

class MostSearchedPresenter extends BasePresenter
{
	/** @var SearchedFacade @inject */
	public $searchedFacade;

	public function actionDefault()
	{
		$this->getTemplate()->products = $this->searchedFacade->getMostSearchedWithProducts(10);
		$this->getTemplate()->terms = $this->searchedFacade->getMostSearched(10);
	}

}
