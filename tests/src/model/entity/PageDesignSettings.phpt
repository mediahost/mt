<?php

namespace Test\Model\Entity;

use App\Model\Entity\PageDesignSettings;
use Kdyby\Doctrine\MemberAccessException;
use Test\BaseTestCase;
use Tester\Assert;

$container = require __DIR__ . '/../../bootstrap.php';

/**
 * TEST: PageDesignSettings entity
 *
 * @testCase
 * @phpVersion 5.4
 */
class PageDesignSettingsTest extends BaseTestCase
{

	public function testSetAndGet()
	{
		$values = [
				'color'            => 'default',
				'layoutBoxed'      => TRUE,
				'containerBgSolid' => TRUE,
				'headerFixed'      => TRUE,
				'footerFixed'      => FALSE,
				'sidebarClosed'    => FALSE,
				'sidebarFixed'     => TRUE,
				'sidebarReversed'  => TRUE,
				'sidebarMenuHover' => FALSE,
				'sidebarMenuLight' => TRUE,
		];

		$entity1 = new PageDesignSettings;
		Assert::null($entity1->id);
		Assert::count(0, $entity1->notNullValuesArray);
		Assert::count(11, $entity1->toArray());

		$entity1->setValues($values);
		Assert::count(10, $entity1->notNullValuesArray);
		Assert::same($values['color'], $entity1->color);
		Assert::same($values['containerBgSolid'], $entity1->containerBgSolid);
		Assert::same($values['headerFixed'], $entity1->headerFixed);
		Assert::same($values['footerFixed'], $entity1->footerFixed);
		Assert::same($values['sidebarClosed'], $entity1->sidebarClosed);
		Assert::same($values['sidebarFixed'], $entity1->sidebarFixed);
		Assert::same($values['sidebarReversed'], $entity1->sidebarReversed);
		Assert::same($values['sidebarMenuHover'], $entity1->sidebarMenuHover);
		Assert::same($values['sidebarMenuLight'], $entity1->sidebarMenuLight);

		Assert::exception(function () use ($entity1) {
			$entity1->id = 123;
		}, MemberAccessException::class);

		// init only one value
		$entity2 = new PageDesignSettings;
		$entity2->color = $values['color'];
		Assert::same($values['color'], $entity2->color);
		Assert::count(1, $entity2->notNullValuesArray);

		// append
		$entity3 = new PageDesignSettings;
		Assert::count(0, $entity3->notNullValuesArray);
		$entity3->append($entity2);
		Assert::count(1, $entity3->notNullValuesArray);
		Assert::same($values['color'], $entity3->color);

		// append with rewrite
		$entity4 = new PageDesignSettings;
		$newColorValue = 'notDefault';
		$entity4->color = $newColorValue;
		Assert::same($newColorValue, $entity4->color);
		$entity4->append($entity2, TRUE);
		Assert::same($values['color'], $entity4->color);
		Assert::notSame($newColorValue, $entity4->color);
	}

}

$test = new PageDesignSettingsTest($container);
$test->run();
