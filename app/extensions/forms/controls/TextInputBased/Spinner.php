<?php

namespace App\Forms\Controls\TextInputBased;

use Nette\Utils\Html;

class Spinner extends MetronicTextInputBase
{

	const TYPE_LEFT_RIGHT = 'left-right';
	const TYPE_UP_DOWN = 'up-down';

	private $attributes = array();
	private $size = self::SIZE_FLUID;
	private $type = self::TYPE_LEFT_RIGHT;
	private $readonly = TRUE;
	private $minusButtUp = FALSE;
	private $minusButtIcon = 'minus';
	private $minusButtColor = 'red';
	private $plusButtUp = TRUE;
	private $plusButtIcon = 'plus';
	private $plusButtColor = 'green';

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
	 * Set type of button printing
	 * @param string $type
	 * @return self
	 */
	public function setType($type = self::TYPE_LEFT_RIGHT)
	{
		$this->type = $type;
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
			$this->minusButtUp = TRUE;
		} else {
			$this->minusButtUp = FALSE;
		}
		$this->plusButtUp = !$this->minusButtUp;
		return $this;
	}

	/**
	 * Set color and icon for left button
	 * @param type $color
	 * @param type $faIcon
	 * @return self
	 */
	public function setMinusButton($color = NULL, $faIcon = NULL)
	{
		if ($color) {
			$this->minusButtColor = $color;
		}
		if ($faIcon) {
			$this->minusButtIcon = $faIcon;
		}
		return $this;
	}

	/**
	 * Set color and icon for right button
	 * @param type $color
	 * @param type $faIcon
	 * @return self
	 */
	public function setPlusButton($color = NULL, $faIcon = NULL)
	{
		if ($color) {
			$this->plusButtColor = $color;
		}
		if ($faIcon) {
			$this->plusButtIcon = $faIcon;
		}
		return $this;
	}

	// </editor-fold>

	/**
	 * Generates control's HTML element.
	 */
	public function getControl()
	{
		$vertical = Html::el('div')
				->class('spinner-buttons input-group-btn btn-group-vertical', TRUE)
				->class($this->readonly ? 'disabled' : '', TRUE)
				->add($this->getUpButton(FALSE))
				->add($this->getDownButton(FALSE));
		$block = Html::el('div')
				->class('input-group', TRUE)
				->class($this->size, TRUE);
		switch ($this->type) {
			case self::TYPE_LEFT_RIGHT:
				$block
						->add($this->getDownButton())
						->add($this->getInput())
						->add($this->getUpButton());
				break;
			case self::TYPE_UP_DOWN:
				$block
						->add($this->getInput())
						->add($vertical);
				break;
		}
		$formSpinner = Html::el('div class="form-spinner"')
						->add($block)
						->addAttributes($this->attributes);
		$errorBlock = Html::el('span class="help-block"')->setText($this->getError());
		return $formSpinner . ($this->hasErrors() ? $errorBlock : NULL);
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

	private function getDownButton($withWrap = TRUE)
	{
		$icon = Html::el('i')->class('fa');
		if ($this->minusButtIcon) {
			$icon->class('fa-' . $this->minusButtIcon, TRUE);
		}
		$button = Html::el('button type="button"')
				->class('btn ' . $this->minusButtColor)
				->class('spinner-' . ($this->minusButtUp ? 'up' : 'down'), TRUE)
				->add($icon);
		return $withWrap ? Html::el('div class="spinner-buttons input-group-btn"')
						->add($button) : $button;
	}

	private function getUpButton($withWrap = TRUE)
	{
		$icon = Html::el('i')->class('fa');
		if ($this->plusButtIcon) {
			$icon->class('fa-' . $this->plusButtIcon, TRUE);
		}
		$button = Html::el('button type="button"')
				->class('btn ' . $this->plusButtColor)
				->class('spinner-' . ($this->plusButtUp ? 'up' : 'down'), TRUE)
				->add($icon);
		return $withWrap ? Html::el('div class="spinner-buttons input-group-btn"')
						->add($button) : $button;
	}

	// </editor-fold>
}
