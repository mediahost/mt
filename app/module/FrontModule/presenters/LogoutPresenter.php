<?php

namespace App\FrontModule\Presenters;

class LogoutPresenter extends BasePresenter
{

	const REDIRECT_NOT_LOGGED = ':Front:Sign:in';

	public function actionDefault()
	{
		$this->user->logout();
		$this->redirect(self::REDIRECT_NOT_LOGGED);
	}

}
