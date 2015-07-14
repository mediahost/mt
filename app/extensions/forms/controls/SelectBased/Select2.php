<?php

namespace App\Forms\Controls\SelectBased;

use Nette\Forms\Controls\SelectBox;

class Select2 extends SelectBox
{

	public function __construct($label = NULL, array $items = NULL)
	{
		parent::__construct($label, $items);
		$this->control->class = ['select2'];
	}

	/**
	 *
	 * @param type $prompt
	 * @return self
	 */
	public function setPrompt($prompt)
	{
		$this->setPlaceholder($prompt);
		return parent::setPrompt('');
	}

	/**
	 * @deprecated Use setPrompt() instead
	 * @param type $value
	 * @return self
	 */
	public function setPlaceholder($value)
	{
		$attr = 'data-placeholder';
		$this->control->$attr = $this->translate($value);
		return $this;
	}

	/**
	 * Disables or enables control.
	 * @param  bool
	 * @return self
	 */
	public function setDisabled($value = TRUE)
	{
		$attr = 'data-disabled';
		$this->control->$attr = $value ? 'disabled' : FALSE;
		return $this;
	}

}
