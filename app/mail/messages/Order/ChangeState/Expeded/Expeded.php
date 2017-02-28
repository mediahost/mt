<?php

namespace App\Mail\Messages\Order\ChangeState;

use App\Mail\Messages\BaseMessage;

class Expeded extends BaseMessage
{
	
	protected function beforeSend()
	{
		$this->setSubject($this->translator->translate('mail.subject.changeStateOrder', ['id' => $this->order->id]));
		parent::beforeSend();
	}

}

interface IExpededFactory
{

	/**
	 * @return Expeded
	 */
	public function create();
}
