<?php

namespace Test\Model\Entity;

use App\Model\Entity\Discount;
use App\Model\Entity\Price;
use App\Model\Entity\Vat;
use Test\BaseTestCase;
use Tester\Assert;

$container = require __DIR__ . '/../../bootstrap.php';

/**
 * TEST: Discount entity
 *
 * @testCase
 * @phpVersion 5.4
 */
class DiscountTest extends BaseTestCase
{

	/**
	 * @dataProvider dataDiscounts
	 */
	public function testSetAndGet($vat, $priceWithout, array $percentage, array $fixed, array $minus)
	{
		list($percentageValue, $expPercWitout, $expPercWith) = $percentage;
		list($fixedValue, $expFixWitout, $expFixWith) = $fixed;
		list($minusValue, $expMinWitout, $expMinWith) = $minus;
		
		$price = new Price(new Vat($vat));
		$price->setWithoutVat($priceWithout);
		
		$discountPercent = new Discount();
		$discountPercent->value = $percentageValue;
		$discountPercent->type = Discount::PERCENTAGE;
		$percDiscounted = $discountPercent->getDiscountedPrice($price);
		
		Assert::same((float) $expPercWitout, $percDiscounted->withoutVat);
		Assert::same((float) $expPercWith, $percDiscounted->withVat);
		
		$discountFixed = new Discount();
		$discountFixed->value = $fixedValue;
		$discountFixed->type = Discount::FIXED_PRICE;
		$fixDiscounted = $discountFixed->getDiscountedPrice($price);
		
		Assert::same((float) $expFixWitout, $fixDiscounted->withoutVat);
		Assert::same((float) $expFixWith, $fixDiscounted->withVat);
		
		$discountMinus = new Discount();
		$discountMinus->value = $minusValue;
		$discountMinus->type = Discount::MINUS_VALUE;
		$minDiscounted = $discountMinus->getDiscountedPrice($price);
		
		Assert::same((float) $expMinWitout, $minDiscounted->withoutVat);
		Assert::same((float) $expMinWith, $minDiscounted->withVat);
	}

	public function dataDiscounts()
	{
		return [
			[20, 100, [10, 90, 108], [10, 10, 12], [11, 89, 106.8]],
			[15, 123456, [5, 117283.2, 134875.68], [5, 5, 5.75], [5, 123451, 141968.65]],
		];
	}

}

$test = new DiscountTest($container);
$test->run();
