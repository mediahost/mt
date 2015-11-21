<?php

namespace App\Components\Basket\Form;

use App\Components\BaseControl;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicHorizontalFormRenderer;
use App\Model\Entity\Address;
use App\Model\Facade\BasketFacade;
use App\Model\Facade\NewsletterFacade;
use App\Model\Facade\UserFacade;
use Nette\Security\User;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;

class Personal extends BaseControl
{

	/** @var BasketFacade @inject */
	public $basketFacade;

	/** @var NewsletterFacade @inject */
	public $newsletterFacade;

	/** @var UserFacade @inject */
	public $userFacade;

	/** @var User @inject */
	public $user;

	// <editor-fold desc="events">

	/** @var array */
	public $onAfterSave = [];

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicHorizontalFormRenderer(4, 8));
		if ($this->isAjax) {
			$form->getElementPrototype()->class('ajax loadingNoOverlay');
		}

		if ($this->basketFacade->needAddress()) {
			$this->createFormWithAddress($form);
		} else {
			$this->createFormWithoutAddress($form);
		}

		$fieldsetHide = Html::el('div', ['class' => 'fieldset', 'style' => 'display:none']);
		
		$form->addGroup('cart.form.note');
		$form->addTextArea('note', 'cart.form.noteDescript', NULL, 3)
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		
		$form->addGroup()->setOption('container', $fieldsetHide);
		$form->addSubmit('save', 'cart.continue')
						->getControlPrototype()->class[] = 'send-button';

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, ArrayHash $values)
	{
		if ($this->basketFacade->needAddress()) {
			$this->formSucceededWithAddress($form, $values);
		} else {
			$this->formSucceededWithoutAddress($form, $values);
		}
		$this->onAfterSave();
	}

	private function createFormWithAddress(Form &$form)
	{
		$form->addGroup('cart.form.billing');
		$form->addText('name', 'cart.form.name', NULL, 100)
						->setRequired('cart.form.validator.filled')
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addText('street', 'cart.form.street', NULL, 100)
						->setRequired('cart.form.validator.filled')
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addText('city', 'cart.form.city', NULL, 100)
						->setRequired('cart.form.validator.filled')
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addText('zipcode', 'cart.form.zipcode', NULL, 10)
						->setRequired('cart.form.validator.filled')
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addSelect2('country', 'cart.form.country', Address::getCountries())
						->setRequired('cart.form.validator.filled')
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addText('phone', 'cart.form.phone', NULL, 20)
						->setRequired('cart.form.validator.filled')
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addText('mail', 'cart.form.mail', NULL, 255)
						->addRule(Form::FILLED, 'cart.form.validator.filled')
						->addRule(Form::EMAIL, 'cart.form.validator.mail')
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form['mail']->setDisabled($this->user->isLoggedIn());
		$form->addCheckSwitch('newsletter', 'cart.form.newsletter', 'YES', 'NO')
				->setDefaultValue(TRUE);

		$shippingBoxId = 'shippingBox';
		$form->addCheckSwitch('other_delivery', 'cart.form.sameDelivery', 'YES', 'NO')
				->setDefaultValue(FALSE)
				->addCondition(Form::EQUAL, TRUE)
				->toggle($shippingBoxId);

		$fieldsetShipping = Html::el('div', ['class' => 'fieldset', 'style' => 'display:none'])->id($shippingBoxId);
		$form->addGroup('cart.form.shipping')
				->setOption('container', $fieldsetShipping);

		$form->addText('s_name', 'cart.form.nameOnly', NULL, 100)
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addText('s_street', 'cart.form.street', NULL, 100)
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addText('s_city', 'cart.form.city', NULL, 100)
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addText('s_zipcode', 'cart.form.zipcode', NULL, 10)
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addSelect2('s_country', 'cart.form.country', Address::getCountries())
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addText('s_phone', 'cart.form.phone', NULL, 20)
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;

		$form->addGroup();
		$companyBoxId = 'companyBox';
		$form->addCheckSwitch('isCompany', 'cart.form.isCompany', 'YES', 'NO')
				->setDefaultValue(FALSE)
				->addCondition(Form::EQUAL, TRUE)
				->toggle($companyBoxId);

		$fieldsetCompany = Html::el('div', ['class' => 'fieldset', 'style' => 'display:none'])->id($companyBoxId);
		$form->addGroup('cart.form.company')
				->setOption('container', $fieldsetCompany);

		$form->addText('ico', 'cart.form.ico', NULL, 30)
				->addConditionOn($form['isCompany'], Form::EQUAL, TRUE)
				->addRule(Form::FILLED, 'cart.form.validator.filled');
		$form['ico']->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addText('dic', 'cart.form.dic', NULL, 30)
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addText('icoVat', 'cart.form.icoVat', NULL, 30)
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
	}

	private function formSucceededWithAddress(Form $form, ArrayHash $values)
	{
		$billingAddress = new Address();
		$billingAddress->phone = $values->phone;
		$billingAddress->name = $values->name;
		$billingAddress->street = $values->street;
		$billingAddress->city = $values->city;
		$billingAddress->zipcode = $values->zipcode;
		$billingAddress->country = $values->country;
		$billingAddress->note = $values->note;
		if ($values->isCompany) {
			$billingAddress->ico = $values->ico;
			$billingAddress->dic = $values->dic;
			$billingAddress->icoVat = $values->icoVat;
		}
		if ($values->other_delivery) {
			$shippingAddress = new Address();
			$shippingAddress->name = $values->s_name;
			$shippingAddress->street = $values->s_street;
			$shippingAddress->city = $values->s_city;
			$shippingAddress->zipcode = $values->s_zipcode;
			$shippingAddress->country = $values->s_country;
			$shippingAddress->phone = $values->s_phone;
		} else {
			$shippingAddress = NULL;
		}

		if ($this->user->loggedIn) {
			$this->userFacade->setAddress($this->user->identity, $billingAddress, $shippingAddress);
			$mail = $this->user->identity->mail;
		} else {
			$mail = $values->mail;
		}
		$this->basketFacade->setAddress($mail, $billingAddress, $shippingAddress, TRUE);

		if ($values->newsletter) {
			$this->newsletterFacade->subscribe($mail);
		} else {
			$this->newsletterFacade->unsubscribe($mail);
		}
	}

	private function createFormWithoutAddress(Form &$form)
	{
		$form->addGroup('cart.address');
		$form->addText('mail', 'cart.form.mail', NULL, 255)
						->addRule(Form::FILLED, 'cart.form.validator.filled')
						->addRule(Form::EMAIL, 'cart.form.validator.mail')
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form['mail']->setDisabled($this->user->isLoggedIn());

		$form->addText('phone', 'cart.form.phone', NULL, 20)
						->setRequired('cart.form.validator.filled')
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;

		$invoiceBoxId = 'invoiceBox';
		$form->addCheckSwitch('invoice', 'cart.form.inputInvoice', 'YES', 'NO')
				->setDefaultValue(FALSE)
				->addCondition(Form::EQUAL, TRUE)
				->toggle($invoiceBoxId);

		$fieldsetShipping = Html::el('div', ['class' => 'fieldset', 'style' => 'display:none'])->id($invoiceBoxId);
		$form->addGroup('cart.form.billing')
				->setOption('container', $fieldsetShipping);

		$form->addText('name', 'cart.form.name', NULL, 100)
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addText('street', 'cart.form.street', NULL, 100)
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addText('city', 'cart.form.city', NULL, 100)
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addText('zipcode', 'cart.form.zipcode', NULL, 10)
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addSelect2('country', 'cart.form.country', Address::getCountries())
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;

		$form->addText('ico', 'cart.form.ico', NULL, 30)
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addText('dic', 'cart.form.dic', NULL, 30)
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addText('icoVat', 'cart.form.icoVat', NULL, 30)
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
	}

	private function formSucceededWithoutAddress(Form $form, ArrayHash $values)
	{
		$address = new Address();
		$address->phone = $values->phone;
		$address->note = $values->note;
		if ($values->invoice) {
			$address->name = $values->name;
			$address->street = $values->street;
			$address->city = $values->city;
			$address->zipcode = $values->zipcode;
			$address->country = $values->country;
			$address->ico = $values->ico;
			$address->dic = $values->dic;
			$address->icoVat = $values->icoVat;
		}
		if ($this->user->loggedIn) {
			$this->userFacade->setAddress($this->user->identity, $address);
			$mail = $this->user->identity->mail;
		} else {
			$mail = $values->mail;
		}
		$this->basketFacade->setAddress($mail, $address);
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [];
		$basket = $this->basketFacade->getBasket();
		$mail = $basket->mail;
		$billingAddress = $basket->billingAddress;
		$shippingAddress = $basket->shippingAddress;
		if ($this->user->loggedIn && $this->user->identity) {
			$values['newsletter'] = $this->user->identity->subscriber !== NULL;
			if (!$mail && !$billingAddress && !$shippingAddress) {
				$mail = $this->user->identity->mail;
				$billingAddress = $this->user->identity->billingAddress;
				$shippingAddress = $this->user->identity->shippingAddress;
			}
		}
		if ($mail) {
			$values['mail'] = $mail;
		}
		if ($billingAddress) {
			$values['invoice'] = $billingAddress->isFilled();
			$values['name'] = $billingAddress->name;
			$values['street'] = $billingAddress->street;
			$values['city'] = $billingAddress->city;
			$values['country'] = $billingAddress->country;
			$values['zipcode'] = $billingAddress->zipcode;
			$values['phone'] = $billingAddress->phone;
			$values['note'] = $billingAddress->note;
			$values['isCompany'] = $billingAddress->isCompany();
			$values['ico'] = $billingAddress->ico;
			$values['dic'] = $billingAddress->dic;
			$values['icoVat'] = $billingAddress->icoVat;
		}
		if ($shippingAddress) {
			$values['other_delivery'] = $shippingAddress->isFilled();
			$values['s_name'] = $shippingAddress->name;
			$values['s_street'] = $shippingAddress->street;
			$values['s_city'] = $shippingAddress->city;
			$values['s_country'] = $shippingAddress->country;
			$values['s_zipcode'] = $shippingAddress->zipcode;
			$values['s_phone'] = $shippingAddress->phone;
		}
		return $values;
	}

}

interface IPersonalFactory
{

	/** @return Personal */
	function create();
}
