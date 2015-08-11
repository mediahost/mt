<?php

namespace App\Mail\Messages;

class BuyoutOurMessage extends BaseMessage
{

	public function __construct()
	{
		parent::__construct();
		$this->addTo('buyout@mt.sk');
		$this->setSubject('Buyout request');
	}

}

interface IBuyoutOurMessageFactory
{

	/**
	 * @return BuyoutOurMessage
	 */
	public function create();
}
