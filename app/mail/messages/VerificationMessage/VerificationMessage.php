<?php

namespace App\Mail\Messages;

class VerificationMessage extends BaseMessage
{

	public function __construct()
	{
		parent::__construct();
		$this->setFrom('noreply@sc.com');
		$this->setSubject('Verify your e-mail');
	}

}

interface IVerificationMessageFactory
{

	/**
	 * @return VerificationMessage
	 */
	public function create();
}
