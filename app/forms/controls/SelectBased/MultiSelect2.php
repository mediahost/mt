<?php

namespace App\Forms\Controls\SelectBased;

class MultiSelect2 extends \Nette\Forms\Controls\MultiSelectBox
{

	public function __construct($label = NULL, array $items = NULL)
	{
		parent::__construct($label, $items);
		$this->control->class = 'select2';
	}

}
