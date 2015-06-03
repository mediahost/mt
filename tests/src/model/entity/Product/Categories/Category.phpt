<?php

namespace Test\Model\Entity;

use App\Model\Entity\Category;
use App\Model\Entity\CategoryTranslation;
use Doctrine\ORM\EntityRepository;
use Test\DbTestCase;
use Tester\Assert;

$container = require __DIR__ . '/../../../../bootstrap.php';

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

	public function testTranslations()
	{
		$entity = new Category('The Name', 'en');
		$entity->translate('cs')->name = 'Jméno';
		$entity->mergeNewTranslations();
		
		$this->em->persist($entity);
		$this->em->flush();
		
		$id = $entity->id;
		
		$findedEntity = $this->categoryRepo->find($id);
		
		Assert::same('The Name', $findedEntity->name);
		Assert::same('The Name', $findedEntity->translate('en')->name);
		Assert::same('Jméno', $findedEntity->translate('cs')->name);
	}

	public function testParentAndChildren()
	{
		$cat1 = new Category('category 1');
		$cat1->mergeNewTranslations();
		$cat2 = new Category('category 2');
		$cat1->mergeNewTranslations();
		$cat3 = new Category('category 3');
		$cat1->mergeNewTranslations();
		$this->em->persist($cat1);
		$this->em->persist($cat2);
		$this->em->persist($cat3);
		
		$entity = new Category('name');
		$entity->parent = $cat1;
		$entity->addChild($cat2);
		$entity->addChild($cat3);
		$entity->mergeNewTranslations();
		
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
		$cat1->mergeNewTranslations();
		$cat2->mergeNewTranslations();
		$cat3->mergeNewTranslations();
		$entity->mergeNewTranslations();
		
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

	public function testPathWithTranslate()
	{
		$cat1 = new Category('Category 1');
		$cat1->translate('cs')->name = 'Kategorie 1';
		$cat2 = new Category('Category 2');
		$cat2->translate('cs')->name = 'Kategorie 2';
		$cat3 = new Category('Category 3');
		$cat3->translate('cs')->name = 'Kategorie 3';
		$entity = new Category('The Name');
		$entity->translate('cs')->name = 'Jméno';
		$cat1->mergeNewTranslations();
		$cat2->mergeNewTranslations();
		$cat3->mergeNewTranslations();
		$entity->mergeNewTranslations();
		
		$cat1->addChild($cat2);
		$cat2->addChild($cat3);
		$cat3->addChild($entity);
		
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
		
		Assert::same('category-1', $findedCategory1->slug);
		Assert::same('category-2', $findedCategory2->slug);
		Assert::same('category-3', $findedCategory3->slug);
		Assert::same('the-name', $findedCategory4->slug);
		
		Assert::same('category-1', $findedCategory1->url);
		Assert::same('category-1/category-2', $findedCategory2->url);
		Assert::same('category-1/category-2/category-3', $findedCategory3->url);
		Assert::same('category-1/category-2/category-3/the-name', $findedCategory4->url);
		
		$findedCategory4->setCurrentLocale('cs');
		
		Assert::same('jmeno', $findedCategory4->slug);
		Assert::same('category-3', $findedCategory3->slug);
		Assert::same('kategorie-1/kategorie-2/kategorie-3/jmeno', $findedCategory4->url);
	}

	public function testIsInPath()
	{
		$cat1 = new Category();
		$cat2 = new Category();
		$cat3 = new Category();
		$entity = new Category();
		
		$cat2->parent = $cat1;
		$cat3->parent = $cat2;
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
		
		Assert::true($findedCategory4->isInPath($findedCategory1));
		Assert::true($findedCategory4->isInPath($findedCategory2));
		Assert::true($findedCategory4->isInPath($findedCategory3));
		Assert::true($findedCategory4->isInPath($findedCategory4));
		Assert::false($findedCategory3->isInPath($findedCategory4));
		Assert::true($findedCategory3->isInPath($findedCategory3));
		Assert::true($findedCategory3->isInPath($findedCategory2));
		Assert::true($findedCategory3->isInPath($findedCategory1));
		Assert::false($findedCategory2->isInPath($findedCategory4));
		Assert::false($findedCategory2->isInPath($findedCategory3));
		Assert::true($findedCategory2->isInPath($findedCategory2));
		Assert::true($findedCategory2->isInPath($findedCategory1));
		Assert::false($findedCategory1->isInPath($findedCategory4));
		Assert::false($findedCategory1->isInPath($findedCategory3));
		Assert::false($findedCategory1->isInPath($findedCategory2));
		Assert::true($findedCategory1->isInPath($findedCategory1));
	}

	protected function getClasses()
	{
		return [
			$this->em->getClassMetadata(Category::getClassName()),
			$this->em->getClassMetadata(CategoryTranslation::getClassName()),
		];
	}

}

$test = new CategoryTest($container);
$test->run();
