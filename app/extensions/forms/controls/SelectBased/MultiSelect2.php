<?php

namespace App\Forms\Controls\SelectBased;

class MultiSelect2 extends \Nette\Forms\Controls\MultiSelectBox
{

	/** @var bool */
	private $autocomplete = FALSE;

	public function __construct($label = NULL, array $items = NULL)
	{
		parent::__construct($label, $items);
		$this->control->class = ['select2'];
	}

	public function setAutocomplete($class = NULL)
	{
		$this->autocomplete = TRUE;
		$this->control->class[] = 'autocomplete';
		$this->control->class[] = $class;
		return $this;
	}

	public function getValue()
	{
		if ($this->autocomplete) {
			return array_values($this->value);
		} else {
			return parent::getValue();
		}
	}

}
