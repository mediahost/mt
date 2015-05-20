<?php

namespace Test\Examples;

use Kdyby\Doctrine\EntityRepository;
use Nette\DI\Container;
use Nette\Utils\DateTime;
use Test\Examples\Model\Entity\Sluggable;
use Test\Examples\Model\Entity\Timestampable;
use Tester\Assert;

$container = require __DIR__ . '/../bootstrap.php';

/**
 * TEST: Timestampable use
 *
 * @testCase
 * @phpVersion 5.4
 */
class TimestampableUseTest extends BaseUse
{

	/** @var EntityRepository */
	private $timestampRepo;

	public function __construct(Container $container)
	{
		parent::__construct($container);
		$this->timestampRepo = $this->em->getRepository(Timestampable::getClassName());
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

	public function testIt_should_initialize_create_and_update_datetime_when_created()
	{
		$entity = new Timestampable();

		$this->em->persist($entity);
		$this->em->flush();

		Assert::type('Datetime', $entity->createdAt);
		Assert::type('Datetime', $entity->updatedAt);
		Assert::equal($entity->createdAt, $entity->updatedAt);
	}

	public function testIt_should_modify_update_datetime_when_updated_but_not_the_creation_datetime()
	{
		$entity = new Timestampable();
		
		$this->em->persist($entity);
        $this->em->flush();
        $id = $entity->id;
        $createdAt = $entity->createdAt;
        $this->em->clear();
		
		// wait for a second:
        sleep(1);
		
		$findedEntity1 = $this->timestampRepo->find($id);
        $findedEntity1->name = 'The name';
        $this->em->flush();
        $this->em->clear();
		
		$findedEntity2 = $this->timestampRepo->find($id);
		Assert::equal($createdAt, $findedEntity2->createdAt);
		Assert::notEqual($findedEntity2->createdAt, $findedEntity2->updatedAt);
	}
	
	public function testIt_should_return_the_same_datetime_when_not_updated()
	{
        $entity = new Timestampable();
		
		$this->em->persist($entity);
        $this->em->flush();
        $id = $entity->id;
        $createdAt = $entity->createdAt;
        $updatedAt = $entity->updatedAt;
        $this->em->clear();
		
		// wait for a second:
        sleep(1);
		
		$findedEntity = $this->timestampRepo->find($id);
        $this->em->persist($findedEntity);
        $this->em->flush();
        $this->em->clear();
		
		Assert::equal($createdAt, $findedEntity->createdAt);
		Assert::equal($updatedAt, $findedEntity->updatedAt);
	}
	
	public function testIt_should_modify_update_datetime_only_once()
	{
        $entity = new Timestampable();
		
		$this->em->persist($entity);
        $this->em->flush();
        $id = $entity->id;
        $createdAt = $entity->createdAt;
        $this->em->clear();
		
		// wait for a second:
        sleep(1);
		
		$findedEntity = $this->timestampRepo->find($id);
        $findedEntity->name = 'The name';
        $this->em->flush();
        $updatedAt = $findedEntity->updatedAt;
		
        sleep(1);
		
		$this->em->flush();
		
		Assert::equal($createdAt, $findedEntity->createdAt);
		Assert::equal($updatedAt, $findedEntity->updatedAt);
	}

	protected function getClasses()
	{
		return [
			$this->em->getClassMetadata(Timestampable::getClassName()),
		];
	}

}

$test = new TimestampableUseTest($container);
$test->run();
