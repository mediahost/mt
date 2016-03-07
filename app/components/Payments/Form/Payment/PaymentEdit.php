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
use Nette\Security\User;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;

class PaymentEdit extends BaseControl
{

	/** @var User @inject */
	public $user;

	/** @var VatFacade @inject */
	public $vatFacade;

	/** @var Payment */
	private $payment;

	/** @var bool */
	private $defaultWithVat = TRUE;

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

		if ($this->user->isAllowed('payments', 'editAll')) {
			$form->addGroup('Superadmin part');
			$form->addCheckSwitch('active', 'Active', 'YES', 'NO');
			$form->addCheckSwitch('needAddress', 'Need Address', 'YES', 'NO');
//			$form->addCheckSwitch('cond1', 'Apply condition #1', 'YES', 'NO');
//			$form->addCheckSwitch('cond2', 'Apply condition #2', 'YES', 'NO');
			$form->addCheckSwitch('isCard', 'Is Card Payment', 'YES', 'NO');
			$form->addCheckSwitch('isHomecreditSk', 'Is Home Credit SK Payment', 'YES', 'NO');
			$form->addText('free', 'Free price')
				->setAttribute('class', ['mask_currency', MetronicTextInputBase::SIZE_S]);
			$form->addGroup('Admin part');
		}

		$form->addText('price', 'Price')
				->setAttribute('class', ['mask_currency', MetronicTextInputBase::SIZE_S])
				->setRequired();

		$form->addSelect2('vat', 'Vat', $this->vatFacade->getValues())
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_XS;

		$form->addCheckSwitch('with_vat', 'With VAT', 'YES', 'NO')
				->setDefaultValue($this->defaultWithVat);

		$allowedTags = Html::el()->setText($this->translator->translate('Allowed tags') . ':');
		$tagOrderNumber = Html::el()->setText('%order_number% - ' . $this->translator->translate('Order number'));
		$separator = Html::el('br');
		$description = $allowedTags
				->add($separator)
				->add($tagOrderNumber);
		$form->addWysiHtml('html', 'Text', 10)
						->setOption('description', $description)
						->getControlPrototype()->class[] = 'page-html-content';
		
		$form->addWysiHtml('errorHtml', 'Text while Error', 10)
						->setOption('description', $description)
						->getControlPrototype()->class[] = 'page-html-content';

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
		if (isset($values->active)) {
			$this->payment->active = $values->active;
		}
		if (isset($values->needAddress)) {
			$this->payment->needAddress = $values->needAddress;
		}
		if (isset($values->cond1)) {
			$this->payment->useCond1 = $values->cond1;
		}
		if (isset($values->cond2)) {
			$this->payment->useCond2 = $values->cond2;
		}
		if (isset($values->isCard)) {
			$this->payment->isCard = $values->isCard;
		}
		if (isset($values->isHomecreditSk)) {
			$this->payment->isHomecreditSk = $values->isHomecreditSk;
		}
		if (isset($values->free)) {
			$this->payment->setFreePrice($values->free, $values->with_vat);
		}
		
		$this->payment->translateAdd($this->translator->getLocale())->html = $values->html;
		$this->payment->translateAdd($this->translator->getLocale())->errorHtml = $values->errorHtml;
		$this->payment->mergeNewTranslations();

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
		$this->payment->setCurrentLocale($this->translator->getLocale());
		$values = [
			'active' => $this->payment->active,
			'needAddress' => $this->payment->needAddress,
			'cond1' => $this->payment->useCond1,
			'cond2' => $this->payment->useCond2,
			'isCard' => $this->payment->isCard,
			'isHomecreditSk' => $this->payment->isHomecreditSk,
			'html' => $this->payment->html,
			'errorHtml' => $this->payment->errorHtml,
		];
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
		if ($this->payment->freePrice) {
			$values += [
				'free' => $this->defaultWithVat ? $this->payment->freePrice->withVat : $this->payment->freePrice->withoutVat,
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
