<?php

namespace App\Service\PaymentNotification;

interface IResolver
{

	/**
	 * @param string $message
	 * @param $from
	 * @return Payment[]
	 */
	function resolve($message, $from);

}
