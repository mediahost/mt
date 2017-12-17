<?php

namespace App\Extensions\PaymentNotification;

interface IXmlResolver
{

	/**
	 * @param string $xml
	 * @return Payment[]
	 */
	function resolve($xml);

}
