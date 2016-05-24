<?php

namespace App\CronModule\Presenters;

use App\Extensions\TodoQueue;

class CleanerPresenter extends BasePresenter
{

	/** @var TodoQueue @inject */
	public $todoQueue;

	public function actionCleanOldEmptyBaskets()
	{
		$this->basketFacade->removeOldEmptyBaskets();

		$this->status = parent::STATUS_OK;
	}

	public function actionCleanOldBaskets()
	{
		$this->basketFacade->removeOldBaskets();

		$this->status = parent::STATUS_OK;
	}

	public function actionCache()
	{
		$this->todoQueue->run();

		$this->status = parent::STATUS_OK;
	}

}
