<?php

namespace App\Mail\Messages\Newsletter;

use App\Mail\Messages\BaseMessage;

class UnsubscribeMessage extends BaseMessage
{

	protected function build()
	{
		$this->setFrom($this->settings->modules->newsletter->email);
		$this->setSubject($this->translator->translate('newsletter.messages.unsubscribe.subject'));
		return parent::build();
	}

}

interface IUnsubscribeMessageFactory
{

	/**
	 * @return UnsubscribeMessage
	 */
	public function create();
}
