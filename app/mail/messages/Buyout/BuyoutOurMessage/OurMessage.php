<?php

namespace App\Mail\Messages\Buyout;

class OurMessage extends BaseMessage
{

	protected function build()
	{
		$this->addTo($this->settings->modules->buyout->email);
		$this->setSubject('Buyout request');
		return parent::build();
	}

}

interface IOurMessageFactory
{

	/** @return OurMessage */
	public function create();
}
