<?php

namespace App\Extensions\PaymentNotification;

interface IResolver
{

	/**
	 * @param string $message
	 * @param $from
	 * @return Payment[]
	 */
	function resolve($message, $from);

}
