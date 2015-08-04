<?php

namespace App\Forms\Controls\TextInputBased;

use Nette\Application\UI\ISignalReceiver;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Callback;

/**
 * Server validated TextInput
 * From: https://www.youtube.com/watch?v=Yc1-_lvZVZs
 */
class ServerValidatedTextInput extends TextInput implements ISignalReceiver
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
		$this->setValue($value);
		$payload = ['valid' => TRUE];
		foreach ($this->serverValidators as $validator) {
			if (!Callback::invoke($validator[0], $this, $validator[2])) {
				$payload = ['valid' => FALSE, 'msg' => $this->translator->translate($validator[1], ['value' => $value])];
				break;
			}
		}
		$this->getPresenter()->sendJson($payload);
	}

}
