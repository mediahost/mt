<?php

namespace App\Components\Product\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Model\Entity\Sign;
use App\Model\Entity\Stock;
use App\Model\Facade\BasketFacade;
use App\Model\Facade\Exception\InsufficientQuantityException;
use Nette\Utils\Random;

class PrintStock extends BaseControl
{

	/** @var BasketFacade @inject */
	public $basketFacade;

	/** @var Stock */
	private $stock;

	/** @var int */
	private $priceLevel = NULL;

	/** @var bool */
	private $showLabels = TRUE;

	/** @var bool */
	private $showSecondImage = TRUE;

	/** @var array */
	private $mainClasses = [
		'product',
	];

	public function handleAddToCart($showLabels = TRUE, $showSecondImage = TRUE, $mainClasses = NULL)
	{
		// simulate persistent parameters
		$this->showLabels = $showLabels;
		$this->showSecondImage = $showSecondImage;
		$this->mainClasses = [$mainClasses];

		try {
			if (!$this->stock) {
				throw new BaseControlException();
			}
			$this->basketFacade->add($this->stock);
			$this->flashMessage($this->translator->translate('cart.product.added'), 'success');
		} catch (InsufficientQuantityException $ex) {
			$this->flashMessage($this->translator->translate('cart.product.youCannotAdd'), 'warning');
		} catch (BaseControlException $ex) {
			$this->flashMessage($this->translator->translate('cart.product.youCannotAdd'), 'warning');
		}

		if ($this->presenter->isAjax()) {
			$this->redrawControl();
			$this->presenter->redrawControl();
			if (isset($this->presenter['products'])) {
				$this->presenter['products']->redrawControl();
			}
		} else {
			$this->redirect('this');
		}
	}

	// <editor-fold defaultstate="collapsed" desc="template">

	protected function loadTemplateSigns()
	{
		$signs = $this->settings->modules->signs;
		if ($signs->enabled) {
			$signRepo = $this->em->getRepository(Sign::getClassName());

			$new = $signRepo->find($signs->values->new);
			$new->setCurrentLocale($this->translator->getLocale());

			$sale = $signRepo->find($signs->values->sale);
			$sale->setCurrentLocale($this->translator->getLocale());

			$top = $signRepo->find($signs->values->top);
			$top->setCurrentLocale($this->translator->getLocale());

			$special = $signRepo->find($signs->values->special);
			$special->setCurrentLocale($this->translator->getLocale());

			$this->template->newSign = $new;
			$this->template->saleSign = $sale;
			$this->template->topSign = $top;
			$this->template->specialSign = $special;
		}
	}

	public function render()
	{
		$this->template->id = $this->stock->id . '-' . Random::generate();
		$this->template->stock = $this->stock;
		$this->template->basket = $this->basketFacade;
		$this->template->priceLevel = $this->priceLevel;
		$this->template->showLabels = $this->showLabels;
		$this->template->showSecondImage = $this->showSecondImage;
		$this->template->mainClasses = implode(' ', $this->mainClasses);
		$this->loadTemplateSigns();
		parent::render();
	}

	public function renderVertical()
	{
		$this->setTemplateFile('vertical');
		$this->render();
	}

	public function renderHorizontal()
	{
		$this->setTemplateFile('horizontal');
		$this->render();
	}

	// </editor-fold>
	// <editor-fold desc="setters & getters">

	public function setStockById($stockId)
	{
		$stockRepo = $this->em->getRepository(Stock::getClassName());
		$stock = $stockRepo->find($stockId);
		if ($stock) {
			$this->setStock($stock);
		}
		return $this;
	}

	public function setStock(Stock $stock)
	{
		$this->stock = $stock;
		$this->stock->product->setCurrentLocale($this->translator->getLocale());
		return $this;
	}

	public function setPriceLevel($level)
	{
		$this->priceLevel = $level;
		return $this;
	}

	public function setMainClasses(array $classes)
	{
		$this->mainClasses = $classes;
		return $this;
	}

	public function setShowLabels($show = TRUE)
	{
		$this->showLabels = $show;
		return $this;
	}

	public function setShowSecondImage($show = TRUE)
	{
		$this->showSecondImage = $show;
		return $this;
	}

	// </editor-fold>
}

interface IPrintStockFactory
{

	/** @return PrintStock */
	function create();
}
