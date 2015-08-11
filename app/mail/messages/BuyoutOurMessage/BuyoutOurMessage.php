<?php

namespace App\Mail\Messages;

class BuyoutOurMessage extends BaseMessage
{

	protected function build()
	{
		$this->addTo($this->settings->modules->buyout->email);
		$this->setSubject('Buyout request');
		return parent::build();
	}

}

interface IBuyoutOurMessageFactory
{

	/**
	 * @return BuyoutOurMessage
	 */
	public function create();
}
