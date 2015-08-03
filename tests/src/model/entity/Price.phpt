<?php

namespace Test\Model\Entity;

use App\Model\Entity\Price;
use App\Model\Entity\Vat;
use Test\BaseTestCase;
use Tester\Assert;

$container = require __DIR__ . '/../../bootstrap.php';

/**
 * TEST: Price entity
 *
 * @testCase
 * @phpVersion 5.4
 */
class PriceTest extends BaseTestCase
{

	/**
	 * @dataProvider dataPrices
	 */
	public function testSetAndGet($vat, $priceWithout, $priceWith)
	{
		$price = new Price(new Vat($vat));

		$vatSum = round($priceWith - $priceWithout, $price->precision);

		$price->setWithoutVat($priceWithout);
		Assert::same((float) $priceWithout, $price->withoutVat);
		Assert::same((float) $priceWith, $price->withVat);
		Assert::same((float) $vatSum, $price->vatSum);

		$price->setWithVat($priceWith);
		Assert::same((float) $priceWithout, $price->withoutVat);
		Assert::same((float) $priceWith, $price->withVat);
		Assert::same((float) $vatSum, $price->vatSum);

		Assert::same((string) $priceWithout, (string) $price);
	}

	public function dataPrices()
	{
		return [
			[0, 100, 100],
			[20, 100, 120],
			[21, 100, 121],
			[21, 158, 191.18],
			[21, 1879, 2273.59],
			[15, 589, 677.35],
		];
	}

}

$test = new PriceTest($container);
$test->run();
