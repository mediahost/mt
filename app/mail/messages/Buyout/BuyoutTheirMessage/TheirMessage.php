<?php

namespace App\Mail\Messages\Buyout;

class TheirMessage extends BaseMessage
{

	protected function build()
	{
		$this->setFrom($this->settings->modules->buyout->email);
		$this->setSubject('Buyout request');
		return parent::build();
	}

}

interface ITheirMessageFactory
{

	/** @return TheirMessage */
	public function create();
}
