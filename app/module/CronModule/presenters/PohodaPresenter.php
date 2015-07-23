<?php

namespace App\CronModule\Presenters;

class PohodaPresenter extends BasePresenter
{

	public function actionSynchronize()
	{
		$this->status = parent::STATUS_OK;
		$this->message = 'Everything is OK';
	}

}
