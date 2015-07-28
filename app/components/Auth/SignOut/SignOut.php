<?php

namespace App\Components\Auth;

use App\Components\BaseControl;

class SignOut extends BaseControl
{

	const REDIRECT_AFTER_LOGOUT = 'this';

	public function handleSignOut()
	{
		$this->presenter->user->logout();
		$message = $this->translator->translate('You have been successfuly signed out.');
		$this->presenter->flashMessage($message, 'success');
		$this->presenter->redirect(self::REDIRECT_AFTER_LOGOUT);
	}

}

interface ISignOutFactory
{

	/** @return SignOut */
	function create();
}
