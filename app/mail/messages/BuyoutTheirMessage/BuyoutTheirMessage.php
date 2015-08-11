<?php

namespace App\Mail\Messages;

class BuyoutTheirMessage extends BaseMessage
{

	public function __construct()
	{
		parent::__construct();
		$this->setFrom('buyout@mt.sk');
		$this->setSubject('Buyout request');
	}

}

interface IBuyoutTheirMessageFactory
{

	/**
	 * @return BuyoutTheirMessage
	 */
	public function create();
}
