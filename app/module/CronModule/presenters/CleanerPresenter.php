<?php

namespace App\CronModule\Presenters;

class CleanerPresenter extends BasePresenter
{

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

}
