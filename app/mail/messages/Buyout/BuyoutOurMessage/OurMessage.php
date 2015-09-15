<?php

namespace App\Mail\Messages\Buyout;

class OurMessage extends BaseMessage
{

	protected function beforeSend()
	{
		$this->addTo($this->settings->modules->buyout->email);
		$this->setSubject($this->translator->translate('buyout.email.our.subject'));
		parent::beforeSend();
	}

}

interface IOurMessageFactory
{

	/** @return OurMessage */
	public function create();
}
