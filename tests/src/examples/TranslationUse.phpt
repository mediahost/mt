<?php

namespace Test\Examples;

use Kdyby\Doctrine\EntityDao;
use Nette\DI\Container;
use Test\Examples\Model\Entity\Article;
use Test\Examples\Model\Entity\ArticleTranslation;
use Tester\Assert;

$container = require __DIR__ . '/../bootstrap.php';

/**
 * TEST: Translation use
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
		$entity = new Article($this->getLanguageService()->language);
		
		$entity->translate('fr')->title = 'fabuleux';
        $entity->translate('en')->title = 'awesome';
        $entity->translate('cs')->title = 'pecka';
		$entity->mergeNewTranslations();
		
		$this->em->persist($entity);
		$this->em->flush();

		$entityFinded = $this->articleDao->find($entity->id);
		$entityFinded->setCurrentLocale($this->getLanguageService()->language);
		Assert::same('awesome', $entityFinded->title);
		Assert::same('fabuleux', $entityFinded->translate('fr')->title);
		Assert::same('awesome', $entityFinded->translate('en')->title);
		Assert::same('pecka', $entityFinded->translate('cs')->title);
		
		$entityFinded->setCurrentLocale('cs');
		Assert::same('pecka', $entityFinded->title);
	}

	public function testUseDefault()
	{
		$entity = new Article('en');
		
		// french isn't set
        $entity->translate('en')->title = 'awesome';
        $entity->translate('cs')->title = 'pecka';
		$entity->mergeNewTranslations();
		
		$this->em->persist($entity);
		$this->em->flush();
		
		$entityFinded = $this->articleDao->find($entity->id);
		$entityFinded->setCurrentLocale('fr');
		Assert::same('awesome', $entityFinded->title);
		Assert::same('awesome', $entityFinded->translate('fr')->title);
		Assert::same('awesome', $entityFinded->translate('en')->title);
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
