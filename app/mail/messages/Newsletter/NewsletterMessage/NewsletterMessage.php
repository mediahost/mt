<?php

namespace App\Mail\Messages\Newsletter;

use App\Mail\Messages\BaseMessage;

class NewsletterMessage extends BaseMessage
{

	protected function beforeSend()
	{
		parent::beforeSend();
	}

}

interface INewsletterMessageFactory
{

	/** @return NewsletterMessage */
	public function create();
}
