<?php

namespace App\Mail\Messages\WatchDog;

use App\Mail\Messages\BaseMessage;

class Available extends BaseMessage
{
	
	protected function beforeSend()
	{
		$this->setFrom($this->settings->mails->automatFrom, $this->settings->pageInfo->projectName);
		$this->setSubject($this->translator->translate('mail.subject.available'));
		parent::beforeSend();
	}

}

interface IAvailableFactory
{

	/**
	 * @return Available
	 */
	public function create();
}
