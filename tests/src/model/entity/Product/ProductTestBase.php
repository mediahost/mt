<?php

namespace Test\Model\Entity;

use App\Model\Entity;
use App\Model\Facade\RoleFacade;
use App\Model\Repository\ProductRepository;
use App\Model\Repository\StockRepository;
use Nette\DI\Container;
use Test\DbTestCase;

abstract class ProductTestBase extends DbTestCase
{

	const NAME = 'my product name';
	const DESC = 'my longer text as description';
	const PEREX = 'my longer text as perex';

	/** @var RoleFacade @inject */
	public $roleFacade;

	/** @var Entity\Product */
	protected $product;

	/** @var Entity\Stock */
	protected $stock;

	/** @var ProductRepository */
	protected $productRepo;

	/** @var StockRepository */
	protected $stockRepo;

	/** @var array */
	private $logs = [];

	public function __construct(Container $container)
	{
		parent::__construct($container);

		$this->productRepo = $this->em->getRepository(Entity\Product::getClassName());
		$this->stockRepo = $this->em->getRepository(Entity\Stock::getClassName());

		$loggedSubscriber = $this->getContainer()->getService('loggableSubscriber');
		$loggedSubscriber->setLoggerCallable([$this, 'log']);
	}

	public function setUp()
	{
		parent::setUp();
		$this->updateSchema();
		$this->roleFacade->create(Entity\Role::GUEST);
	}

	public function tearDown()
	{
		$this->dropSchema();
		parent::tearDown();
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
			$this->em->getClassMetadata(Entity\Product::getClassName()),
			$this->em->getClassMetadata(Entity\ProductTranslation::getClassName()),
			$this->em->getClassMetadata(Entity\Stock::getClassName()),
			$this->em->getClassMetadata(Entity\Vat::getClassName()),
			$this->em->getClassMetadata(Entity\Discount::getClassName()),
			$this->em->getClassMetadata(Entity\Category::getClassName()),
			$this->em->getClassMetadata(Entity\CategoryTranslation::getClassName()),
			$this->em->getClassMetadata(Entity\Producer::getClassName()),
			$this->em->getClassMetadata(Entity\Sign::getClassName()),
			$this->em->getClassMetadata(Entity\SignTranslation::getClassName()),
			$this->em->getClassMetadata(Entity\Parameter::getClassName()),
			$this->em->getClassMetadata(Entity\ParameterTranslation::getClassName()),
			$this->em->getClassMetadata(Entity\Group::getClassName()),
			$this->em->getClassMetadata(Entity\GroupDiscount::getClassName()),
			$this->em->getClassMetadata(Entity\Unit::getClassName()),
			$this->em->getClassMetadata(Entity\UnitTranslation::getClassName()),
			$this->em->getClassMetadata(Entity\Role::getClassName()),
		];
	}

}
