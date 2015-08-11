<?php

namespace App\Mail\Messages;

class BuyoutTheirMessage extends BaseMessage
{

	protected function build()
	{
		$this->setFrom($this->settings->modules->buyout->email);
		$this->setSubject('Buyout request');
		return parent::build();
	}
}

interface IBuyoutTheirMessageFactory
{

	/**
	 * @return BuyoutTheirMessage
	 */
	public function create();
}
