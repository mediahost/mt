<?php

namespace App\Forms\Controls\ChoiseBased;

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
		foreach ($this->getItems() as $value => $label) {
			$ids[$value] = $input->id . '-' . $value;
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
				array('for:' => $ids, 'class' => $this->labelParsClass),
				$this->separator
			)
		);
	}

}
