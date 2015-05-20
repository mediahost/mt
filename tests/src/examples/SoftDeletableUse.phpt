<?php

namespace Test\Examples;

use Kdyby\Doctrine\EntityRepository;
use Nette\DI\Container;
use Nette\Utils\DateTime;
use Test\Examples\Model\Entity\SoftDeletable;
use Tester\Assert;

$container = require __DIR__ . '/../bootstrap.php';

/**
 * TEST: SoftDeletable use
 * https://github.com/KnpLabs/DoctrineBehaviors/blob/master/tests/Knp/DoctrineBehaviors/ORM/SoftDeletableTest.php
 *
 * @testCase
 * @phpVersion 5.4
 */
class SoftDeletableUseTest extends BaseUse
{

	/** @var EntityRepository */
	private $deleteRepo;

	public function __construct(Container $container)
	{
		parent::__construct($container);
		$this->deleteRepo = $this->em->getRepository(SoftDeletable::getClassName());
	}

	protected function setUp()
	{
		parent::setUp();
		$this->updateSchema();
	}

	protected function tearDown()
	{
		parent::tearDown();
		$this->dropSchema();
	}

	public function testDelete()
	{
		$entity = new SoftDeletable();
		
		$this->em->persist($entity);
		$this->em->flush();
		
		$id = $entity->id;
		Assert::notSame(NULL, $id);
		Assert::false($entity->isDeleted());
		
		$this->em->remove($entity);
		$this->em->flush();
		$this->em->clear();
		
		$findedEntity = $this->deleteRepo->find($id);
		
		Assert::notSame(NULL, $findedEntity);
		Assert::true($findedEntity->isDeleted());
	}

	public function testPostDelete()
	{
		$entity = new SoftDeletable();
		
		$this->em->persist($entity);
		$this->em->flush();
		
		$id = $entity->id;
		Assert::notSame(NULL, $id);
		
		$entity->deletedAt = (new DateTime)->modify('+1 day');
		
		$this->em->flush();
		$this->em->clear();
		
		$findedEntity1 = $this->deleteRepo->find($id);
		
		Assert::notSame(NULL, $findedEntity1);
		Assert::false($findedEntity1->isDeleted());
		Assert::true($findedEntity1->willBeDeleted());
		Assert::true($findedEntity1->willBeDeleted((new DateTime)->modify('+2 day')));
		Assert::false($findedEntity1->willBeDeleted((new DateTime)->modify('+12 hour')));
		
		$findedEntity1->deletedAt = (new DateTime)->modify('-1 day');
		
		$this->em->flush();
		$this->em->clear();
		
		$findedEntity2 = $this->deleteRepo->find($id);
		
		Assert::notSame(NULL, $findedEntity1);
		Assert::true($findedEntity2->isDeleted());
	}

	protected function getClasses()
	{
		return [
			$this->em->getClassMetadata(SoftDeletable::getClassName()),
		];
	}

}

$test = new SoftDeletableUseTest($container);
$test->run();
