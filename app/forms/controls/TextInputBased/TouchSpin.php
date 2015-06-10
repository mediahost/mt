<?php

namespace App\Forms\Controls\TextInputBased;

/**
 * http://www.virtuosoft.eu/code/bootstrap-touchspin/
 *
 * @method self setPrefix(string $value) Set text before the input - default none
 * @method self setPostfix(string $value) Set text after the input - default none
 * @method self setButtonDownClass(string $value) Set class to button down - default 'btn btn-default'
 * @method self setButtonUpClass(string $value) Set class to up down - default 'btn btn-default'
 * @method self setMin(string $value) Set minimum allowed value
 * @method self setMax(string $value) Set maximum allowed value
 * @method self setStep(string $value) Set value to step
 * @method self setDecimals(string $value) Set decimal numbers
 * @method self setBooster(string $value) If enabled, the the spinner is continually becoming faster as holding the button - default true
 * @method self setBoostat(string $value) Boost at every nth step - default 10
 * @method self setMaxBoostedStep(string $value) Maximum step when boosted - default false
 */
class TouchSpin extends MetronicTextInputBase
{

	public function __construct($label = NULL)
	{
		parent::__construct($label);
		$this->control->class = ['touchspin'];
		$this->dataAttributes = [
			'ButtonDownClass' => 'buttondown-class',
			'ButtonUpClass' => 'buttonup-class',
		];
	}

}
