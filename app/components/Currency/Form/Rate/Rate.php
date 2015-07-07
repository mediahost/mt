<?php

namespace App\Components\Currency\Form;

use App\Components\BaseControl;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity;
use h4kuna\Exchange\Currency\Property;
use h4kuna\Exchange\Exchange;

class Rate extends BaseControl
{

	/** @var Exchange @inject */
	public $exchange;

	// <editor-fold desc="events">

	/** @var array */
	public $onAfterSave = [];

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer);

		$defaultCurrency = $this->exchange->getDefault();
		$defaultSymbol = $defaultCurrency->getFormat()->symbol;

		$rates = $form->addContainer('rates');
		foreach ($this->exchange as $code => $currency) {
			/* @var $currency Property */
			if ($code !== $defaultCurrency->getCode()) {
				$currency->revertRate();
				$rating = sprintf('ECB: 1%s = %.3f %s', $defaultSymbol, $currency->getRate(), $code);
				$rates->addText($code, $this->translator->translate('%s rate', NULL, $code))
								->setOption('description', $rating)
								->setAttribute('placeholder', $currency->getRate())
								->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_M;
			}
		}

		$form->addSubmit('save', 'Save');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		foreach ($values->rates as $code => $rate) {
			$rate = preg_replace('/[\,\.]/', '.', $rate);
			$this->saveRate($code, $rate);
		}
		$this->onAfterSave();
	}

	private function saveRate($code, $value)
	{
		$rateRepo = $this->em->getRepository(Entity\Rate::getClassName());
		$rate = $rateRepo->find($code);
		if ($rate) {
			if (!empty($value)) {
				$rate->value = $value;
				$rateRepo->save($rate);
			} else {
				$rateRepo->delete($rate);
			}
		} else if (!empty($value)) {
			$rate = new Entity\Rate($code, $value);
			$rateRepo->save($rate);
		}
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [
			'rates' => [],
		];
		$rateRepo = $this->em->getRepository(Entity\Rate::getClassName());
		foreach ($rateRepo->findPairs('value') as $code => $rate) {
			$values['rates'][$code] = $rate;
		}
		return $values;
	}

}

interface IRateFactory
{

	/** @return Rate */
	function create();
}
