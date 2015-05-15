<?php

namespace App\Forms;

use App\Forms\Controls\ChoiseBased\CheckboxList;
use App\Forms\Controls\ChoiseBased\CheckSwitch;
use App\Forms\Controls\ChoiseBased\RadioList;
use App\Forms\Controls\Custom\DateInput;
use App\Forms\Controls\Custom\DatePicker;
use App\Forms\Controls\SelectBased\MultiSelect2;
use App\Forms\Controls\SelectBased\MultiSelectBoxes;
use App\Forms\Controls\SelectBased\NoUiRanger;
use App\Forms\Controls\SelectBased\NoUiSlider;
use App\Forms\Controls\SelectBased\Select2;
use App\Forms\Controls\SelectBased\ServerValidatedMultiSelectBoxes;
use App\Forms\Controls\SubmitBased\ImageWithPreview;
use App\Forms\Controls\TextAreaBased\WysiHtml;
use App\Forms\Controls\TextInputBased\ServerValidatedTextInput;
use App\Forms\Controls\TextInputBased\Spinner;
use App\Forms\Controls\TextInputBased\TagInput;
use App\Forms\Controls\TextInputBased\TouchSpin;
use App\Forms\Controls\UploadBased\UploadImageWithPreview;

trait AddControls
{
	// <editor-fold desc="Special controls">

	/**
	 * Adds text Input with server validation
	 * @return ServerValidatedTextInput
	 */
	public function addServerValidatedText($name, $label = NULL, $maxLength = NULL)
	{
		return $this[$name] = new ServerValidatedTextInput($label, $maxLength);
	}

	/**
	 * Add DateInput
	 * @param type $name
	 * @param type $caption
	 * @return DateInput
	 */
	public function addDateInput($name, $caption = NULL)
	{
		return $this[$name] = new DateInput($caption);
	}

	/**
	 * Add TagInput
	 * @param type $name
	 * @param type $caption
	 * @return TagInput
	 */
	public function addTagInput($name, $caption = NULL)
	{
		return $this[$name] = new TagInput($caption);
	}

	/**
	 * Add DatePicker
	 * @param type $name
	 * @param type $caption
	 * @return DatePicker
	 */
	public function addDatePicker($name, $caption = NULL)
	{
		return $this[$name] = new DatePicker($caption);
	}

	/**
	 * Add WysiHtml
	 * @param type $name
	 * @param type $caption
	 * @param type $rows
	 * @return WysiHtml
	 */
	public function addWysiHtml($name, $caption = NULL, $rows = NULL)
	{
		return $this[$name] = new WysiHtml($caption, $rows);
	}

	/**
	 * Add CheckSwitch
	 * @param type $name
	 * @param type $caption
	 * @param type $onText
	 * @param type $offText
	 * @return CheckSwitch
	 */
	public function addCheckSwitch($name, $caption = NULL, $onText = NULL, $offText = NULL)
	{
		return $this[$name] = new CheckSwitch($caption, $onText, $offText);
	}

	/**
	 * Add TouchSpin
	 * @param type $name
	 * @param type $caption
	 * @return TouchSpin
	 */
	public function addTouchSpin($name, $caption = NULL)
	{
		return $this[$name] = new TouchSpin($caption);
	}

	/**
	 * Add Spinner
	 * @param type $name
	 * @param type $caption
	 * @return Spinner
	 */
	public function addSpinner($name, $caption = NULL)
	{
		return $this[$name] = new Spinner($caption);
	}

	/**
	 * Add NoUiSlider
	 * @param type $name
	 * @param type $label
	 * @return NoUiSlider
	 */
	public function addSlider($name, $label = NULL, array $items = NULL)
	{
		return $this[$name] = new NoUiSlider($label, $items);
	}

	/**
	 * Add NoUiRanger
	 * @param type $name
	 * @param type $label
	 * @return NoUiRanger
	 */
	public function addRangeSlider($name, $label = NULL, array $items = NULL)
	{
		return $this[$name] = new NoUiRanger($label, $items);
	}

	/**
	 * Add Select2
	 * @param type $name
	 * @param type $label
	 * @return Select2
	 */
	public function addSelect2($name, $label = NULL, array $items = NULL)
	{
		return $this[$name] = new Select2($label, $items);
	}

	/**
	 * Add MultiSelect2
	 * @param type $name
	 * @param type $label
	 * @return MultiSelect2
	 */
	public function addMultiSelect2($name, $label = NULL, array $items = NULL)
	{
		return $this[$name] = new MultiSelect2($label, $items);
	}

	/**
	 * Adds MultiSelectBoxes with server validation
	 * @return ServerValidatedMultiSelectBoxes
	 */
	public function addServerMultiSelectBoxes($name, $label = NULL, array $items = NULL)
	{
		return $this[$name] = new ServerValidatedMultiSelectBoxes($label, $items);
	}

	/**
	 * Add MultiSelectBoxes
	 * @param type $name
	 * @param type $label
	 * @return MultiSelectBoxes
	 */
	public function addMultiSelectBoxes($name, $label = NULL, array $items = NULL)
	{
		return $this[$name] = new MultiSelectBoxes($label, $items);
	}

	/**
	 * Adds set of radio button controls to the form.
	 * @param  string  control name
	 * @param  string  label
	 * @param  array   options from which to choose
	 * @return RadioList
	 */
	public function addRadioList($name, $label = NULL, array $items = NULL)
	{
		return $this[$name] = new RadioList($label, $items);
	}

	/**
	 * Adds set of checkbox controls to the form.
	 * @return CheckboxList
	 */
	public function addCheckboxList($name, $label = NULL, array $items = NULL)
	{
		return $this[$name] = new CheckboxList($label, $items);
	}

	/**
	 * Adds file input for image with preview.
	 * @return UploadImageWithPreview
	 */
	public function addUploadImageWithPreview($name, $label = NULL, $multiple = FALSE)
	{
		return $this[$name] = new UploadImageWithPreview($label, $multiple);
	}

	// </editor-fold>
}
