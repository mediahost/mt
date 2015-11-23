<?php

namespace App\Extensions\PaymentNotification;

use Nette\Object;

/**
 * @property-read $price
 * @property-read $vs
 * @property-read $type
 */
class Payment extends Object
{

	private $price;

	private $vs;

	protected $type;

	function __construct($vs, $price, $type)
	{
		$this->price = $price;
		$this->vs = $vs;
		$this->type = $type;
	}

	public function getPrice()
	{
		return $this->price;
	}

	public function getVs()
	{
		return $this->vs;
	}

	public function getType()
	{
		return $this->type;
	}

}
