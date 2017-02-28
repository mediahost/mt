<?php

namespace App\Mail\Messages\Auth;

use App\Mail\Messages\BaseMessage;

class CreateRegistrationMessage extends BaseMessage
{

	protected function beforeSend()
	{
		$this->setSubject($this->translator->translate('mail.subject.createRegistration'));
		parent::beforeSend();
	}

}

interface ICreateRegistrationMessageFactory
{

	/**
	 * @return CreateRegistrationMessage
	 */
	public function create();
}
