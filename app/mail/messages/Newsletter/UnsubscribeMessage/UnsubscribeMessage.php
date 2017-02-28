<?php

namespace App\Mail\Messages\Newsletter;

use App\Mail\Messages\BaseMessage;

class UnsubscribeMessage extends BaseMessage
{

	protected function beforeSend()
	{
		$this->setSubject($this->translator->translate('newsletter.messages.unsubscribe.subject'));
		parent::beforeSend();
	}

}

interface IUnsubscribeMessageFactory
{

	/** @return UnsubscribeMessage */
	public function create();
}
