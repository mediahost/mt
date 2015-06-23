<?php

namespace Test\Model\Entity;

use App\Model\Entity\Sign;
use App\Model\Entity\SignTranslation;
use App\Model\Repository\SignRepository;
use Test\DbTestCase;
use Tester\Assert;

$container = require __DIR__ . '/../../../../bootstrap.php';

/**
 * TEST: Sign entity
 *
 * @testCase
 * @phpVersion 5.4
 */
class SignTest extends DbTestCase
{
	
	/** @var SignRepository */
	private $signRepo;

	public function setUp()
	{
		parent::setUp();
		$this->updateSchema();
		$this->signRepo = $this->em->getRepository(Sign::class);
	}

	public function tearDown()
	{
		$this->dropSchema();
		parent::tearDown();
	}

	public function testTranslations()
	{
		$entity = new Sign('The Name', 'en');
		$entity->translate('cs')->name = 'Jméno';
		$entity->mergeNewTranslations();
		
		$this->em->persist($entity);
		$this->em->flush();
		
		$id = $entity->id;
		
		$findedEntity = $this->signRepo->find($id);
		
		Assert::same('The Name', $findedEntity->name);
		Assert::same('The Name', $findedEntity->translate('en')->name);
		Assert::same('Jméno', $findedEntity->translate('cs')->name);
	}

	protected function getClasses()
	{
		return [
			$this->em->getClassMetadata(Sign::getClassName()),
			$this->em->getClassMetadata(SignTranslation::getClassName()),
		];
	}

}

$test = new SignTest($container);
$test->run();
