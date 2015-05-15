<?php

namespace App\FrontModule\Presenters;

class MyAccountPresenter extends BasePresenter
{

	/**
	 * @secured
	 * @resource('myAccount')
	 * @privilege('default')
	 */
	public function actionDefault()
	{

	}

}
