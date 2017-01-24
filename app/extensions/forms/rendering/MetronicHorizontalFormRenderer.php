<?php

namespace App\Forms\Renderers;

use App\Forms\Controls\ChoiseBased\CheckSwitch;
use Nette\Forms\Controls\Checkbox;
use Nette\Forms\Form;
use Nette\Utils\Html;

class MetronicHorizontalFormRenderer extends MetronicFormRenderer
{
	const DEFAULT_LABEL_WIDTH = '3';
	const DEFAULT_INPUT_WIDTH = '9';

	public $labelWidth;
	public $inputWidth;

	public function __construct($labelWidth = self::DEFAULT_LABEL_WIDTH, $inputWidth = self::DEFAULT_INPUT_WIDTH)
	{
		parent::__construct();
		$this->setLabelWidth($labelWidth)
				->setInputWidth($inputWidth);
		$this->initWrapper();
	}

	private function setLabelWidth($width)
	{
		$this->labelWidth = (string) $width;
		return $this;
	}

	private function setInputWidth($width)
	{
		$this->inputWidth = (string) $width;
		return $this;
	}

	protected function initWrapper()
	{
		parent::initWrapper();
		$wrapper = 'div class="col-md-' . $this->inputWidth . '"';
		$wrapperWithOffset = 'div class="col-md-offset-' . $this->labelWidth . ' col-md-' . $this->inputWidth . '"';
		$wrapperWithCheckboxlist = 'div class="col-md-' . $this->inputWidth . ' checkbox-list"';
		$this->wrappers['control']['container'] = $wrapper;
		$this->wrappers['control.checkboxlist']['container'] = $wrapperWithCheckboxlist;
		$this->wrappers['control.checkbox']['container'] = $wrapperWithOffset;
		$this->wrappers['control.submit']['container'] = $wrapperWithOffset;
	}

	protected function customizeInitedForm(Form &$form)
	{
		parent::customizeInitedForm($form);

		$form->getElementPrototype()->class('form-horizontal', TRUE);

		$usedPrimary = FALSE;
		foreach ($form->getControls() as $control) {
			
			$this->customizeStandardControl($control, $usedPrimary);
			
			if ($control->getLabelPrototype() instanceof Html && !($control instanceof Checkbox && !$control instanceof CheckSwitch)) {
				$control->getLabelPrototype()->class('col-md-' . $this->labelWidth, TRUE);
			}
		}
	}

}
