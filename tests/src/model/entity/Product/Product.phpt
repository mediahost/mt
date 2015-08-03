<?php

namespace Test\Model\Entity;

use App\Model\Entity\BaseTranslatable;
use App\Model\Entity\Category;
use App\Model\Entity\Producer;
use App\Model\Entity\Product;
use Tester\Assert;

$container = require __DIR__ . '/../../../bootstrap.php';

/**
 * TEST: Product entity
 *
 * @testCase
 * @phpVersion 5.4
 */
class ProductTest extends ProductTestBase
{

	public function testTranslationManipulationWithoutLanguage()
	{
		$this->product = new Product();
		$this->product->name = self::NAME;
		$this->product->description = self::DESC;
		$this->product->perex = self::PEREX;
		$this->product->mergeNewTranslations();

		$this->saveProduct();

		Assert::same(self::NAME, $this->product->name);
		Assert::same(self::DESC, $this->product->description);
		Assert::same(self::PEREX, $this->product->perex);

		$this->product->setCurrentLocale('cs');

		Assert::same(self::NAME, $this->product->name);
		Assert::same(self::DESC, $this->product->description);
		Assert::same(self::PEREX, $this->product->perex);
	}

	public function testTranslationManipulationWithLanguage()
	{
		$defaultLanguage = BaseTranslatable::DEFAULT_LOCALE;

		$this->product = new Product($defaultLanguage);
		$this->product->name = self::NAME;
		$this->product->description = self::DESC;
		$this->product->perex = self::PEREX;

		$secondLanguage = 'cs';
		$this->product->translate($secondLanguage)->name = 'jméno';
		$this->product->translate($secondLanguage)->description = 'dlouhý popisek';
		$this->product->translate($secondLanguage)->perex = 'krátký popisek';

		$thirdLanguage = 'fr';
		$this->product->setCurrentLocale($thirdLanguage);
		$this->product->name = 'le jméno';
		$this->product->description = 'le dlouhý le popisek';
		$this->product->perex = 'le petit popisek';

		$this->product->mergeNewTranslations();

		$this->saveProduct();

		Assert::same(self::NAME, $this->product->name);
		Assert::same(self::DESC, $this->product->description);
		Assert::same(self::PEREX, $this->product->perex);

		$this->product->setCurrentLocale($secondLanguage);
		Assert::same('jméno', $this->product->name);
		Assert::same('dlouhý popisek', $this->product->description);
		Assert::same('krátký popisek', $this->product->perex);

		Assert::same('le jméno', $this->product->translate($thirdLanguage)->name);
		Assert::same('le dlouhý le popisek', $this->product->translate($thirdLanguage)->description);
		Assert::same('le petit popisek', $this->product->translate($thirdLanguage)->perex);

		$this->reloadProduct(); // reset current language

		Assert::same(self::NAME, $this->product->name);
	}

	public function testTranslationSeo()
	{
		$defaultLanguage = BaseTranslatable::DEFAULT_LOCALE;

		$this->product = new Product($defaultLanguage);
		$this->product->name = self::NAME;
		$this->product->seo->name = self::PEREX;
		$this->product->seo->description = self::DESC;

		$secondLanguage = 'cs';
		$this->product->setCurrentLocale($secondLanguage);
		$this->product->name = 'jméno';
		$this->product->seo->name = 'krátký popisek';
		$this->product->seo->description = 'dlouhý popisek';

		$this->product->mergeNewTranslations();

		$this->saveProduct();

		Assert::same(self::NAME, $this->product->name);
		Assert::same(self::PEREX, $this->product->seo->name);
		Assert::same(self::DESC, $this->product->seo->description);

		$this->product->setCurrentLocale($secondLanguage);
		Assert::same('jméno', $this->product->name);
		Assert::same('krátký popisek', $this->product->seo->name);
		Assert::same('dlouhý popisek', $this->product->seo->description);
	}

	public function testProducer()
	{
		$producer = new Producer('My Producer');
		$this->em->persist($producer);

		$this->product = new Product();
		$this->product->producer = $producer;

		$this->saveProduct();

		Assert::same('My Producer', (string) $this->product->producer);
	}

	public function testMainCategories()
	{
		$category = new Category('My Category');
		$this->em->persist($category);

		$this->product = new Product();
		$this->product->mainCategory = $category;

		$this->saveProduct();

		Assert::same('My Category', (string) $this->product->mainCategory);
	}

	public function testAddAndRemoveCategory()
	{
		$cat1 = new Category('My Category 1');
		$cat2 = new Category('My Category 2');
		$cat3 = new Category('My Category 3');
		$this->em->persist($cat1);
		$this->em->persist($cat2);
		$this->em->persist($cat3);

		$this->product = new Product();
		$this->product->addCategory($cat1);
		$this->product->addCategory($cat2);
		$this->product->addCategory($cat3);

		$this->saveProduct();

		Assert::same('My Category 1', (string) $this->product->mainCategory);
		Assert::count(3, $this->product->categories);

		$this->product->removeCategory($cat2);

		$this->saveProduct();

		Assert::count(2, $this->product->categories);
	}

	public function testAddCategories()
	{
		$cat1 = new Category('My Category 1');
		$cat2 = new Category('My Category 2');
		$cat3 = new Category('My Category 3');
		$cat4 = new Category('My Category 4');
		$cat5 = new Category('My Category 5');
		$this->em->persist($cat1);
		$this->em->persist($cat2);
		$this->em->persist($cat3);
		$this->em->persist($cat4);
		$this->em->persist($cat5);

		$this->product = new Product();
		$this->product->setCategories([$cat2, $cat1, $cat3]);

		$this->saveProduct();

		Assert::same('My Category 2', (string) $this->product->mainCategory);
		Assert::count(3, $this->product->categories);
		Assert::same('My Category 1', (string) $this->product->categories[0]);
		Assert::same('My Category 2', (string) $this->product->categories[1]);
		Assert::same('My Category 3', (string) $this->product->categories[2]);

		$this->product->setCategories([$cat4, $cat5]);
		$this->saveProduct();

		Assert::same('My Category 4', (string) $this->product->mainCategory);
		Assert::count(2, $this->product->categories);
		Assert::same('My Category 4', (string) $this->product->categories[0]);
		Assert::same('My Category 5', (string) $this->product->categories[1]);

		$this->product->setCategories([]);

		Assert::null($this->product->mainCategory);
		Assert::count(0, $this->product->categories);
	}
	
	public function testSimilars()
	{
		$product1 = new Product();
		$product2 = new Product();
		$product3 = new Product();
		$this->em->persist($product1);
		$this->em->persist($product2);
		$this->em->persist($product3);
		$this->em->flush();
		
		$product1->addSimilar($product2);
		$product1->addSimilar($product3);
		$this->em->persist($product1);
		$this->em->flush();
		
		$this->em->clear();
		$productRepo = $this->em->getRepository(Product::getClassName());
		
		$finded1 = $productRepo->find(1);
		Assert::count(2, $finded1->similars);
		Assert::count(0, $finded1->similarsWithMe);
		
		$finded2 = $productRepo->find(2);
		Assert::count(0, $finded2->similars);
		Assert::count(1, $finded2->similarsWithMe);
		
		$finded3 = $productRepo->find(3);
		Assert::count(0, $finded3->similars);
		Assert::count(1, $finded3->similarsWithMe);
	}

}

$test = new ProductTest($container);
$test->run();
