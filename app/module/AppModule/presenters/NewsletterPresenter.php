<?php

use App\AppModule\Presenters\BasePresenter;

namespace App\AppModule\Presenters;

class NewsletterPresenter extends BasePresenter
{

	/**
	 * @secured
	 * @resource('subscriber')
	 * @privilege('default')
	 */
	public function actionDefault()
	{

	}

	/**
	 * @secured
	 * @resource('subscriber')
	 * @privilege('add')
	 */
	public function actionAdd()
	{
		
	}

	/**
	 * @secured
	 * @resource('subscriber')
	 * @privilege('edit')
	 */
	public function actionEdit($id)
	{
		
	}

	/**
	 * @secured
	 * @resource('subscriber')
	 * @privilege('delete')
	 */
	public function actionDelete($id)
	{
		
	}

}
