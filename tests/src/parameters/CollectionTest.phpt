<?php

namespace Test\Model\Facade;

use Doctrine\ORM\Query\Expr\Join;
use Test\DbTestCase;
use Test\Parameters\Model\Entity\Parameter;
use Test\Parameters\Model\Entity\ParameterCollection;
use Test\Parameters\Model\Entity\ParameterName;
use Tester\Assert;

$container = require __DIR__ . '/../bootstrap.php';

/**
 * TEST: CollectionTest
 *
 * @testCase
 * @phpVersion 5.4
 */
class CollectionTest extends DbTestCase
{

	protected function setUp()
	{
		parent::setUp();
		$this->updateSchema();
		$this->importDbDataFromFile(realpath('./sql/parameter_collection.sql')); // $this->createProducts(20000);
	}

	protected function tearDown()
	{
		$this->dropSchema();
		parent::tearDown();
	}

	public function testOne()
	{
		$parameterRepo = $this->em->getRepository(ParameterName::getClassName());
		$parameter1 = $parameterRepo->findOneBy(['name' => 'Color']);

		\Tracy\Debugger::timer();

		$qb = $this->em->createQueryBuilder()
				->select('p')
				->from(ParameterCollection::getClassName(), 'p')
				->join('p.parameters', 'param')
				->andWhere('param.name = :paramName')
				->setParameter('paramName', $parameter1)
				->andWhere('param.string = :value')
				->setParameter('value', 'green')
		;

		$result = $qb->getQuery()->getResult();

		$time = \Tracy\Debugger::timer();
		\Tracy\Debugger::barDump($time);

		\Tracy\Debugger::barDump($result);
		Assert::same(TRUE, TRUE);
	}

	public function testMulti()
	{
		$parameterRepo = $this->em->getRepository(ParameterName::getClassName());
		$parameter1 = $parameterRepo->findOneBy(['name' => 'Color']);
		$parameter3 = $parameterRepo->findOneBy(['name' => 'Material']);
		$parameter5 = $parameterRepo->findOneBy(['name' => 'Performance']);
		$parameter7 = $parameterRepo->findOneBy(['name' => 'Width']);
		$parameter9 = $parameterRepo->findOneBy(['name' => 'Display']);
		$parameter11 = $parameterRepo->findOneBy(['name' => 'WiFi']);

		\Tracy\Debugger::timer();

		$qb = $this->em->createQueryBuilder()
				->select('p')
				->from(ParameterCollection::getClassName(), 'p')
				->join('p.parameters', 'p1', Join::WITH, 'p1.name = :n1')
				->join('p.parameters', 'p3', Join::WITH, 'p3.name = :n3')
				->andWhere('p1.string = :v1')
				->andWhere('p3.string = :v3')
//				->andWhere('param.name = :n5 AND param.string = :v5')
//				->andWhere('param.name = :n7 AND param.number > :v7l AND param.number < :v7h')
//				->andWhere('param.name = :n9 AND param.number >= :v9l AND param.number <= :v9h')
//				->andWhere('param.name = :n11 AND param.bool = :v11')
				->setParameter('n1', $parameter1)->setParameter('v1', 'blue')
				->setParameter('n3', $parameter3)->setParameter('v3', 'steel')
//				->setParameter('n5', $parameter5)->setParameter('v5', 'high')
//				->setParameter('n7', $parameter7)->setParameter('v7l', 110)->setParameter('v7h', 190)
//				->setParameter('n9', $parameter9)->setParameter('v9l', 310)->setParameter('v9h', 390)
//				->setParameter('n11', $parameter11)->setParameter('v11', TRUE)
		;

		$result = $qb->getQuery()->getResult();

		$time = \Tracy\Debugger::timer();
		\Tracy\Debugger::barDump($time);

		\Tracy\Debugger::barDump($result);
		Assert::same(TRUE, TRUE);
	}

	private function createParameters()
	{
		$parameters = [
			'Color',
			'Size',
			'Material',
			'Construction',
			'Performance',
			'Weight',
			'Width',
			'Height',
			'Display',
			'Battery',
			'WiFi',
		];
		$parameterRepo = $this->em->getRepository(ParameterName::getClassName());
		foreach ($parameters as $name) {
			$parameter = $parameterRepo->findOneBy(['name' => $name]);
			if (!$parameter) {
				$parameter = new ParameterName($name);
				$this->em->persist($parameter);
			}
		}
		$this->em->flush();
	}

	private function createProducts($count, $limit = 100)
	{
		$this->createParameters();
		$parameterRepo = $this->em->getRepository(ParameterName::getClassName());
		$parameter1 = $parameterRepo->findOneBy(['name' => 'Color']);
		$parameter2 = $parameterRepo->findOneBy(['name' => 'Size']);
		$parameter3 = $parameterRepo->findOneBy(['name' => 'Material']);
		$parameter4 = $parameterRepo->findOneBy(['name' => 'Construction']);
		$parameter5 = $parameterRepo->findOneBy(['name' => 'Performance']);
		$parameter6 = $parameterRepo->findOneBy(['name' => 'Weight']);
		$parameter7 = $parameterRepo->findOneBy(['name' => 'Width']);
		$parameter8 = $parameterRepo->findOneBy(['name' => 'Height']);
		$parameter9 = $parameterRepo->findOneBy(['name' => 'Display']);
		$parameter10 = $parameterRepo->findOneBy(['name' => 'Battery']);
		$parameter11 = $parameterRepo->findOneBy(['name' => 'WiFi']);

		$colors = ['red', 'green', 'blue', 'yellow', 'pink'];
		$sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];
		$materials = ['wood', 'steel', 'plastic'];
		$construction = ['smartphone', 'outdoor', 'basic', 'seniors'];
		$performance = ['maximum', 'high', 'standard'];

		$productRepo = $this->em->getRepository(ParameterCollection::getClassName());
		$lastProduct = $productRepo->findOneBy([], ['id' => 'DESC']);
		$start = 1;
		if ($lastProduct) {
			$start = $lastProduct->id + 1;
		}
		$rest = $count - $start + 1;
		if ($rest > $limit) {
			$count = $start + $limit - 1;
		}

		for ($i = $start; $i <= $count; $i++) {
			$product = new ParameterCollection('product_' . $i);
			$product->addParameter($parameter1, $this->rand($colors));
			$product->addParameter($parameter2, $this->rand($sizes));
			$product->addParameter($parameter3, $this->rand($materials));
			$product->addParameter($parameter4, $this->rand($construction));
			$product->addParameter($parameter5, $this->rand($performance));
			$product->addParameter($parameter6, rand(20, 100));
			$product->addParameter($parameter7, rand(101, 200));
			$product->addParameter($parameter8, rand(201, 300));
			$product->addParameter($parameter9, rand(301, 400));
			$product->addParameter($parameter10, rand(401, 500));
			$product->addParameter($parameter11, (bool) rand(0, 1));
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
			$this->em->getClassMetadata(ParameterCollection::getClassName()),
			$this->em->getClassMetadata(Parameter::getClassName()),
			$this->em->getClassMetadata(ParameterName::getClassName()),
		];
	}

}

$test = new CollectionTest($container);
$test->run();
