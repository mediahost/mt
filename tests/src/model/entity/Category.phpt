<?php

namespace Test\Model\Entity;

use App\Model\Entity\Category;
use Doctrine\ORM\EntityRepository;
use Test\DbTestCase;
use Tester\Assert;

$container = require __DIR__ . '/../../bootstrap.php';

/**
 * TEST: Category entity
 *
 * @testCase
 * @phpVersion 5.4
 */
class CategoryTest extends DbTestCase
{
	
	/** @var EntityRepository */
	private $categoryRepo;

	public function setUp()
	{
		parent::setUp();
		$this->updateSchema();
		$this->categoryRepo = $this->em->getRepository(Category::class);
	}

	public function tearDown()
	{
		$this->dropSchema();
		parent::tearDown();
	}

	public function testSetAndGet()
	{
		$cat1 = new Category('category 1');
		$cat2 = new Category('category 2');
		$cat3 = new Category('category 3');
		$this->em->persist($cat1);
		$this->em->persist($cat2);
		$this->em->persist($cat3);
		
		$entity = new Category('name');
		$entity->parent = $cat1;
		$entity->addChild($cat2);
		$entity->addChild($cat3);
		
		$this->em->persist($entity);
		$this->em->flush();
		
		$id = $entity->id;
		$parentId = $cat1->id;
		
		$findedEntity = $this->categoryRepo->find($id);
		
		Assert::same($id, $findedEntity->id);
		
		Assert::same($parentId, $findedEntity->parent->id);
		Assert::same('category 1', (string) $findedEntity->parent);
		Assert::null($findedEntity->parent->parent);
		
		Assert::count(2, $findedEntity->children);
		Assert::same('category 2', (string) $findedEntity->children[0]);
		Assert::same('category 3', $findedEntity->children[1]->name);
	}

	public function testPathAndUrl()
	{
		$cat1 = new Category('Category 1');
		$cat2 = new Category('Category 2');
		$cat3 = new Category('Category 3');
		$entity = new Category('The Name');
		
		$cat1->addChild($cat2);
		$cat2->addChild($cat3);
		$entity->parent = $cat3;
		
		$this->em->persist($cat1);
		$this->em->persist($cat2);
		$this->em->persist($cat3);
		
		$this->em->persist($entity);
		$this->em->flush();
		
		$id = $entity->id;
		$parentId = $cat3->id;
		$parentParentId = $cat2->id;
		$parentParentParentId = $cat1->id;
		
		$findedCategory1 = $this->categoryRepo->find($parentParentParentId);
		$findedCategory2 = $this->categoryRepo->find($parentParentId);
		$findedCategory3 = $this->categoryRepo->find($parentId);
		$findedCategory4 = $this->categoryRepo->find($id);
		
		Assert::same($id, $findedCategory4->id);
		Assert::same($parentId, $findedCategory4->parent->id);
		Assert::same($parentParentId, $findedCategory4->parent->parent->id);
		Assert::same($parentParentParentId, $findedCategory4->parent->parent->parent->id);
		Assert::null($findedCategory4->parent->parent->parent->parent);
		Assert::null($findedCategory1->parent);
		
		Assert::same('category-1', $findedCategory1->slug);
		Assert::same('category-2', $findedCategory2->slug);
		Assert::same('category-3', $findedCategory3->slug);
		Assert::same('the-name', $findedCategory4->slug);
		
		Assert::same('category-1', $findedCategory1->url);
		Assert::same('category-1/category-2', $findedCategory2->url);
		Assert::same('category-1/category-2/category-3', $findedCategory3->url);
		Assert::same('category-1/category-2/category-3/the-name', $findedCategory4->url);
	}

	protected function getClasses()
	{
		return [
			$this->em->getClassMetadata(Category::getClassName()),
		];
	}

}

$test = new CategoryTest($container);
$test->run();
