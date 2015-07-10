<?php

namespace App\Components\Example\Form;

use App\Components\BaseControl;
use App\Forms\Controls\Custom\DatePicker;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Forms\Renderers\MetronicHorizontalFormRenderer;

/**
 * Form with all items for test rendering
 */
class ExampleForm extends BaseControl
{
	// <editor-fold desc="events">

	/** @var array */
	public $onAfterSave = [];

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());

		$values = [1 => 'test1', 2 => 'test2'];

		$form->addText('text', 'Text');
		$form->addText('phone', 'Phone')
				->setAttribute('class', 'mask_phone');
		$form->addText('date', 'Date')
				->setAttribute('class', 'mask_date');
		$form->addText('password', 'Password');
		$form->addCheckSwitch('checkswitch', 'Check Switch', 'On', 'Off')
				->setOffColor('danger')
				->setOnColor('success'); // default, info, success, warning, danger
		$form->addDateInput('dateinput', 'Date Input');
		$form->addDatePicker('datepicker', 'Date Picker')
				->setTodayHighlight()
				->setSize(DatePicker::SIZE_S);
		$form->addSpinner('spinner', 'Spinner')
				->setMin(10)
				->setMax(100)
				->setValue(50)
				->setSize(MetronicTextInputBase::SIZE_S);
		$form->addTouchSpin('touchspin', 'Touch Spin')
				->setButtonDownClass('btn red')
				->setButtonUpClass('btn green')
				->setPostfix('€')
				->setDecimals(2)
				->setValue(1)
				->setStep(0.1)
				->setMin(0)
				->setMax(2)
				->setSize(MetronicTextInputBase::SIZE_M);
		$form->addSlider('slider', 'Slider', [1 => 'N/A', 'One', 'Two', 'Tree'])
				->setColor('info')
				->setTooltip()
				->setPips();
		$form->addCheckbox('checkbox', 'Checkbox');
		$form->addCheckboxList('checkboxlist', 'Checkbox list', $values);
		$form->addCheckboxList('checkboxlistinline', 'Checkbox list inline', $values)
				->setInline();
		$form->addRadioList('radiolist', 'Radio list', $values);
		$form->addRadioList('radiolistinline', 'Radio list inline', $values)
				->setInline();
		$form->addSelect('select', 'Select', $values);
		$form->addSelect2('select2', 'Select 2', $values);
		$form->addMultiSelect('multiselect', 'Multi Select', $values);
		$form->addMultiSelect2('multiselect2', 'Multi Select2', $values);
		$form->addMultiSelectBoxes('multiselectboxes', 'Multi Select Boxes', $values);
		$form->addTagInput('tags', 'Tags')
				->setPlaceholder($this->translator->translate('add a tag'))
				->setValue('ahoj, ahoj2');
		$form->addTextArea('textarea', 'Textarea');
		$form->addWysiHtml('wysi', 'HTML');

		$form->addSubmit('save', 'Save');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->presenter->flashMessage('This is only example form', 'success');
		$this->onAfterSave($form, $values);
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [];
		return $values;
	}

	public function renderHorizontal($labelWidth = MetronicHorizontalFormRenderer::DEFAULT_LABEL_WIDTH, $inputWidth = MetronicHorizontalFormRenderer::DEFAULT_INPUT_WIDTH)
	{
		$this['form']->setRenderer(new MetronicHorizontalFormRenderer($labelWidth, $inputWidth));
		parent::render();
	}

}

interface IExampleFormFactory
{

	/** @return ExampleForm */
	function create();
}
