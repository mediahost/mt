<?php

namespace App\Forms\Controls\SelectBased;

use Nette\Application\UI\ISignalReceiver;
use Nette\Utils\Callback;

class ServerValidatedMultiSelectBoxes extends MultiSelectBoxes implements ISignalReceiver
{

	use \Nextras\Forms\Controls\Fragments\ComponentControlTrait;

	/** @var array */
	private $serverValidators = [];

	/**
	 * Add server validate rule
	 * @param type $validator
	 * @param type $message
	 * @param type $arg
	 * @return self
	 */
	public function addServerRule($validator, $message, $arg = NULL)
	{
		$this->addRule($validator, $message, $arg);
		$this->serverValidators[] = [$validator, $message, $arg];
		return $this;
	}

	public function getControl()
	{
		$el = parent::getControl();
		if ($this->serverValidators) {
			$rules = $el->{'data-nette-rules'};
			$rules[] = [
				'op' => 'serverValidate',
				'arg' => $this->link('//serverValidate!', ['value' => '__VALUE__']),
			];
			$el->{'data-nette-rules'} = $rules;
		}
		return $el;
	}

	public function handleServerValidate($value)
	{
		$values = explode(',', $value);
		$this->setValue($values);
		$payload = ['valid' => TRUE];
		foreach ($this->serverValidators as $validator) {
			if (!Callback::invoke($validator[0], $this, $validator[2])) {
				$payload = ['valid' => FALSE, 'msg' => sprintf($validator[1], $values)];
				break;
			}
		}
		$this->getPresenter()->sendJson($payload);
	}

}
