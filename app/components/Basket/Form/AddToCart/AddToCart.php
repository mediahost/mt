<?php

namespace App\Components\Basket\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Controls\TextInputBased\Spinner;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Stock;
use App\Model\Facade\BasketFacade;
use App\Model\Facade\Exception\InsufficientQuantityException;

class AddToCart extends BaseControl
{

	const MAX = 100;
	const MIN = 1;

	/** @var BasketFacade @inject */
	public $basketFacade;

	// <editor-fold desc="events">

	/** @var array */
	public $onAfterAdd = [];

	// </editor-fold>

	/** @var Stock */
	private $stock;

	/** @var int */
	private $alreadyInBasket;

	/** @var int */
	private $canAddToBasket;

	/** @var int */
	private $max = self::MAX;

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());
		if ($this->isAjax) {
			$form->getElementPrototype()->class('ajax loadingNoOverlay');
		}

		$form->addSpinner('quantity')
				->setDisabled($this->isDisabled())
				->setPlusButton('default', 'fa fa-angle-up')
				->setMinusButton('default', 'fa fa-angle-down')
				->setMin(self::MIN)
				->setMax($this->max)
				->setType(Spinner::TYPE_UP_DOWN)
				->setSize(MetronicTextInputBase::SIZE_XS);

		$form->addSubmit('add', 'Add to cart')
				->setDisabled($this->isDisabled());

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		try {
			$quantity = isset($values->quantity) ? $values->quantity : 0;
			$newQuantity = $this->basketFacade->add($this->stock, $quantity);
			$this->alreadyInBasket = $newQuantity;
			if ($this->alreadyInBasket === $this->stock->inStore) {
				$form['add']->setDisabled();
				$form->addError($this->translator->translate('cart.product.reachedMaxCount'));
			}
			if ($this->presenter->ajax) {
				$this->redrawControl();
			}
			$this->onAfterAdd($this->stock, $newQuantity);
		} catch (InsufficientQuantityException $e) {
			$message1 = $this->translator->translate('cart.product.onlyCountOnStore', $this->stock->inStore);
			$message2 = $this->translator->translate('cart.product.alreadyInBasket', $this->alreadyInBasket);
			$message3 = $this->canAddToBasket ?
					$this->translator->translate('cart.product.youCanAddOnly', $this->canAddToBasket) :
					$this->translator->translate('cart.product.youCannotAdd');
			$form->addError($message1);
			$form->addError($message2);
			$form->addError($message3);
			if ($this->presenter->ajax) {
				$this->redrawControl();
			}
		}
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [];
		return $values;
	}

	private function checkEntityExistsBeforeRender()
	{
		if (!$this->stock) {
			throw new BaseControlException('Use setStock(\App\Model\Entity\Stock) before render');
		}
	}

	public function render()
	{
		$this->template->alreadyInBasket = $this->alreadyInBasket;
		parent::render();
	}

	// <editor-fold desc="setters & getters">

	public function setStock(Stock $stock)
	{
		$this->stock = $stock;
		$this->alreadyInBasket = $this->basketFacade->getCountInBasket($this->stock);
		$this->canAddToBasket = $this->basketFacade->getCountAllowedToAdd($this->stock);
		$this->max = $this->canAddToBasket > self::MAX ? self::MAX : $this->canAddToBasket;
		return $this;
	}

	public function isDisabled()
	{
		return $this->canAddToBasket <= 0;
	}

	// </editor-fold>
}

interface IAddToCartFactory
{

	/** @return AddToCart */
	function create();
}
