<?php

namespace App\Service\PaymentNotification;

use Nette\Object;

/**
 * @property-read $price
 * @property-read $vs
 */
class Payment extends Object
{

	/** @var */
	private $price;

	/** @var */
	private $vs;

	function __construct($vs, $price)
	{
		$this->price = $price;
		$this->vs = $vs;
	}

	/**
	 * @return mixed
	 */
	public function getPrice()
	{
		return $this->price;
	}

	/**
	 * @return mixed
	 */
	public function getVs()
	{
		return $this->vs;
	}



}