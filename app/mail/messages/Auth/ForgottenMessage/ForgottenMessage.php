<?php

namespace App\Mail\Messages\Auth;

use App\Mail\Messages\BaseMessage;

class ForgottenMessage extends BaseMessage
{

	protected function beforeSend()
	{
		$this->setFrom($this->settings->mails->automatFrom, $this->settings->pageInfo->projectName);
		$this->setSubject($this->translator->translate('mail.subject.forgotten'));
		parent::beforeSend();
	}

}

interface IForgottenMessageFactory
{

	/**
	 * @return ForgottenMessage
	 */
	public function create();
}
