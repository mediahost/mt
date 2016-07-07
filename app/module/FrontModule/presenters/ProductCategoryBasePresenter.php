<?php

namespace App\FrontModule\Presenters;

use App\BaseModule\Presenters\BasePresenter as BaseBasePresenter;
use App\Components\Auth\ISignInFactory;
use App\Components\Auth\SignIn;
use App\Components\Newsletter\Form\ISubscribeFactory;
use App\Components\Newsletter\Form\Subscribe;
use App\Components\Producer\Form\IModelSelectorFactory;
use App\Components\Producer\Form\ModelSelector;
use App\Components\Product\Form\IPrintStockFactory;
use App\Extensions\Products\IProductListFactory;
use App\Extensions\Products\ProductList;
use App\Forms\Form;
use App\Helpers;
use App\Model\Entity\Category;
use App\Model\Entity\Page;
use App\Model\Entity\Producer;
use App\Model\Entity\ProducerModel;
use App\Model\Entity\Product;
use App\Model\Entity\Sign;
use App\Model\Entity\Stock;
use App\Model\Entity\Voucher;
use App\Model\Facade\CategoryFacade;
use App\Model\Repository\CategoryRepository;
use App\Model\Repository\ProductRepository;
use App\Model\Repository\StockRepository;
use Nette\Application\UI\Multiplier;
use Nette\Utils\ArrayHash;

abstract class ProductCategoryBasePresenter extends BasePresenter
{

	/**
	 * Active category ID
	 * @var int @persistent
	 */
	public $c;

	/** @var Category */
	protected $activeCategory;

	protected function setActiveCategory(Category $category)
	{
		$this->activeCategory = $category;
		$this->c = $category->id;
	}

	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->activeCategory = $this->activeCategory;
	}

}
