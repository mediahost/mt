<?php

namespace App\Mail\Messages\Service;

use App\Mail\Messages\BaseMessage;

class TheirMessage extends BaseMessage
{

	protected function beforeSend()
	{
		$this->setFrom($this->settings->mails->automatFrom, $this->settings->pageInfo->projectName);
		$this->setSubject($this->translator->translate('service.email.their.subject'));
		parent::beforeSend();
	}

}

interface ITheirMessageFactory
{

	/** @return TheirMessage */
	public function create();
}
