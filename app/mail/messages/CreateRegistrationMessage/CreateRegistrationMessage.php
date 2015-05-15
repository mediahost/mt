<?php

namespace App\Mail\Messages;

class CreateRegistrationMessage extends BaseMessage
{

	public function __construct()
	{
		parent::__construct();
		$this->setFrom('noreply@sc.com');
		$this->setSubject('Your registration');
	}

}

interface ICreateRegistrationMessageFactory
{

	/**
	 * @return CreateRegistrationMessage
	 */
	public function create();
}
