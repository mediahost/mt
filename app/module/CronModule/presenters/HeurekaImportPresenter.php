<?php

namespace App\CronModule\Presenters;

use App\Extensions\Settings\SettingsStorage;
use App\Model\Facade\HeurekaFacade;
use Tracy\Debugger;

class HeurekaImportPresenter extends BasePresenter
{

	/** @var SettingsStorage @inject */
	public $settings;

	/** @var HeurekaFacade @inject */
	public $heurekaFacade;

	public function actionCategories()
	{
		$settings = $this->settings->modules->heureka;

		if (!$settings->enabled) {
			$this->message = 'This module is not allowed';
		}

		if (array_key_exists($this->locale, $settings->categoryImport->url)) {
			$url = $settings->categoryImport->url[$this->locale];
			$this->heurekaFacade->downloadCategories($url, $this->locale, (array)$settings->categoryImport->allowedIds);
			$this->status = parent::STATUS_OK;
		} else {
			$this->message = 'This language is not allowed';
		}
	}

}
