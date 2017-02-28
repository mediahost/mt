<?php

namespace App\Mail\Messages\Order\ChangeState;

use App\Mail\Messages\BaseMessage;

class Storno extends BaseMessage
{
	
	protected function beforeSend()
	{
		$this->setSubject($this->translator->translate('mail.subject.changeStateOrder', ['id' => $this->order->id]));
		parent::beforeSend();
	}

}

interface IStornoFactory
{

	/**
	 * @return Storno
	 */
	public function create();
}
