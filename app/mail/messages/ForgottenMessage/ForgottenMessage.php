<?php

namespace App\Mail\Messages;

class ForgottenMessage extends BaseMessage
{

	public function __construct()
	{
		parent::__construct();
		$this->setFrom('noreply@sc.com');
		$this->setSubject('Lost password');
	}

}

interface IForgottenMessageFactory
{

	/**
	 * @return ForgottenMessage
	 */
	public function create();
}
