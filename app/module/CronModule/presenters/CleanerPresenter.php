<?php

namespace App\CronModule\Presenters;

class CleanerPresenter extends BasePresenter
{

	public function actionCleanOldEmptyBaskets()
	{
		$this->basketFacade->clearOldEmptyBaskets();

		$this->status = parent::STATUS_OK;
	}

}
