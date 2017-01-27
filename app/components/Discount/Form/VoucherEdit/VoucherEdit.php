<?php

namespace App\Components\Discount\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Controls\Custom\DatePicker;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Price;
use App\Model\Entity\Voucher;
use App\Model\Facade\VoucherFacade;
use Nette\Forms\IControl;
use Nette\Utils\ArrayHash;

class VoucherEdit extends BaseControl
{

	/** @var Voucher */
	private $voucher;

	/** @var VoucherFacade @inject */
	public $voucherFacade;

	// <editor-fold desc="events">

	/** @var array */
	public $onAfterSave = [];

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());

		if ($this->voucher->isNew()) {
			$form->addServerValidatedText('code', 'Code')
				->setRequired('Code is required')
				->addServerRule([$this, 'validateCode'], $this->translator->translate('%value% is already used.'))
				->setValue($this->voucher->code)
				->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_M;

			$form->addText('value', 'Value')
				->setRequired('Value is required')
				->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_M;
			if ($this->voucher->type === Voucher::PERCENTAGE) {
				$form['value']->addRule(Form::RANGE, 'Must be between %d and %d', [1, 99]);
				$form['value']->getControlPrototype()->class[] = 'mask_percentage';
			} else {
				$form['value']->addRule(Form::MIN, 'Must be bigger than %d', 0.1);
				$currencies = [];
				foreach ($this->exchange->getArrayCopy() as $code => $currency) {
					$currencies[$code] = $code;
				}
				$form->addSelect2('currency', 'Currency', $currencies)
					->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_M;
			}
		}

		$form->addDatePicker('activeFrom', 'Active from')
			->setSize(DatePicker::SIZE_M)
			->setPlaceholder($this->translator->translate('now'));
		$form->addDatePicker('activeTo', 'Active to')
			->setSize(DatePicker::SIZE_M)
			->setPlaceholder($this->translator->translate('without ending'));

		$form->addSubmit('save', 'Save');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function validateCode(IControl $control, $arg = NULL)
	{
		return $this->voucherFacade->isUnique($control->getValue());
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->load($values);
		$this->save();
		$this->onAfterSave($this->voucher);
	}

	private function load(ArrayHash $values)
	{
		if ($values->code) {
			$this->voucher->setCode($values->code);
		}
		if ($values->value) {
			$this->voucher->setValue(Price::strToFloat($values->value));
		}
		$this->voucher->currency = isset($values->currency) ? $values->currency : NULL;
		if ($values->activeFrom) {
			$this->voucher->activeFrom = $values->activeFrom;
		}
		if ($values->activeTo) {
			$this->voucher->activeTo = $values->activeTo;
		}
		return $this;
	}

	private function save()
	{
		$voucherRepo = $this->em->getRepository(Voucher::getClassName());
		$voucherRepo->save($this->voucher);
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [
			'value' => $this->voucher->value,
			'currency' => $this->voucher->currency,
			'activeFrom' => $this->voucher->activeFrom,
			'activeTo' => $this->voucher->activeTo,
		];
		return $values;
	}

	private function checkEntityExistsBeforeRender()
	{
		if (!$this->voucher) {
			throw new BaseControlException('Use setVoucher(\App\Model\Entity\Voucher) before render');
		}
	}

	// <editor-fold desc="setters & getters">

	public function setVoucher(Voucher $voucher)
	{
		$this->voucher = $voucher;
		return $this;
	}

	// </editor-fold>
}

interface IVoucherEditFactory
{

	/** @return VoucherEdit */
	function create();
}
