<?php

namespace Test\Model\Entity;

use App\Model\Entity\PageConfigSettings;
use App\Model\Entity\User;
use Kdyby\Doctrine\MemberAccessException;
use Test\BaseTestCase;
use Tester\Assert;

$container = require __DIR__ . '/../../bootstrap.php';

/**
 * TEST: PageConfigSettings entity
 *
 * @testCase
 * @phpVersion 5.4
 */
class PageConfigSettingsTest extends BaseTestCase
{

	public function testSetAndGet()
	{
		$values = [
				'language' => 'cs_CS',
		];
		$user = new User;
		$user->mail = ('user@mail.com');

		$entity1 = new PageConfigSettings;
		Assert::null($entity1->id);
		Assert::count(0, $entity1->notNullValuesArray);
		Assert::count(2, $entity1->toArray());

		$entity1->setValues($values);
		Assert::count(1, $entity1->notNullValuesArray);
		Assert::same($values['language'], $entity1->language);

		Assert::exception(function () use ($entity1) {
			$entity1->id = 123;
		}, MemberAccessException::class);

		// init only one value
		$entity2 = new PageConfigSettings;
		$entity2->language = $values['language'];
		Assert::same($values['language'], $entity2->language);
		Assert::count(1, $entity2->notNullValuesArray);

		// append
		$entity3 = new PageConfigSettings;
		Assert::count(0, $entity3->notNullValuesArray);
		$entity3->append($entity2);
		Assert::count(1, $entity3->notNullValuesArray);
		Assert::same($values['language'], $entity3->language);

		// append with rewrite
		$entity4 = new PageConfigSettings();
		$newLanguageValue = 'sk_SK';
		$entity4->language = $newLanguageValue;
		Assert::same($newLanguageValue, $entity4->language);
		$entity4->append($entity2, TRUE);
		Assert::same($values['language'], $entity4->language);
		Assert::notSame($newLanguageValue, $entity4->language);
	}

}

$test = new PageConfigSettingsTest($container);
$test->run();
