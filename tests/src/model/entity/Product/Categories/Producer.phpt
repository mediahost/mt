<?php

namespace Test\Model\Entity;

use App\Model\Entity\Producer;
use Doctrine\ORM\EntityRepository;
use Test\DbTestCase;
use Tester\Assert;

$container = require __DIR__ . '/../../../../bootstrap.php';

/**
 * TEST: Producer entity
 *
 * @testCase
 * @phpVersion 5.4
 */
class ProducerTest extends DbTestCase
{
	
	/** @var EntityRepository */
	private $producerRepo;

	public function setUp()
	{
		parent::setUp();
		$this->updateSchema();
		$this->producerRepo = $this->em->getRepository(Producer::class);
	}

	public function tearDown()
	{
		$this->dropSchema();
		parent::tearDown();
	}

	public function testSetAndGet()
	{		
		$entity = new Producer('name');
		
		$this->em->persist($entity);
		$this->em->flush();
		
		$id = $entity->id;
		
		$findedEntity = $this->producerRepo->find($id);
		Assert::same($id, $findedEntity->id);
	}

	protected function getClasses()
	{
		return [
			$this->em->getClassMetadata(Producer::getClassName()),
		];
	}

}

$test = new ProducerTest($container);
$test->run();
