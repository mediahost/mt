<?php

namespace Test\Model\Entity;

use App\Extensions\Settings\Model\Service\LanguageService;
use App\Extensions\Settings\Model\Storage\DefaultSettingsStorage;
use App\Model\Entity\Category;
use App\Model\Entity\CategoryTranslation;
use App\Model\Entity\Discount;
use App\Model\Entity\Group;
use App\Model\Entity\GroupDiscount;
use App\Model\Entity\Parameter;
use App\Model\Entity\ParameterType;
use App\Model\Entity\ParameterTypeTranslation;
use App\Model\Entity\ParameterValue;
use App\Model\Entity\ParameterValueTranslation;
use App\Model\Entity\Price;
use App\Model\Entity\Producer;
use App\Model\Entity\Product;
use App\Model\Entity\ProductSeo;
use App\Model\Entity\ProductTranslation;
use App\Model\Entity\Stock;
use App\Model\Entity\Tag;
use App\Model\Entity\TagTranslation;
use App\Model\Entity\Vat;
use App\Model\Repository\ProductRepository;
use App\Model\Repository\StockRepository;
use Nette\DI\Container;
use Test\DbTestCase;

abstract class ProductTestBase extends DbTestCase
{

	const NAME = 'my product name';
	const DESC = 'my longer text as description';
	const PEREX = 'my longer text as perex';

	/** @var Product */
	protected $product;

	/** @var Stock */
	protected $stock;

	/** @var ProductRepository */
	protected $productRepo;

	/** @var StockRepository */
	protected $stockRepo;

	/** @var DefaultSettingsStorage */
	protected $defaultSettings;

	/** @var LanguageService */
	protected $languageService;

	/** @var array */
	private $logs = [];

	public function __construct(Container $container)
	{
		parent::__construct($container);

		$this->productRepo = $this->em->getRepository(Product::getClassName());
		$this->stockRepo = $this->em->getRepository(Stock::getClassName());
		
		$this->defaultSettings = new DefaultSettingsStorage();

		$loggedSubscriber = $this->getContainer()->getService('loggableSubscriber');
		$loggedSubscriber->setLoggerCallable([$this, 'log']);
	}

	public function setUp()
	{
		parent::setUp();
		$this->updateSchema();
	}

	public function tearDown()
	{
		$this->dropSchema();
		parent::tearDown();
	}

	protected function getLanguageService()
	{
		if (!$this->languageService) {
			$this->defaultSettings->setLanguages([
				'default' => 'en',
				'allowed' => ['en' => 'English', 'fr' => 'French', 'cs' => 'Czech'],
			]);
			$this->languageService = new LanguageService();
			$this->languageService->defaultStorage = $this->defaultSettings;
			$this->languageService->em = $this->em;
		}
		return $this->languageService;
	}

	protected function saveProduct($safePersist = FALSE)
	{
		if ($safePersist) {
			$this->em->safePersist($this->product);
			$this->em->flush();
		} else {
			$this->productRepo->save($this->product);
		}
		$this->reloadProduct();
		return $this;
	}

	protected function reloadProduct()
	{
		$this->em->detach($this->product);
		$this->product = $this->productRepo->find($this->product->id);
		return $this;
	}

	protected function saveStock($safePersist = FALSE)
	{
		if ($safePersist) {
			$this->em->safePersist($this->stock);
			$this->em->flush();
		} else {
			$this->stockRepo->save($this->stock);
		}
		$this->reloadStock();
		return $this;
	}

	protected function reloadStock()
	{
		$this->em->detach($this->stock);
		$this->stock = $this->stockRepo->find($this->stock->id);
		return $this;
	}

	public function log($message)
	{
		$this->logs[] = $message;
	}

	protected function getClasses()
	{
		return [
			$this->em->getClassMetadata(Product::getClassName()),
			$this->em->getClassMetadata(ProductTranslation::getClassName()),
			$this->em->getClassMetadata(Stock::getClassName()),
			$this->em->getClassMetadata(ProductSeo::getClassName()),
			$this->em->getClassMetadata(Price::getClassName()),
			$this->em->getClassMetadata(Vat::getClassName()),
			$this->em->getClassMetadata(Discount::getClassName()),
			$this->em->getClassMetadata(Category::getClassName()),
			$this->em->getClassMetadata(CategoryTranslation::getClassName()),
			$this->em->getClassMetadata(Producer::getClassName()),
			$this->em->getClassMetadata(Tag::getClassName()),
			$this->em->getClassMetadata(TagTranslation::getClassName()),
			$this->em->getClassMetadata(Parameter::getClassName()),
			$this->em->getClassMetadata(ParameterType::getClassName()),
			$this->em->getClassMetadata(ParameterTypeTranslation::getClassName()),
			$this->em->getClassMetadata(ParameterValue::getClassName()),
			$this->em->getClassMetadata(ParameterValueTranslation::getClassName()),
			$this->em->getClassMetadata(Group::getClassName()),
			$this->em->getClassMetadata(GroupDiscount::getClassName()),
		];
	}

}
