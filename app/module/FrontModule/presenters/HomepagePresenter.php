<?php

namespace App\FrontModule\Presenters;

class HomepagePresenter extends BasePresenter
{

	const DEFAULT_SHOW_SLIDER = FALSE;
	const DEFAULT_SHOW_BRANDS = TRUE;

	public function actionDefault($slider = self::DEFAULT_SHOW_SLIDER, $brands = self::DEFAULT_SHOW_BRANDS)
	{
		$this->showSlider = (bool) $slider;
		$this->showBrands = (bool) $brands;
		$this['modelSelector']->setAccessories();
	}

	public function renderDefault()
	{
		$titleText = $this->translator->translate('shopTitle');
		$this->changePageInfo(self::PAGE_INFO_TITLE, $titleText);
	}

}
