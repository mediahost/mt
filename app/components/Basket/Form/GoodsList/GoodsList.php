<?php

namespace App\Components\Basket\Form;

use App\Components\BaseControl;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Controls\TextInputBased\Spinner;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Stock;
use App\Model\Facade\BasketFacade;
use App\Model\Facade\Exception\InsufficientQuantityException;

class GoodsList extends BaseControl
{

	const MAX = 100;
	const MIN = 1;

	/** @var BasketFacade @inject */
	public $basketFacade;

	// <editor-fold desc="events">

	/** @var array */
	public $onSend = [];

	// </editor-fold>

	/** @var int */
	private $max = self::MAX;

	/** @var int */
	protected $priceLevel;

	/** @return Form */
	protected function createComponentForm()
	{
		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());
		if ($this->isAjax) {
			$form->getElementPrototype()->class('ajax loadingNoOverlay' . ($this->isSendOnChange ? ' sendOnChange' : NULL));
		}

		$quantityContainer = $form->addContainer('quantity');
		foreach ($this->basketFacade->getItems() as $item) {
			$quantityContainer->addSpinner($item->stock->id)
					->setValue($item->quantity)
					->setPlusButton('default', 'fa fa-angle-up')
					->setMinusButton('default', 'fa fa-angle-down')
					->setMin(self::MIN)
					->setMax($this->max)
					->setType(Spinner::TYPE_UP_DOWN)
					->setSize(MetronicTextInputBase::SIZE_XS);
		}

		$form->addSubmit('send', 'cart.continue')
				->setDisabled(!$this->basketFacade->isAllItemsInStore());

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
//		$form->onValidate[] = $this->formValidate;
		return $form;
	}

	public function formValidate(Form $form, $values)
	{
		$stockRepo = $this->em->getRepository(Stock::getClassName());
		foreach ($values->quantity as $stockId => $quantity) {
			$stock = $stockRepo->find($stockId);
			if ($stock && isset($form['quantity'][$stockId])) {
				if ($stock->inStore < $quantity) {
					$errorMessage = $this->translator->translate('cart.countInStore', ['count' => $stock->inStore, 'unit' => $stock->product->unit]);
					$form['quantity'][$stockId]->addError($errorMessage);
				}
			}
		}
		if ($this->presenter->ajax) {
			$this->presenter->redrawControl();
		}
	}

	public function formSucceeded(Form $form, $values)
	{
		$stockRepo = $this->em->getRepository(Stock::getClassName());
		foreach ($values->quantity as $stockId => $quantity) {
			$stock = $stockRepo->find($stockId);
			if ($stock && isset($form['quantity'][$stockId])) {
				try {
					$this->basketFacade->setQuantity($stock, $quantity);
				} catch (InsufficientQuantityException $e) {
					$errorMessage = $this->translator->translate('cart.countInStore', ['count' => $stock->inStore, 'unit' => $stock->product->unit]);
					$form['quantity'][$stockId]->addError($errorMessage);
					$form['quantity'][$stockId]->setValue($stock->inStore);
				}
			}
		}

		if (!$form->hasErrors() && $form['send']->submittedBy) {
			$this->onSend();
		} else {
			if ($this->presenter->ajax) {
				$this->presenter->redrawControl();
			}
		}
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [];
		return $values;
	}

	public function render()
	{
		$this->template->basket = $this->basketFacade;
		$this->template->priceLevel = $this->priceLevel;
		$this->template->exchange = $this->exchange;
		parent::render();
	}

	// <editor-fold desc="setters & getters">

	public function setPriceLevel($level)
	{
		$this->priceLevel = $level;
		return $this;
	}

	// </editor-fold>
}

interface IGoodsListFactory
{

	/** @return GoodsList */
	function create();
}
