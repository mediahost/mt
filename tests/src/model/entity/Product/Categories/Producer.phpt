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
		$prod1 = new Producer('producer 1');
		$prod2 = new Producer('producer 2');
		$prod3 = new Producer('producer 3');
		$this->em->persist($prod1);
		$this->em->persist($prod2);
		$this->em->persist($prod3);
		
		$entity = new Producer('name');
		$entity->parent = $prod1;
		$entity->addChild($prod2);
		$entity->addChild($prod3);
		
		$this->em->persist($entity);
		$this->em->flush();
		
		$id = $entity->id;
		$parentId = $prod1->id;
		
		$findedEntity = $this->producerRepo->find($id);
		
		Assert::same($id, $findedEntity->id);
		
		Assert::same($parentId, $findedEntity->parent->id);
		Assert::same('producer 1', (string) $findedEntity->parent);
		Assert::null($findedEntity->parent->parent);
		
		Assert::count(2, $findedEntity->children);
		Assert::same('producer 2', (string) $findedEntity->children[0]);
		Assert::same('producer 3', $findedEntity->children[1]->name);
	}

	public function testPathAndUrl()
	{
		$prod1 = new Producer('Producer 1');
		$prod2 = new Producer('Producer 2');
		$prod3 = new Producer('Producer 3');
		$entity = new Producer('The Name');
		
		$prod1->addChild($prod2);
		$prod2->addChild($prod3);
		$entity->parent = $prod3;
		
		$this->em->persist($prod1);
		$this->em->persist($prod2);
		$this->em->persist($prod3);
		
		$this->em->persist($entity);
		$this->em->flush();
		
		$id = $entity->id;
		$parentId = $prod3->id;
		$parentParentId = $prod2->id;
		$parentParentParentId = $prod1->id;
		
		$findedProducer1 = $this->producerRepo->find($parentParentParentId);
		$findedProducer2 = $this->producerRepo->find($parentParentId);
		$findedProducer3 = $this->producerRepo->find($parentId);
		$findedProducer4 = $this->producerRepo->find($id);
		
		Assert::same($id, $findedProducer4->id);
		Assert::same($parentId, $findedProducer4->parent->id);
		Assert::same($parentParentId, $findedProducer4->parent->parent->id);
		Assert::same($parentParentParentId, $findedProducer4->parent->parent->parent->id);
		Assert::null($findedProducer4->parent->parent->parent->parent);
		Assert::null($findedProducer1->parent);
		
		Assert::same('producer-1', $findedProducer1->slug);
		Assert::same('producer-2', $findedProducer2->slug);
		Assert::same('producer-3', $findedProducer3->slug);
		Assert::same('the-name', $findedProducer4->slug);
		
		Assert::same('producer-1', $findedProducer1->url);
		Assert::same('producer-1/producer-2', $findedProducer2->url);
		Assert::same('producer-1/producer-2/producer-3', $findedProducer3->url);
		Assert::same('producer-1/producer-2/producer-3/the-name', $findedProducer4->url);
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
