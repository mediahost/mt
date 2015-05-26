<?php

namespace Test\Model\Entity;

use App\Model\Entity\Tag;
use App\Model\Entity\TagTranslation;
use Doctrine\ORM\EntityRepository;
use Test\DbTestCase;
use Tester\Assert;

$container = require __DIR__ . '/../../../../bootstrap.php';

/**
 * TEST: Tag entity
 *
 * @testCase
 * @phpVersion 5.4
 */
class TagTest extends DbTestCase
{
	
	/** @var EntityRepository */
	private $tagRepo;

	public function setUp()
	{
		parent::setUp();
		$this->updateSchema();
		$this->tagRepo = $this->em->getRepository(Tag::class);
	}

	public function tearDown()
	{
		$this->dropSchema();
		parent::tearDown();
	}

	public function testTranslations()
	{
		$entity = new Tag('The Name', 'en');
		$entity->translate('cs')->name = 'Jméno';
		$entity->mergeNewTranslations();
		
		$this->em->persist($entity);
		$this->em->flush();
		
		$id = $entity->id;
		
		$findedEntity = $this->tagRepo->find($id);
		
		Assert::same('The Name', $findedEntity->name);
		Assert::same('The Name', $findedEntity->translate('en')->name);
		Assert::same('Jméno', $findedEntity->translate('cs')->name);
	}

	protected function getClasses()
	{
		return [
			$this->em->getClassMetadata(Tag::getClassName()),
			$this->em->getClassMetadata(TagTranslation::getClassName()),
		];
	}

}

$test = new TagTest($container);
$test->run();
