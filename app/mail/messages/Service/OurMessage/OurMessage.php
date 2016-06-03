<?php

namespace App\Mail\Messages\Service;

use App\Mail\Messages\BaseMessage;

class OurMessage extends BaseMessage
{

	protected function beforeSend()
	{
		$this->addTo($this->settings->modules->service->email);
		$this->setSubject($this->translator->translate('service.email.our.subject'));
		parent::beforeSend();
	}

}

interface IOurMessageFactory
{

	/** @return OurMessage */
	public function create();
}
