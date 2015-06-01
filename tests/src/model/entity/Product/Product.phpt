<?php

namespace Test\Model\Entity;

use App\Model\Entity\Category;
use App\Model\Entity\Discount;
use App\Model\Entity\EntityException;
use App\Model\Entity\Group;
use App\Model\Entity\Parameter;
use App\Model\Entity\ParameterType;
use App\Model\Entity\ParameterValue;
use App\Model\Entity\Price;
use App\Model\Entity\Producer;
use App\Model\Entity\Product;
use App\Model\Entity\Tag;
use App\Model\Entity\Vat;
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

//	public function testTranslationManipulationWithoutLanguage()
//	{
//		$this->product = new Product();
//		$this->product->name = self::NAME;
//		$this->product->description = self::DESC;
//		$this->product->perex = self::PEREX;
//		$this->product->mergeNewTranslations();
//
//		$this->saveProduct();
//
//		Assert::same(self::NAME, $this->product->name);
//		Assert::same(self::DESC, $this->product->description);
//		Assert::same(self::PEREX, $this->product->perex);
//
//		$this->product->setCurrentLocale('cs');
//
//		Assert::same(self::NAME, $this->product->name);
//		Assert::same(self::DESC, $this->product->description);
//		Assert::same(self::PEREX, $this->product->perex);
//	}
//
//	public function testTranslationManipulationWithLanguage()
//	{
//		$defaultLanguage = $this->getLanguageService()->language;
//
//		$this->product = new Product($defaultLanguage);
//		$this->product->name = self::NAME;
//		$this->product->description = self::DESC;
//		$this->product->perex = self::PEREX;
//
//		$secondLanguage = 'cs';
//		$this->product->translate($secondLanguage)->name = 'jméno';
//		$this->product->translate($secondLanguage)->description = 'dlouhý popisek';
//		$this->product->translate($secondLanguage)->perex = 'krátký popisek';
//
//		$thirdLanguage = 'fr';
//		$this->product->setCurrentLocale($thirdLanguage);
//		$this->product->name = 'le jméno';
//		$this->product->description = 'le dlouhý le popisek';
//		$this->product->perex = 'le petit popisek';
//
//		$this->product->mergeNewTranslations();
//
//		$this->saveProduct();
//
//		Assert::same(self::NAME, $this->product->name);
//		Assert::same(self::DESC, $this->product->description);
//		Assert::same(self::PEREX, $this->product->perex);
//
//		$this->product->setCurrentLocale($secondLanguage);
//		Assert::same('jméno', $this->product->name);
//		Assert::same('dlouhý popisek', $this->product->description);
//		Assert::same('krátký popisek', $this->product->perex);
//
//		Assert::same('le jméno', $this->product->translate($thirdLanguage)->name);
//		Assert::same('le dlouhý le popisek', $this->product->translate($thirdLanguage)->description);
//		Assert::same('le petit popisek', $this->product->translate($thirdLanguage)->perex);
//
//		$this->reloadProduct(); // reset current language
//
//		Assert::same(self::NAME, $this->product->name);
//	}
//
//	public function testTranslationSeo()
//	{
//		$defaultLanguage = $this->getLanguageService()->language;
//
//		$this->product = new Product($defaultLanguage);
//		$this->product->name = self::NAME;
//		$this->product->seo->name = self::PEREX;
//		$this->product->seo->description = self::DESC;
//
//		$secondLanguage = 'cs';
//		$this->product->setCurrentLocale($secondLanguage);
//		$this->product->name = 'jméno';
//		$this->product->seo->name = 'krátký popisek';
//		$this->product->seo->description = 'dlouhý popisek';
//
//		$this->product->mergeNewTranslations();
//
//		$this->saveProduct();
//
//		Assert::same(self::NAME, $this->product->name);
//		Assert::same(self::PEREX, $this->product->seo->name);
//		Assert::same(self::DESC, $this->product->seo->description);
//
//		$this->product->setCurrentLocale($secondLanguage);
//		Assert::same('jméno', $this->product->name);
//		Assert::same('krátký popisek', $this->product->seo->name);
//		Assert::same('dlouhý popisek', $this->product->seo->description);
//	}
//
//	public function testProducer()
//	{
//		$producer = new Producer('My Producer');
//		$this->em->persist($producer);
//
//		$this->product = new Product();
//		$this->product->producer = $producer;
//
//		$this->saveProduct();
//
//		Assert::same('My Producer', (string) $this->product->producer);
//	}
//
//	public function testMainCategories()
//	{
//		$category = new Category('My Category');
//		$this->em->persist($category);
//
//		$this->product = new Product();
//		$this->product->mainCategory = $category;
//
//		$this->saveProduct();
//
//		Assert::same('My Category', (string) $this->product->mainCategory);
//	}
//
//	public function testAddAndRemoveCategory()
//	{
//		$cat1 = new Category('My Category 1');
//		$cat2 = new Category('My Category 2');
//		$cat3 = new Category('My Category 3');
//		$this->em->persist($cat1);
//		$this->em->persist($cat2);
//		$this->em->persist($cat3);
//
//		$this->product = new Product();
//		$this->product->addCategory($cat1);
//		$this->product->addCategory($cat2);
//		$this->product->addCategory($cat3);
//
//		$this->saveProduct();
//
//		Assert::same('My Category 1', (string) $this->product->mainCategory);
//		Assert::count(3, $this->product->categories);
//
//		$this->product->removeCategory($cat2);
//
//		$this->saveProduct();
//
//		Assert::count(2, $this->product->categories);
//	}
//
//	public function testAddCategories()
//	{
//		$cat1 = new Category('My Category 1');
//		$cat2 = new Category('My Category 2');
//		$cat3 = new Category('My Category 3');
//		$cat4 = new Category('My Category 4');
//		$cat5 = new Category('My Category 5');
//		$this->em->persist($cat1);
//		$this->em->persist($cat2);
//		$this->em->persist($cat3);
//		$this->em->persist($cat4);
//		$this->em->persist($cat5);
//
//		$this->product = new Product();
//		$this->product->setCategories([$cat2, $cat1, $cat3]);
//
//		$this->saveProduct();
//
//		Assert::same('My Category 2', (string) $this->product->mainCategory);
//		Assert::count(3, $this->product->categories);
//		Assert::same('My Category 1', (string) $this->product->categories[0]);
//		Assert::same('My Category 2', (string) $this->product->categories[1]);
//		Assert::same('My Category 3', (string) $this->product->categories[2]);
//
//		$this->product->setCategories([$cat4, $cat5]);
//		$this->saveProduct();
//
//		Assert::same('My Category 4', (string) $this->product->mainCategory);
//		Assert::count(2, $this->product->categories);
//		Assert::same('My Category 4', (string) $this->product->categories[0]);
//		Assert::same('My Category 5', (string) $this->product->categories[1]);
//
//		$this->product->setCategories([]);
//
//		Assert::null($this->product->mainCategory);
//		Assert::count(0, $this->product->categories);
//	}
//
//	public function testTagsAndSigns()
//	{
//		$tag1 = new Tag('tag one');
//		$tag2 = new Tag('tag two');
//		$tag3 = new Tag('tag three');
//		$tag4 = new Tag('tag four');
//		$sign1 = new Tag('sign one');
//		$sign1->type = Tag::TYPE_SIGN;
//		$sign2 = new Tag('sign two');
//		$sign2->type = Tag::TYPE_SIGN;
//
//		$this->em->persist($tag1);
//		$this->em->persist($tag2);
//		$this->em->persist($tag3);
//		$this->em->persist($tag4);
//		$this->em->persist($sign1);
//		$this->em->persist($sign2);
//
//		$this->product = new Product();
//		$this->product->setTags([$tag1, $tag2]);
//		$this->product->setSigns([$sign1]);
//		$this->saveProduct();
//
//		Assert::count(2, $this->product->tags);
//		Assert::count(1, $this->product->signs);
//
//		$this->product->addTag($tag3);
//		$this->product->setSigns([]);
//		$this->saveProduct();
//
//		Assert::count(3, $this->product->tags);
//		Assert::count(0, $this->product->signs);
//
//		$this->product->setTags([$tag4]);
//		$this->product->addSign($sign2);
//		$this->saveProduct();
//
//		Assert::count(1, $this->product->tags);
//		Assert::count(1, $this->product->signs);
//
//		$this->product->setTags([]);
//		$this->product->setSigns([$sign1, $sign2]);
//		$this->saveProduct();
//
//		Assert::count(0, $this->product->tags);
//		Assert::count(2, $this->product->signs);
//	}
//
//	public function testParameters()
//	{
//		$paramType1 = new ParameterType('param type 1');
//		$paramType1->mergeNewTranslations();
//		$paramType2 = new ParameterType('param type 2');
//		$paramType2->mergeNewTranslations();
//		$paramType3 = new ParameterType('param type 3');
//		$paramType3->mergeNewTranslations();
//		$this->em->persist($paramType1);
//		$this->em->persist($paramType2);
//		$this->em->persist($paramType3);
//
//		$paramValue1a = new ParameterValue('my value 1a', $paramType1);
//		$paramValue1a->mergeNewTranslations();
//		$paramValue1b = new ParameterValue('my value 1b', $paramType1);
//		$paramValue1b->mergeNewTranslations();
//		$paramValue2a = new ParameterValue('my value 2a', $paramType2);
//		$paramValue2a->mergeNewTranslations();
//		$paramValue2b = new ParameterValue('my value 2b', $paramType2);
//		$paramValue2b->mergeNewTranslations();
//		$paramValue3a = new ParameterValue('my value 3a', $paramType3);
//		$paramValue3a->mergeNewTranslations();
//		$paramValue3b = new ParameterValue('my value 3b', $paramType3);
//		$paramValue3b->mergeNewTranslations();
//		$this->em->persist($paramValue1a);
//		$this->em->persist($paramValue1b);
//		$this->em->persist($paramValue2a);
//		$this->em->persist($paramValue2b);
//		$this->em->persist($paramValue3a);
//		$this->em->persist($paramValue3b);
//
//		$parameter1 = new Parameter($paramType1, $paramValue1a);
//		$parameter2 = new Parameter($paramType2, $paramValue2a);
//		$parameter3 = new Parameter($paramType3, $paramValue3a);
//		$this->em->persist($parameter1);
//		$this->em->persist($parameter2);
//		$this->em->persist($parameter3);
//
//		$this->product = new Product();
//		$this->product->setParameters([$parameter1, $parameter2]);
//		$this->saveProduct();
//
//		Assert::count(2, $this->product->parameters);
//
//		$this->product->addParameter($parameter3);
//		$this->saveProduct();
//
//		Assert::count(3, $this->product->parameters);
//		Assert::same('param type 1', (string) $this->product->parameters[0]->type);
//		Assert::same('my value 1a', (string) $this->product->parameters[0]->value);
//		Assert::same('param type 2', (string) $this->product->parameters[1]->type);
//		Assert::same('my value 2a', (string) $this->product->parameters[1]->value);
//
//		$parameterToChange1 = $this->product->parameters[0];
//		$parameterToChange1->value = $paramValue1b;
//		$this->em->persist($parameterToChange1);
//		$this->em->flush();
//
//		$this->reloadProduct();
//
//		Assert::same('param type 1', (string) $this->product->parameters[0]->type);
//		Assert::same('my value 1b', (string) $this->product->parameters[0]->value);
//
//		$parameterToChange2 = $this->product->parameters[0];
//		Assert::exception(function () use ($parameterToChange2, $paramValue2b) {
//			$parameterToChange2->value = $paramValue2b;
//		}, EntityException::class);
//	}
//
//	public function testPrices()
//	{
//		$vat = new Vat('20');
//		$this->em->persist($vat);
//		
//		$group1 = new Group('first');
//		$group2 = new Group('second');
//		$group3 = new Group('third');
//		$this->em->persist($group1);
//		$this->em->persist($group2);
//		$this->em->persist($group3);
//		
//		$this->em->flush();
//		
//		$discount1 = new Discount(5);
//		$discount2 = new Discount(30, Discount::MINUS_VALUE);
//		$discount3 = new Discount(90, Discount::FIXED_PRICE);
//		
//		$this->product = new Product();
//		$this->product->price = new Price($vat, 100);
//		$this->product->addDiscount($discount1, $group1);
//		$this->product->addDiscount($discount2, $group2);
//		$this->product->addDiscount($discount3, $group3);
//		$this->saveProduct();
//		
//		Assert::same((float) 100, $this->product->price->withoutVat);
//		Assert::same((float) 120, $this->product->price->withVat);
//		
//		Assert::same((float) 95, $this->product->getPrice($group1)->withoutVat);
//		Assert::same((float) 114, $this->product->getPrice($group1)->withVat);
//		Assert::same((float) 70, $this->product->getPrice($group2)->withoutVat);
//		Assert::same((float) 84, $this->product->getPrice($group2)->withVat);
//		Assert::same((float) 90, $this->product->getPrice($group3)->withoutVat);
//		Assert::same((float) 108, $this->product->getPrice($group3)->withVat);
//		
//		$discount4 = new Discount();
//		$discount4->type = Discount::PERCENTAGE;
//		$discount4->value = 15;
//		$this->product->addDiscount($discount4, $group3);
//		$this->saveProduct();
//		
//		Assert::same((float) 85, $this->product->getPrice($group3)->withoutVat);
//		Assert::same((float) 102, $this->product->getPrice($group3)->withVat);
//	}
	
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
