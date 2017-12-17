<?php

namespace App\Extensions\PaymentNotification;

use App\Model\Entity\Order;
use XMLReader;

class SkSporitelnaResolver implements IXmlResolver
{

	public function resolve($xml)
	{
		$reader = new XMLReader();
		$reader->open($xml);

		$payments = [];
		while ($reader->read()) {
			if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'Ntry') { // Ntry

				$vs = $price = NULL;
				$currency = 'eur';

				while ($reader->read()) {
					if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->name === 'Ntry') {
						break;
					}

					if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'Amt') { // Amt
						$currency = (string)$reader->getAttribute('Ccy');
						$price = $reader->readString();
					}

					if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'NtryDtls') { // NtryDtls
						while ($reader->read()) {
							if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->name === 'NtryDtls') {
								break;
							}
							if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'Refs') { // Refs
								while ($reader->read()) {
									if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->name === 'Refs') {
										break;
									}
									if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'EndToEndId') { // EndToEndId
										if (preg_match('/\/VS0*(?P<VS>[0-9]*)/i', $reader->readString(), $matches)) {
											$vs = $matches['VS'];
										}
									}
								}
							}
						}
					}
				}
				if ($vs) {
					$payments[] = new Payment($vs, $price, Order::PAYMENT_BLAME_SK_SPORITELNA, $currency);
				}
			}
		}
		return $payments;
	}

}