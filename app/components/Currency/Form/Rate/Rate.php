<?php

namespace App\Components\Currency\Form;

use App\Components\BaseControl;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity;
use h4kuna\Exchange\Currency\Property;
use h4kuna\Exchange\Exchange;
use Nette\Utils\Strings;

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
		$form->setRenderer(new MetronicFormRenderer());

		$defaultCurrency = $this->exchange->getDefault();
		$defaultSymbol = $defaultCurrency->getFormat()->symbol;

		$rateRepo = $this->em->getRepository(Entity\BankRate::getClassName());

		$rates = $form->addContainer('rates');
		foreach ($this->exchange as $code => $currency) {
			/* @var $currency Property */
			if ($code !== $defaultCurrency->getCode()) {
				$currency->revertRate();

				$rate = $rateRepo->findOneByCode(Strings::upper($code));

				$rating = sprintf('ECB: 1%s = %.3f %s', $defaultSymbol, $rate->getValue(TRUE), $code);
				$rates->addText($code, $this->translator->translate('%code% rate', NULL, ['code' => $code]))
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
			$rate = Entity\Price::strToFloat($rate);
			$this->saveRate($code, $rate);
		}
		$this->onAfterSave();
	}

	private function saveRate($code, $value)
	{
		$rateRepo = $this->em->getRepository(Entity\BankRate::getClassName());
		$rate = $rateRepo->findOneByCode(Strings::upper($code));
		if (!$rate) {
			$rate = new Entity\BankRate($code, $value);
		}

		$rate->fixed = empty($value) ? NULL : $value;
		$this->em->persist($rate);
		$this->em->flush();
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [
			'rates' => [],
		];
		$rateRepo = $this->em->getRepository(Entity\BankRate::getClassName());
		foreach ($rateRepo->findPairs('fixed') as $code => $rate) {
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
