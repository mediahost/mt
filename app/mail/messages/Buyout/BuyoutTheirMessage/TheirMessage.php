<?php

namespace App\Mail\Messages\Buyout;

use App\Mail\Messages\BaseMessage;

class TheirMessage extends BaseMessage
{

	protected function beforeSend()
	{
		$this->setFrom($this->settings->modules->buyout->email);
		$this->setSubject($this->translator->translate('buyout.email.their.subject'));
		parent::beforeSend();
	}

}

interface ITheirMessageFactory
{

	/** @return TheirMessage */
	public function create();
}
