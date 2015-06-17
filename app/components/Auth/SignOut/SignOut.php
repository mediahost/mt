<?php

namespace App\Components\Auth;

use App\Components\BaseControl;

class SignOut extends BaseControl
{

	const REDIRECT_AFTER_LOGOUT = ':Front:Homepage:';

	public function handleSignOut()
	{
		$this->presenter->user->logout();
		$this->presenter->flashMessage('You have been successfuly signed out.', 'success');
		$this->presenter->redirect(self::REDIRECT_AFTER_LOGOUT);
	}

}

interface ISignOutFactory
{

	/** @return SignOut */
	function create();
}
