<?php

namespace App\Mail\Messages\WatchDog;

use App\Mail\Messages\BaseMessage;

class LoweredPrice extends BaseMessage
{
	
	protected function beforeSend()
	{
		$this->setFrom($this->settings->mails->automatFrom, $this->settings->pageInfo->projectName);
		$this->setSubject($this->translator->translate('mail.subject.loweredPrice'));
		parent::beforeSend();
	}

}

interface ILoweredPriceFactory
{

	/**
	 * @return LoweredPrice
	 */
	public function create();
}
