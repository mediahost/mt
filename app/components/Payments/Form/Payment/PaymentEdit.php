<?php

namespace App\Components\Payments\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Payment;
use App\Model\Entity\Shipping;
use App\Model\Entity\Vat;
use App\Model\Facade\VatFacade;
use Nette\Utils\ArrayHash;

class PaymentEdit extends BaseControl
{

	/** @var Payment */
	private $payment;

	/** @var bool */
	private $defaultWithVat = TRUE;

	/** @var VatFacade @inject */
	public $vatFacade;

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

		$shippingRepo = $this->em->getRepository(Shipping::getClassName());
		$shippings = $shippingRepo->findPairs('name');
		
		$form->addText('price', 'Price')
				->setAttribute('class', ['mask_currency', MetronicTextInputBase::SIZE_S])
				->setRequired();

		$form->addSelect2('vat', 'Vat', $this->vatFacade->getValues())
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_XS;
		
		$form->addCheckSwitch('with_vat', 'With VAT', 'YES', 'NO')
				->setDefaultValue($this->defaultWithVat);

		$form->addMultiSelect2('shippings', 'For Shippings', $shippings);

		$form->addSubmit('save', 'Save');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->load($values);
		$this->save();
		$this->onAfterSave($this->payment);
	}

	private function load(ArrayHash $values)
	{
		$shippingRepo = $this->em->getRepository(Shipping::getClassName());
		$vatRepo = $this->em->getRepository(Vat::getClassName());
		
		$this->payment->clearShippings();
		foreach ($values->shippings as $shippingId) {
			$shipping = $shippingRepo->find($shippingId);
			if ($shipping) {
				$this->payment->addShipping($shipping);
			}
		}

		$vat = $vatRepo->find($values->vat);
		$this->payment->vat = $vat;
		$this->payment->setPrice($values->price, $values->with_vat);
		
		return $this;
	}

	private function save()
	{
		$paymentRepo = $this->em->getRepository(Payment::getClassName());
		$paymentRepo->save($this->payment);
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [];
		foreach ($this->payment->shippings as $shipping) {
			$values['shippings'][] = $shipping->id;
		}
		if ($this->payment->price) {
			$values += [
				'price' => $this->defaultWithVat ? $this->payment->price->withVat : $this->payment->price->withoutVat,
				'with_vat' => $this->defaultWithVat,
				'vat' => $this->payment->vat->id,
			];
		}
		return $values;
	}

	private function checkEntityExistsBeforeRender()
	{
		if (!$this->payment) {
			throw new BaseControlException('Use setPayment(\App\Model\Entity\Payment) before render');
		}
	}

	// <editor-fold desc="setters & getters">

	public function setPayment(Payment $payment)
	{
		$this->payment = $payment;
		return $this;
	}

	// </editor-fold>
}

interface IPaymentEditFactory
{

	/** @return PaymentEdit */
	function create();
}
