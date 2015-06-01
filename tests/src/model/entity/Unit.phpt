<?php

namespace Test\Model\Entity;

use App\Model\Entity\Unit;
use App\Model\Entity\UnitTranslation;
use Doctrine\ORM\EntityRepository;
use Test\DbTestCase;
use Tester\Assert;

$container = require __DIR__ . '/../../bootstrap.php';

/**
 * TEST: Unit entity
 *
 * @testCase
 * @phpVersion 5.4
 */
class UnitTest extends DbTestCase
{
	
	/** @var EntityRepository */
	private $unitRepo;

	public function setUp()
	{
		parent::setUp();
		$this->updateSchema();
		$this->unitRepo = $this->em->getRepository(Unit::class);
	}

	public function tearDown()
	{
		$this->dropSchema();
		parent::tearDown();
	}

	public function testTranslations()
	{
		$entity = new Unit(Unit::PIECES, 'en');
		$entity->translate('cs')->name = 'Ks';
		$entity->mergeNewTranslations();
		
		$this->em->persist($entity);
		$this->em->flush();
		
		$id = $entity->id;
		
		$findedEntity = $this->unitRepo->find($id);
		
		Assert::same(Unit::PIECES, $findedEntity->name);
		Assert::same(Unit::PIECES, $findedEntity->translate('en')->name);
		Assert::same('Ks', $findedEntity->translate('cs')->name);
	}

	public function testFindByName()
	{
		$entity = new Unit(Unit::PIECES, 'en');
		$entity->translate('cs')->name = 'Ks';
		$entity->mergeNewTranslations();
		
		$this->em->persist($entity);
		$this->em->flush();
		
		$id = $entity->id;
		
		$findedEntities = $this->unitRepo->findByName(Unit::PIECES, 'en');
		$findedEntity = reset($findedEntities);
		
		Assert::same($id, $findedEntity->id);
		Assert::same(Unit::PIECES, $findedEntity->name);
		Assert::same(Unit::PIECES, $findedEntity->translate('en')->name);
		Assert::same('Ks', $findedEntity->translate('cs')->name);
	}

	protected function getClasses()
	{
		return [
			$this->em->getClassMetadata(Unit::getClassName()),
			$this->em->getClassMetadata(UnitTranslation::getClassName()),
		];
	}

}

$test = new UnitTest($container);
$test->run();
