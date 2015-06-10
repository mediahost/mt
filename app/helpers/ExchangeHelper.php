<?php

namespace App;

use App\Model\Entity\Price;
use h4kuna\Exchange\Exchange;

class ExchangeHelper
{

	/** @var Exchange @inject */
	private $exchange;

	public function __construct(Exchange $exchange)
	{
		$this->exchange = $exchange;
	}

	public function format($number, $from = NULL, $to = NULL, $vat = NULL)
	{
		if ($number instanceof Price) {
			if ($vat === TRUE) {
				$vat = $number->vat->value;
				$number = $number->withVat;
			} else {
				$number = $number->withoutVat;
			}
		}
		return $this->exchange->format($number, $from, $to, $vat);
	}

	public function formatVat(Price $price)
	{
		return $this->format($price, NULL, NULL, TRUE);
	}

	public function change($price, $from = NULL, $to = NULL, $round = NULL, $vat = NULL)
	{
		if ($price instanceof Price) {
			if ($vat === TRUE) {
				$vat = $price->vat->value;
				$price = $price->withVat;
			} else {
				$price = $price->withoutVat;
			}
		}
		return $this->exchange->change($price, $from, $to, $round, $vat);
	}

	public function changeVat(Price $price)
	{
		return $this->change($price, NULL, NULL, NULL, TRUE);
	}

}
