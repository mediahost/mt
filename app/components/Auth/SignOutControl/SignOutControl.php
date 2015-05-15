<?php

namespace App\Components\Auth;

use App\Components\BaseControl;

class SignOutControl extends BaseControl
{

	const REDIRECT_AFTER_LOGOUT = ':Front:Homepage:';

	public function handleSignOut()
	{
		$this->presenter->user->logout();
		$this->presenter->flashMessage('You have been successfuly signed out.', 'success');
		$this->presenter->redirect(self::REDIRECT_AFTER_LOGOUT);
	}

}

interface ISignOutControlFactory
{

	/** @return SignOutControl */
	function create();
}
