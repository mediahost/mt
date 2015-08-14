<?php

namespace App\Components\Basket\Form;

use App\Components\BaseControl;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Facade\BasketFacade;

class Payments extends BaseControl
{

	/** @var BasketFacade @inject */
	public $basketFacade;

	// <editor-fold desc="events">

	/** @var array */
	public $onAfterSave = [];

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());
		if ($this->isAjax) {
			$form->getElementPrototype()->class('ajax loadingNoOverlay sendOnChange');
		}

		$shippings = [
			1 => 'Osobně',
			2 => 'PPL',
			3 => 'DPD',
			4 => 'Česká Pošta',
		];
		$payments = [
			1 => 'Hotově',
			2 => 'Na dobírku',
			3 => 'Platba předem',
			4 => 'Kartou',
		];
		$form->addRadioList('shipping', 'cart.shipping', $shippings);
		$form->addRadioList('payment', 'cart.payment', $payments);

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		if ($this->presenter->isAjax()) {
			$this->redrawControl();
		}
		$this->onAfterSave();
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [];
		return $values;
	}

}

interface IPaymentsFactory
{

	/** @return Payments */
	function create();
}
