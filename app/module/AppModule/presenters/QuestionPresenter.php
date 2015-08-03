<?php

namespace App\AppModule\Presenters;

class QuestionPresenter extends BasePresenter
{

	/**
	 * @secured
	 * @resource('question')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		
	}

	/**
	 * @secured
	 * @resource('question')
	 * @privilege('add')
	 */
	public function actionAdd()
	{
		
	}

	/**
	 * @secured
	 * @resource('question')
	 * @privilege('edit')
	 */
	public function actionEdit($id)
	{
		
	}

	/**
	 * @secured
	 * @resource('question')
	 * @privilege('delete')
	 */
	public function actionDelete($id)
	{
		
	}

}
