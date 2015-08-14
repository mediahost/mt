<?php

namespace App\Components\Basket\Form;

use App\Components\BaseControl;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Facade\BasketFacade;

class Personal extends BaseControl
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
			$form->getElementPrototype()->class('ajax loadingNoOverlay');
		}

		$form->addText('name', 'cart.address.name');

		$form->addSubmit('save', 'cart.continue');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->onAfterSave();
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [];
		return $values;
	}

}

interface IPersonalFactory
{

	/** @return Personal */
	function create();
}
