<?php

namespace App\Mail\Messages\Auth;

use App\Mail\Messages\BaseMessage;

class VerificationMessage extends BaseMessage
{

	protected function beforeSend()
	{
		$this->setFrom($this->settings->mails->automatFrom, $this->settings->pageInfo->projectName);
		$this->setSubject($this->translator->translate('mail.subject.verify'));
		parent::beforeSend();
	}

}

interface IVerificationMessageFactory
{

	/**
	 * @return VerificationMessage
	 */
	public function create();
}
