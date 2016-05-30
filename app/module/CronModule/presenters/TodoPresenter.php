<?php

namespace App\CronModule\Presenters;

use App\Extensions\TodoQueue;

class TodoPresenter extends BasePresenter
{

	/** @var TodoQueue @inject */
	public $todoQueue;

	public function actionRun()
	{
		$this->todoQueue->run();

		$this->status = parent::STATUS_OK;
	}

}
