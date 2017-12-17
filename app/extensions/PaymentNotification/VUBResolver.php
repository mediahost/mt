<?php

namespace App\Extensions\PaymentNotification;

use App\Model\Entity\Order;

class VUBResolver implements IResolver
{

	/**
	 * @param string $message
	 * @param $from
	 * @return Payment[]
	 */
	function resolve($message, $from)
	{
		if ($from != 'nonstopbanking@vub.sk') {
			return [];
		}
		$vsFound = preg_match('/Variabilný symbol: (\d+)/', $message, $matchesVS);
		$priceFound = preg_match('/vo výške ([0-9,]+) EUR\./', $message, $matchesPrice);

		if (!$vsFound || !$priceFound) {
			return [];
		}

		$vs = $matchesVS[1];
		$price = floatval(str_replace(',', '.', $matchesPrice[1]));

		$payment = new Payment($vs, $price, Order::PAYMENT_BLAME_VUB, 'eur');
		return [$payment];
	}

}
