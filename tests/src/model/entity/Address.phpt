<?php

namespace Test\Model\Entity;

use App\Model\Entity\Address;
use Kdyby\Doctrine\MemberAccessException;
use Test\BaseTestCase;
use Tester\Assert;

$container = require __DIR__ . '/../../bootstrap.php';

/**
 * TEST: Address entity
 *
 * @testCase
 * @phpVersion 5.4
 */
class AddressTest extends BaseTestCase
{

	public function testSetAndGet()
	{
		$name = 'name surname';
		$street = 'street 123';
		$city = 'city under hill';
		$zipcode = '123 45';
		$country = 'our country';

		$entity = new Address;
		$entity->name = $name;
		$entity->street = $street;
		$entity->city = $city;
		$entity->zipcode = $zipcode;
		$entity->country = $country;

		Assert::null($entity->id);
		Assert::same($name, $entity->name);
		Assert::same($street, $entity->street);
		Assert::same($city, $entity->city);
		Assert::same($zipcode, $entity->zipcode);
		Assert::same($country, $entity->country);

		Assert::same($name, (string) $entity);

		Assert::exception(function () use ($entity) {
			$entity->id = 123;
		}, MemberAccessException::class);
	}

}

$test = new AddressTest($container);
$test->run();
