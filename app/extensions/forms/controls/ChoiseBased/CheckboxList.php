<?php

namespace App\Forms\Controls\ChoiseBased;

use Nette\Forms\Controls\CheckboxList as CheckboxListParent;
use Nette\Forms\Controls\MultiChoiceControl;
use Nette\Forms\Helpers;

/**
 * Rewrited CheckboxList
 */
class CheckboxList extends CheckboxListParent
{
	
	private $labelParsClass = NULL;
	
	public function setInline()
	{
		$this->labelParsClass = 'checkbox-inline';
		return $this;
	}
	
	public function setLabelPartsClass($class)
	{
		$this->labelParsClass = $class;
		return $this;
	}

	/**
	 * Generates control's HTML element.
	 * @return string
	 */
	public function getControl()
	{
		$items = $this->getItems();
		reset($items);
		$input = MultiChoiceControl::getControl();
		return Helpers::createInputList(
			$this->translate($items),
			array_merge($input->attrs, array(
				'id' => NULL,
				'checked?' => $this->value,
				'disabled:' => $this->disabled,
				'required' => NULL,
				'data-nette-rules:' => array(key($items) => $input->attrs['data-nette-rules']),
			)),
			array('class' => $this->labelParsClass),
			$this->separator
		);
	}

}
