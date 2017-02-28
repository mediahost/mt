<?php

namespace App\Mail\Messages\Basket;

use App\Mail\Messages\BaseMessage;

class Unfinished extends BaseMessage
{
	
	protected function beforeSend()
	{
		$this->setSubject($this->translator->translate('mail.subject.unfinished'));
		parent::beforeSend();
	}

}

interface IUnfinishedMessageFactory
{

	/**
	 * @return Unfinished
	 */
	public function create();
}
