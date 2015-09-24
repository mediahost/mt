<?php

namespace App\Service\PaymentNotification;

class CSOBResolver implements IResolver
{

	/**
	 * @param string $message
	 * @param $from
	 * @return Payment[]
	 */
	function resolve($message, $from)
	{
		if ($from != 'AdminTBS@csob.sk') {
			return [];
		}
		$matched = preg_match('/ovoľujeme si Vám oznámiť.*informácia pre príjemcu:/ims', $message, $matches);
		if (!$matched) {
		    return [];
		};

		$payments = explode('dovoľujeme si Vám oznámiť',$matches[0]);
		$vs = NULL;
		$price = NULL;
		$orderId = NULL;
		$return = [];
		foreach ($payments as $payment) {
			$priceFound = preg_match('/suma:\s*\+([0-9,]*)\s*EUR/i', $payment, $matches);
			if (!$priceFound) {
				continue;
			}
			$str = str_replace(',', '.', $matches[1]);
			$price = $priceFound ? floatval($str) : NULL;
			$vsFound = preg_match('/referencia platiteľa:\s*\/VS0*([0-9]*)\//i', $payment, $matches);
			if (!$vsFound) {
				continue;
			}
			$vs = $matches[1];
			$return[] = new Payment($vs, $price);
		}
		return $return;
	}

}
