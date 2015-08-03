<?php

namespace Test\Examples;

use App\Model\Entity\BaseTranslatable;
use Kdyby\Doctrine\EntityDao;
use Nette\DI\Container;
use Test\Examples\Model\Entity\Article;
use Test\Examples\Model\Entity\ArticleTranslation;
use Tester\Assert;

$container = require __DIR__ . '/../bootstrap.php';

/**
 * TEST: Translation use
 * https://github.com/KnpLabs/DoctrineBehaviors/blob/master/tests/Knp/DoctrineBehaviors/ORM/TranslatableTest.php
 *
 * @testCase
 * @phpVersion 5.4
 */
class TranslationUseTest extends BaseUse
{

	/** @var EntityDao */
	private $articleDao;

	public function __construct(Container $container)
	{
		parent::__construct($container);
		$this->articleDao = $this->em->getDao(Article::getClassName());
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

	public function testBaseUse()
	{
		$entity = new Article(BaseTranslatable::DEFAULT_LOCALE);
		
		$entity->translate('fr')->title = 'fabuleux';
        $entity->translate(BaseTranslatable::DEFAULT_LOCALE)->title = 'awesome';
        $entity->translate('cs')->title = 'pecka';
		$entity->mergeNewTranslations();
		
		$this->em->persist($entity);
		$this->em->flush();

		$entityFinded = $this->articleDao->find($entity->id);
		$entityFinded->setCurrentLocale(BaseTranslatable::DEFAULT_LOCALE);
		Assert::same('awesome', $entityFinded->title);
		Assert::same('fabuleux', $entityFinded->translate('fr')->title);
		Assert::same('awesome', $entityFinded->translate(BaseTranslatable::DEFAULT_LOCALE)->title);
		Assert::same('pecka', $entityFinded->translate('cs')->title);
		
		$entityFinded->setCurrentLocale('cs');
		Assert::same('pecka', $entityFinded->title);
	}

	public function testUseDefault()
	{
		$entity = new Article(BaseTranslatable::DEFAULT_LOCALE);
		
		// french isn't set
        $entity->translate(BaseTranslatable::DEFAULT_LOCALE)->title = 'awesome';
        $entity->translate('cs')->title = 'pecka';
		$entity->mergeNewTranslations();
		
		$this->em->persist($entity);
		$this->em->flush();
		
		/* @var $entityFinded Article */
		$entityFinded = $this->articleDao->find($entity->id);
		$entityFinded->setCurrentLocale('fr');
		Assert::same('awesome', $entityFinded->title);
		Assert::same('awesome', $entityFinded->translate('fr')->title);
		Assert::same('awesome', $entityFinded->translate(BaseTranslatable::DEFAULT_LOCALE)->title);
		Assert::same('pecka', $entityFinded->translate('cs')->title);
	}

	protected function getClasses()
	{
		return [
			$this->em->getClassMetadata(Article::getClassName()),
			$this->em->getClassMetadata(ArticleTranslation::getClassName()),
		];
	}

}

$test = new TranslationUseTest($container);
$test->run();
