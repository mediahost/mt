<?php

namespace App\Forms\Controls\SelectBased;

use Nette\Forms\Controls\SelectBox;

class NoUiSlider extends SelectBox
{

	public function __construct($label = NULL, array $items = NULL)
	{
		parent::__construct($label, $items);
		$this->control->class = 'noUiSlider';
	}

	/**
	 * Set Slider color class
	 * @param type $color
	 * @return self
	 */
	public function setColor($color)
	{
		$attr = 'data-class';
		$this->control->$attr = 'noUi-' . $color;
		return $this;
	}

	/**
	 * Set Slider tooltip
	 * @param type $allow
	 * @return self
	 */
	public function setTooltip($allow = TRUE)
	{
		$attr = 'data-tooltip';
		$this->control->$attr = $allow ? 'true' : 'false';
		return $this;
	}

	/**
	 * Set Slider tooltip
	 * @param type $allow
	 * @return self
	 */
	public function setTooltipFixed($allow = TRUE)
	{
		$attr = 'data-tooltip-fixed';
		$this->control->$attr = $allow ? 'true' : 'false';
		return $this;
	}

	/**
	 * Set Pips under slider
	 * @param type $allow
	 * @return self
	 */
	public function setPips($allow = TRUE)
	{
		$attr = 'data-pips';
		$this->control->$attr = $allow ? 'true' : 'false';
		return $this;
	}

}
