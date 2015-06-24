<?php

namespace App\Forms\Controls\TextInputBased;

use Nette\Utils\Html;

class Spinner extends MetronicTextInputBase
{

	private $attributes = array();
	private $size = self::SIZE_FLUID;
	private $readonly = TRUE;
	private $leftButtUp = FALSE;
	private $leftButtIcon = 'minus';
	private $leftButtColor = 'red';
	private $rightButtUp = TRUE;
	private $rightButtIcon = 'plus';
	private $rightButtColor = 'green';

	public function __construct($label = NULL)
	{
		parent::__construct($label);
	}

	// <editor-fold desc="setters">

	/**
	 * Set size of main element
	 * @param string $size
	 * @return self
	 */
	public function setSize($size = self::SIZE_FLUID)
	{
		$this->size = $this->getStandardedSize($size);
		return $this;
	}

	/**
	 * Set readonly parameter
	 * @param type $value
	 * @return self
	 */
	public function setReadOnly($value = TRUE)
	{
		$this->readonly = $value;
		return $this;
	}

	/**
	 * Set disabled and set read only
	 * @param type $value
	 * @return self
	 */
	public function setDisabled($value = TRUE)
	{
		$this->attributes['data-disabled'] = $value ? 'true' : 'false';
		$this->setReadOnly($value);
		return parent::setDisabled($value);
	}

	/**
	 * Set Value for spinner and for input
	 * @param type $value
	 * @return self
	 */
	public function setValue($value)
	{
		$this->attributes['data-value'] = $value;
		return parent::setValue($value);
	}

	/**
	 * Set Minimum value
	 * @param type $value
	 * @return self
	 */
	public function setMin($value)
	{
		$this->attributes['data-min'] = $value;
		return $this;
	}

	/**
	 * Set maximum value
	 * @param type $value
	 * @return self
	 */
	public function setMax($value)
	{
		$this->attributes['data-max'] = $value;
		return $this;
	}

	/**
	 * Set step to move
	 * @param type $value
	 * @return self
	 */
	public function setStep($value)
	{
		$this->attributes['data-step'] = $value;
		return $this;
	}

	/**
	 * If TRUE then Up button is in the left
	 * @param type $inverse
	 * @return self
	 */
	public function setInverse($inverse = TRUE)
	{
		if ($inverse) {
			$this->leftButtUp = TRUE;
		} else {
			$this->leftButtUp = FALSE;
		}
		$this->rightButtUp = !$this->leftButtUp;
		return $this;
	}

	/**
	 * Set color and icon for left button
	 * @param type $color
	 * @param type $faIcon
	 * @return self
	 */
	public function setLeftButton($color = NULL, $faIcon = NULL)
	{
		if ($color) {
			$this->leftButtColor = $color;
		}
		if ($faIcon) {
			$this->leftButtIcon = $faIcon;
		}
		return $this;
	}

	/**
	 * Set color and icon for right button
	 * @param type $color
	 * @param type $faIcon
	 * @return self
	 */
	public function setRightButton($color = NULL, $faIcon = NULL)
	{
		if ($color) {
			$this->rightButtColor = $color;
		}
		if ($faIcon) {
			$this->rightButtIcon = $faIcon;
		}
		return $this;
	}

	// </editor-fold>

	/**
	 * Generates control's HTML element.
	 */
	public function getControl()
	{
		$block = Html::el('div')
				->class('input-group', TRUE)
				->class($this->size, TRUE)
				->add($this->getLeftButton())
				->add($this->getInput())
				->add($this->getRightButton());
		return Html::el('div class="form-spinner"')
						->add($block)
						->addAttributes($this->attributes);
	}

	// <editor-fold desc="controls for buttons">

	private function getInput()
	{
		$input = Html::el('input class="spinner-input form-control"')
				->name($this->getHtmlName())
				->id($this->getHtmlId())
				->value($this->getValue());
		if ($this->readonly) {
			$input->readonly('readonly');
		}
		return $input;
	}

	private function getLeftButton()
	{
		$icon = Html::el('i')->class('fa');
		if ($this->leftButtIcon) {
			$icon->class('fa-' . $this->leftButtIcon, TRUE);
		}
		$button = Html::el('button type="button"')
				->class('btn ' . $this->leftButtColor)
				->class('spinner-' . ($this->leftButtUp ? 'up' : 'down'), TRUE)
				->add($icon);
		return Html::el('div class="spinner-buttons input-group-btn"')
						->add($button);
	}

	private function getRightButton()
	{
		$icon = Html::el('i')->class('fa');
		if ($this->rightButtIcon) {
			$icon->class('fa-' . $this->rightButtIcon, TRUE);
		}
		$button = Html::el('button type="button"')
				->class('btn ' . $this->rightButtColor)
				->class('spinner-' . ($this->rightButtUp ? 'up' : 'down'), TRUE)
				->add($icon);
		return Html::el('div class="spinner-buttons input-group-btn"')
						->add($button);
	}

	// </editor-fold>
}
