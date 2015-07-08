<?php

namespace Test\Model\Facade;

use Test\DbTestCase;
use Test\Parameters\Model\Entity\ParameterInline;
use Tester\Assert;

$container = require __DIR__ . '/../bootstrap.php';

/**
 * TEST: InlineTest
 *
 * @testCase
 * @phpVersion 5.4
 */
class InlineTest extends DbTestCase
{

//	protected function setUp()
//	{
//		parent::setUp();
////		$this->updateSchema();
//		$this->importDbDataFromFile(realpath('./sql/parameter_inline.sql')); // $this->createProducts(20000);
//	}

//	protected function tearDown()
//	{
//		$this->dropSchema();
//		parent::tearDown();
//	}

	public function testOne()
	{
		\Tracy\Debugger::timer();

		$qb = $this->em->createQueryBuilder()
				->select('p')
				->from(ParameterInline::getClassName(), 'p')
				->andWhere('p.parameter1 = :p')
				->setParameter('p', 'green')
		;

		$result = $qb->getQuery()->getResult();

		$time = \Tracy\Debugger::timer();
		\Tracy\Debugger::barDump($time);

		\Tracy\Debugger::barDump($result);
		Assert::same(TRUE, TRUE);
	}

	public function testMulti()
	{
		\Tracy\Debugger::timer();

		$qb = $this->em->createQueryBuilder()
				->select('p')
				->from(ParameterInline::getClassName(), 'p')
				->andWhere('p.parameter1 = :p1')
				->andWhere('p.parameter3 = :p3')
				->andWhere('p.parameter5 = :p5')
				->andWhere('p.parameter7 > :p7l')
				->andWhere('p.parameter7 < :p7h')
				->andWhere('p.parameter9 >= :p9l')
				->andWhere('p.parameter9 <= :p9h')
				->andWhere('p.parameter11 = :p11')
				->setParameter('p1', 'blue')
				->setParameter('p3', 'steel')
				->setParameter('p5', 'high')
				->setParameter('p7l', 110)
				->setParameter('p7h', 190)
				->setParameter('p9l', 310)
				->setParameter('p9h', 390)
				->setParameter('p11', TRUE)
		;

		$result = $qb->getQuery()->getResult();

		$time = \Tracy\Debugger::timer();
		\Tracy\Debugger::barDump($time);

		\Tracy\Debugger::barDump($result);
		Assert::same(TRUE, TRUE);
	}

	private function createProducts($count, $start = 1)
	{
		$colors = ['red', 'green', 'blue', 'yellow', 'pink'];
		$sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];
		$materials = ['wood', 'steel', 'plastic'];
		$construction = ['smartphone', 'outdoor', 'basic', 'seniors'];
		$performance = ['maximum', 'high', 'standard'];
		for ($i = $start; $i <= $count; $i++) {
			$product = new ParameterInline('product_' . $i);
			$product->parameter1 = $this->rand($colors);
			$product->parameter2 = $this->rand($sizes);
			$product->parameter3 = $this->rand($materials);
			$product->parameter4 = $this->rand($construction);
			$product->parameter5 = $this->rand($performance);
			$product->parameter6 = rand(20, 100);
			$product->parameter7 = rand(101, 200);
			$product->parameter8 = rand(201, 300);
			$product->parameter9 = rand(301, 400);
			$product->parameter10 = rand(401, 500);
			$product->parameter11 = (bool) rand(0, 1);
			$this->em->persist($product);
		}
		$this->em->flush();
	}

	private function rand(array $array)
	{
		return $array[rand(0, count($array) - 1)];
	}

	protected function getClasses()
	{
		return [
			$this->em->getClassMetadata(ParameterInline::getClassName()),
		];
	}

}

$test = new InlineTest($container);
$test->run();
