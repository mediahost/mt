<?php

namespace App\FrontModule\Presenters;

class MyOrdersPresenter extends BasePresenter
{

	/**
	 * @secured
	 * @resource('myOrders')
	 * @privilege('default')
	 */
	public function actionDefault()
	{

	}

}
