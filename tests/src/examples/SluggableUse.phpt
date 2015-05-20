<?php

namespace Test\Examples;

use Kdyby\Doctrine\EntityRepository;
use Nette\DI\Container;
use Nette\Utils\DateTime;
use Test\Examples\Model\Entity\Sluggable;
use Tester\Assert;

$container = require __DIR__ . '/../bootstrap.php';

/**
 * TEST: Sluggable use
 * https://github.com/KnpLabs/DoctrineBehaviors/blob/master/tests/Knp/DoctrineBehaviors/ORM/SluggableTest.php
 *
 * @testCase
 * @phpVersion 5.4
 */
class SluggableUseTest extends BaseUse
{

	/** @var EntityRepository */
	private $sluggRepo;

	public function __construct(Container $container)
	{
		parent::__construct($container);
		$this->sluggRepo = $this->em->getRepository(Sluggable::getClassName());
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

	public function testSlugLoading()
	{
		$entity = new Sluggable();

		$entity->name = 'The name';

		$this->em->persist($entity);
		$this->em->flush();

		$id = $entity->id;
		Assert::notSame(NULL, $entity->id);

		$this->em->clear();

		$findedEntity = $this->sluggRepo->find($id);

		Assert::notSame(NULL, $findedEntity);
		Assert::same('the-name', $findedEntity->slug);
	}

	public function testNotUpdatedSlug()
	{
		$data = [
			[
				'slug' => 'the-name',
				'name' => 'The name',
			],
			[
				'slug' => 'loic-rene',
				'name' => 'Löic & René',
			],
			[
				'slug' => 'chateauneuf-du-pape',
				'name' => 'Châteauneuf du Pape'
			],
			[
				'slug' => 'prilis-zlutoucky-kun-upel-dabelske-ody',
				'name' => 'Příliš žluťoučký kůň úpěl ďábelské ódy'
			],
			[
				'slug' => 'pattyzdnove-vlcata-nervozne-stekaju-na-mojho-datla-v-trni',
				'name' => 'Päťtýždňové vĺčatá nervózne štekajú na môjho ďatľa v tŕní'
			],
		];
		foreach ($data as $row) {
			$entity = new Sluggable();
			
			$entity->name = $row['name'];
			
			$this->em->persist($entity);
			$this->em->flush();
			
			$entity->setDate(new DateTime());
			
			$this->em->persist($entity);
			$this->em->flush();
			
			Assert::same($row['slug'], $entity->slug);
		}
	}
	
	public function testUpdatedSlug()
	{
        $entity = new Sluggable();
		
        $entity->name = 'The name';
		
        $this->em->persist($entity);
        $this->em->flush();
		
        Assert::same('the-name', $entity->slug);
		
        $entity->name = 'The name 2';
		
        $this->em->persist($entity);
        $this->em->flush();
		
        Assert::same('the-name-2', $entity->slug);
	}

	protected function getClasses()
	{
		return [
			$this->em->getClassMetadata(Sluggable::getClassName()),
		];
	}

}

$test = new SluggableUseTest($container);
$test->run();
