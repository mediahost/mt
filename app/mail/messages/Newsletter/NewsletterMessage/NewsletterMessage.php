<?php

namespace App\Mail\Messages\Newsletter;

use App\Mail\Messages\BaseMessage;

class NewsletterMessage extends BaseMessage
{

	protected function build()
	{
		$this->setFrom($this->settings->modules->newsletter->email);	
		return parent::build();
	}

}

interface INewsletterMessageFactory
{

	/** @return NewsletterMessage */
	public function create();
}
