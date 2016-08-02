<?php

namespace App\Extensions\Products\Components;

use App\Components\BaseControl;
use App\Extensions\Products\ProductList;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;

class SortingForm extends BaseControl
{

	private $sorting;
	private $perPage;
	private $perPageList = [];

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

		$form->addSelect('sort', 'Sort by', $this->getSortingMethods())
			->setDefaultValue($this->sorting)
			->getControlPrototype()->class('input-sm category-selections-select');

		$perPage = $form->addSelect('perPage', 'Show', $this->getItemsForCountSelect())
			->getControlPrototype()->class('input-sm category-selections-select');
		$defaultPerPage = array_search($this->perPage, $this->perPageList);
		if ($defaultPerPage !== FALSE) {
			$perPage->setDefaultValue($this->perPage);
		}

		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->setSorting($values->sort);

		$key = array_search($values->perPage, $this->perPageList);
		if ($key !== FALSE) {
			$this->perPage = $key ? $values->perPage : NULL;
		}
		$this->onAfterSend($this->sorting, $this->perPage);
	}

	public function setSorting($value)
	{
		$this->sorting = $value;
		return $this;
	}

	public function setPerPage($perPage, array $perPageList)
	{
		$this->perPage = $perPage;
		$this->perPageList = $perPageList;
		return $this;
	}

	/** @return array */
	private function getSortingMethods()
	{
		return [
			ProductList::SORT_BY_PRICE_ASC => 'Price (Low > High)',
			ProductList::SORT_BY_PRICE_DESC => 'Price (High > Low)',
			ProductList::SORT_BY_NAME_ASC => 'Name (A - Z)',
			ProductList::SORT_BY_NAME_DESC => 'Name (Z - A)',
		];
	}

	/** @return array */
	private function getItemsForCountSelect()
	{
		return array_combine($this->perPageList, $this->perPageList);
	}

}

interface ISortingFormFactory
{

	/** @return SortingForm */
	function create();
}
