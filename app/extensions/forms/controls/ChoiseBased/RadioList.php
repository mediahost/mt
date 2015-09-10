<?php

namespace App\Forms\Controls\ChoiseBased;

use App\Helpers as AppHelpers;
use Nette\Forms\Controls\ChoiceControl;
use Nette\Forms\Controls\RadioList as RadioListParent;
use Nette\Forms\Helpers;
use Nette\Utils\Html;

/**
 * Rewrited Radiolist
 */
class RadioList extends RadioListParent
{
	
	private $labelParsClass = NULL;
	
	public function setInline()
	{
		$this->labelParsClass = 'radio-inline';
	}
	
	public function setLabelPartsClass($class)
	{
		$this->labelParsClass = $class;
	}

	/**
	 * Generates control's HTML element.
	 * @return Html
	 */
	public function getControl($key = NULL)
	{
		if ($key !== NULL) {
			trigger_error(sprintf('Partial %s() is deprecated; use getControlPart() instead.', __METHOD__), E_USER_DEPRECATED);
			return $this->getControlPart($key);
		}

		$input = ChoiceControl::getControl();
		$ids = array();
		$labelClass = array();
		foreach ($this->getItems() as $value => $label) {
			$ids[$value] = $input->id . '-' . $value;
			$activeClass = $this->value === $value ? 'active' : NULL;
			$disabledClass = is_array($this->disabled) && array_key_exists($value, $this->disabled) && $this->disabled[$value] ? 'disabled' : NULL;
			$labelClass[$value] = AppHelpers::concatStrings(' ', $this->labelParsClass, $activeClass, $disabledClass);
		}
		
		return $this->container->setHtml(
			Helpers::createInputList(
				$this->translate($this->getItems()),
				array_merge($input->attrs, array(
					'id:' => $ids,
					'checked?' => $this->value,
					'disabled:' => $this->disabled,
					'data-nette-rules:' => array(key($ids) => $input->attrs['data-nette-rules']),
				)),
				array('for:' => $ids, 'class:' => $labelClass),
				$this->separator
			)
		);
	}

}
