<?php

namespace App\Forms\Renderers;

use App\Forms\Controls\ChoiseBased\CheckSwitch;
use Nette\Forms\Controls\Checkbox;
use Nette\Forms\Controls\CheckboxList;
use Nette\Forms\Form;
use Nette\Forms\IControl;
use Nette\Forms\Rendering\DefaultFormRenderer;
use Nette\InvalidArgumentException;
use Nette\Utils\Html;

/**
 * Extended about new abstract functions
 */
class ExtendedFormRenderer extends DefaultFormRenderer
{

	public function __construct()
	{
		$this->wrappers['control.submit']['container'] = $this->wrappers['control']['container'];
		$this->wrappers['control.checkbox']['container'] = $this->wrappers['control']['container'];
		$this->wrappers['control.checkboxlist']['container'] = $this->wrappers['control']['container'];
	}

	// <editor-fold desc="customize functions">

	/**
	 * After initializating form
	 */
	protected function customizeInitedForm(Form &$form)
	{

	}

	// </editor-fold>
	// <editor-fold desc="overrrides functions">

	/**
	 * Provides complete form rendering.
	 * @param  Form
	 * @param  string 'begin', 'errors', 'ownerrors', 'body', 'end' or empty to render all
	 * @return string
	 */
	public function render(Form $form, $mode = NULL)
	{
		if ($this->form !== $form) {
			$this->form = $form;
		}

		// START EDIT
		$this->customizeInitedForm($this->form);
		// END EDIT

		$s = '';
		if (!$mode || $mode === 'begin') {
			$s .= $this->renderBegin();
		}
		if (!$mode || strtolower($mode) === 'ownerrors') {
			$s .= $this->renderErrors();
		} elseif ($mode === 'errors') {
			$s .= $this->renderErrors(NULL, FALSE);
		}
		if (!$mode || $mode === 'body') {
			$s .= $this->renderBody();
		}
		if (!$mode || $mode === 'end') {
			$s .= $this->renderEnd();
		}
		return $s;
	}

	/**
	 * Renders single visual row of multiple controls.
	 * @param  IFormControl[]
	 * @return string
	 */
	public function renderPairMulti(array $controls)
	{
		$s = array();
		foreach ($controls as $control) {
			if (!$control instanceof IControl) {
				throw new InvalidArgumentException('Argument must be array of Nette\Forms\IControl instances.');
			}
			$description = $control->getOption('description');
			if ($description instanceof Html) {
				$description = ' ' . $control->getOption('description');
			} elseif (is_string($description)) {
				$description = ' ' . $this->getWrapper('control description')->setText($control->translate($description));
			} else {
				$description = '';
			}

			$control->setOption('rendered', TRUE);
			$el = $control->getControl();
			if ($el instanceof Html && $el->getName() === 'input') {
				$el->class($this->getValue("control .$el->type"), TRUE);
			}
			$s[] = $el . $description;
		}
		$pair = $this->getWrapper('pair container');
		$pair->add($this->renderLabel($control));
		// START EDIT
		$pair->add($this->getWrapper('control.submit container')->setHtml(implode(' ', $s)));
		// END EDIT
		return $pair->render(0);
	}

	/**
	 * Renders 'control' part of visual row of controls.
	 * @return string
	 */
	public function renderControl(IControl $control)
	{
		// START EDIT
		if ($control instanceof Checkbox && !$control instanceof CheckSwitch) {
			$body = $this->getWrapper('control.checkbox container');
		} else if ($control instanceof CheckboxList) {
			$body = $this->getWrapper('control.checkboxlist container');
		} else {
			$body = $this->getWrapper('control container');
		}
		// END EDIT
		if ($this->counter % 2) {
			$body->class($this->getValue('control .odd'), TRUE);
		}

		$description = $control->getOption('description');
		if ($description instanceof Html) {
			$description = ' ' . $description;
		} elseif (is_string($description)) {
			$description = ' ' . $this->getWrapper('control description')->setText($control->translate($description));
		} else {
			$description = '';
		}

		if ($control->isRequired()) {
			$description = $this->getValue('control requiredsuffix') . $description;
		}

		$control->setOption('rendered', TRUE);
		$el = $control->getControl();
		if ($el instanceof Html && $el->getName() === 'input') {
			$el->class($this->getValue("control .$el->type"), TRUE);
		}
		return $body->setHtml($el . $description . $this->renderErrors($control));
	}

	// </editor-fold>

	public function renderPureLabel(IControl $control)
	{
		$suffix = $this->getValue('label suffix') . ($control->isRequired() ? $this->getValue('label requiredsuffix') : '');
		$label = $control->getLabel();
		if ($label instanceof Html) {
			$label->add($suffix);
			if ($control->isRequired()) {
				$label->class($this->getValue('control .required'), TRUE);
			}
		} elseif ($label != NULL) { // @intentionally ==
			$label .= $suffix;
		}
		return $label;
	}

}
