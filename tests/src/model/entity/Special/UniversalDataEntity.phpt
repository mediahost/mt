<?php

namespace Test\Model\Entity\Special;

use App\Model\Entity\Special\UniversalDataEntity;
use Test\BaseTestCase;
use Tester\Assert;

$container = require __DIR__ . '/../../../bootstrap.php';

/**
 * TEST: Universal data entity
 *
 * @testCase
 * @phpVersion 5.4
 */
class UniversalDataEntityTest extends BaseTestCase
{

	public function testSetAndGet()
	{
		$values1 = [
				'property1' => 'value1',
				'property2' => TRUE,
				'property3' => 'value2',
		];
		$values2 = [
				'property' => 'value',
		];

		$entity = new UniversalDataEntity($values1);
		Assert::same($values1['property1'], $entity->property1);
		Assert::true($entity->property2);
		Assert::same($values1['property3'], $entity->property3);
		Assert::null($entity->propertyUndefined);

		$entity->setData($values2);
		Assert::same($values2['property'], $entity->property);
		Assert::null($entity->property1);
	}

}

$test = new UniversalDataEntityTest($container);
$test->run();
