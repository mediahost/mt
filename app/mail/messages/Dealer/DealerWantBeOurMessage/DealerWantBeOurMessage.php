<?php

namespace App\Mail\Messages\Dealer;

use App\Mail\Messages\BaseMessage;

class DealerWantBeOurMessage extends BaseMessage
{

	protected function beforeSend()
	{
		$this->addTo($this->settings->modules->dealer->email);
		$this->setSubject($this->translator->translate('dealer.email.our.subject'));
		parent::beforeSend();
	}

}

interface IDealerWantBeOurMessageFactory
{

	/** @return DealerWantBeOurMessage */
	public function create();
}
