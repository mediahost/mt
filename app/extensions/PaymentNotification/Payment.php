<?php

namespace App\Extensions\PaymentNotification;

use Nette\Object;

/**
 * @property-read float $price
 * @property-read string $vs
 * @property-read string $type
 * @property-read string $currency
 */
class Payment extends Object
{

	/** @var float */
	private $price;

	/** @var string */
	private $vs;

	/** @var string */
	protected $type;

	/** @var string */
	protected $currency;

	function __construct($vs, $price, $type, $currency)
	{
		$this->price = $price;
		$this->vs = $vs;
		$this->type = $type;
		$this->currency = $currency;
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

	public function getCurrency()
	{
		return $this->currency;
	}

}
