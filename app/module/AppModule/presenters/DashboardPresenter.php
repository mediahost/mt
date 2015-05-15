<?php

namespace App\AppModule\Presenters;

/**
 * Dashboard presenter
 */
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
