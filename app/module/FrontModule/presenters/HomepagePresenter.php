<?php

namespace App\FrontModule\Presenters;

class HomepagePresenter extends BasePresenter
{

	public function actionDefault()
	{
		$this->activeCategory = $this->categoryRepo->find(10);
	}

	public function renderTest1()
	{
		$this->template->backlink = $this->storeRequest();
	}

	public function renderTest2()
	{
		$this->template->backlink = $this->storeRequest();
	}

}
