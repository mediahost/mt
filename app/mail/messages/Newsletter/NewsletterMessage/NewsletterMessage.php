<?php

namespace App\Mail\Messages\Newsletter;

use App\Mail\Messages\BaseMessage;

class NewsletterMessage extends BaseMessage
{

	protected function beforeSend()
	{
		$this->setFrom($this->settings->mails->automatFrom, $this->settings->pageInfo->projectName);
		parent::beforeSend();
	}

}

interface INewsletterMessageFactory
{

	/** @return NewsletterMessage */
	public function create();
}
