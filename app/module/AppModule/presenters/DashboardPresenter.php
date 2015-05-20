<?php

namespace App\AppModule\Presenters;

class DashboardPresenter extends BasePresenter
{

	/**
	 * @secured
	 * @resource('dashboard')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		
	}

}
