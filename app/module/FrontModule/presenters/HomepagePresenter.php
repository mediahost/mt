<?php

namespace App\FrontModule\Presenters;

class HomepagePresenter extends BasePresenter
{

	public function actionDefault()
	{
	}

	public function renderDefault()
	{
		$titleText = $this->translator->translate('shopTitle');
		$this->changePageInfo(self::PAGE_INFO_TITLE, $titleText);
	}

}
