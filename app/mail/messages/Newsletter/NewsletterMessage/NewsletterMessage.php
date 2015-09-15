<?php

namespace App\Mail\Messages\Newsletter;

use App\Mail\Messages\BaseMessage;

class NewsletterMessage extends BaseMessage
{

	protected function beforeSend()
	{
		$this->setFrom($this->settings->modules->newsletter->email);
		parent::beforeSend();
	}

}

interface INewsletterMessageFactory
{

	/** @return NewsletterMessage */
	public function create();
}
