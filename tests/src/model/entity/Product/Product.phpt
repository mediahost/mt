<?php

namespace Test\Model\Entity;

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
		$defaultLanguage = $this->getLanguageService()->language;
		
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
		$defaultLanguage = $this->getLanguageService()->language;
		
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

}

$test = new ProductTest($container);
$test->run();
