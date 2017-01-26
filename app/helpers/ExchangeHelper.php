<?php

namespace App;

use App\Model\Entity\Price;
use h4kuna\Exchange\Currency\Property;
use h4kuna\Exchange\Exchange;

class ExchangeHelper
{

	/** @var Exchange @inject */
	private $exchange;

	public function __construct(Exchange $exchange)
	{
		$this->exchange = $exchange;
	}

	public function getExchange()
	{
		return $this->exchange;
	}

	public function format($price, $from = NULL, $to = NULL, $vat = FALSE)
	{
		$number = $price;
		if ($price instanceof Price) {
			$number = $vat ? $price->withVat : $price->withoutVat;
			if (!$price->convertible) {
				return $this->formatNumber($number, TRUE, $to);
			}
		}
		return $this->exchange->format($number, $from, $to);
	}

	public function formatVat(Price $price, $from = NULL, $to = NULL)
	{
		return $this->format($price, $from, $to, TRUE);
	}

	public function change($price, $from = NULL, $to = NULL, $round = NULL, $vat = FALSE)
	{
		$number = $price;
		if ($price instanceof Price) {
			$number = $vat ? $price->withVat : $price->withoutVat;
			if (!$price->convertible) {
				return $number;
			}
		}
		return $this->exchange->change($number, $from, $to, $round);
	}

	public function changeVat(Price $price, $from = NULL, $to = NULL, $round = NULL)
	{
		return $this->change($price, $from, $to, $round, TRUE);
	}

	public function formatNumber($number, $withSymbol = FALSE, $to = NULL)
	{
		$format = $to ? clone $this->exchange[$to]->getFormat() : clone $this->exchange->getWeb()->getFormat();
		if (!$withSymbol) {
			$format->symbol = NULL;
		}
		return $format->render($number);
	}

	public static function getRelatedRate($newRate, Property $originCurrency)
	{
		$dbRate = (float)$newRate;
		$originRate = (float)$originCurrency->getForeing();
		$rateRelated = $originRate / $dbRate;
		return $rateRelated;
	}

}
