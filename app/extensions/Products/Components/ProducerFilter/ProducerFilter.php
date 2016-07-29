<?php

namespace App\Extensions\Products\Components;

use App\Components\BaseControl;
use App\Extensions\Products\ProductList;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;

class ProducerFilter extends BaseControl
{

	/** @var array */
	public $onAfterSend = [];

	/** @return Form */
	protected function createComponentForm()
	{
		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());
		$form->getElementPrototype()->class = [
			!$this->isSendOnChange ?: 'sendOnChange',
			!$this->isAjax ?: 'ajax'
		];

		$form->addSelect('model', 'Model', [])
//			->setDefaultValue($this->sorting)
			->getControlPrototype()->class('input-sm category-selections-select');

		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
//		$this->setSorting($values->sort);
//		$key = array_search($values->perPage, $this->perPageList);
//		if ($key !== FALSE) {
//			$this->perPage = $key ? $values->perPage : NULL;
//		}
//		$this->onAfterSend($this->sorting, $this->perPage);
	}

}

interface IProducerFilterFactory
{

	/** @return ProducerFilter */
	function create();
}
