<?php

namespace App\CronModule\Presenters;

use App\Model\Facade\NewsletterFacade;

class NewsletterPresenter extends BasePresenter
{

	const DEFAULT_QUANTITY = 50;
	const LOGNAME = 'pohoda_cron';

	/** @var NewsletterFacade @inject */
	public $newsletterFacade;

	public function actionSent($quantity = self::DEFAULT_QUANTITY)
	{
		$this->newsletterFacade->send($quantity);
	}

}
