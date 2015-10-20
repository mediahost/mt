<?php

namespace App\Components\User\Form;

use App\Components\BaseControl;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicHorizontalFormRenderer;
use App\Model\Entity\Address;
use App\Model\Entity\User as EntityUser;
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
		$form->setRenderer(new MetronicHorizontalFormRenderer(5, 7));
		if ($this->isAjax) {
			$form->getElementPrototype()->class('ajax loadingNoOverlay');
		}

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
		$form->addCheckSwitch('dealer', 'cart.form.dealer', 'YES', 'NO')
				->setDefaultValue(FALSE);
		if ($this->user->identity->isDealer()) {
			$form['dealer']->setDefaultValue(TRUE)
					->setDisabled();
		}
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
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addText('dic', 'cart.form.dic', NULL, 30)
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addText('icoVat', 'cart.form.icoVat', NULL, 30)
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;

		$form->addGroup();
		$form->addSubmit('save', 'Save');

		$form->setDefaults($this->getDefaults());
		$form->onValidate[] = $this->formValidate;
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formValidate(Form $form, ArrayHash $values)
	{
		if ($values->dealer) {
			if (!$values->ico || !$values->dic || !$values->icoVat) {
				$form->addError($this->translator->translate('cart.form.validator.dealer'));
				$form['dealer']->addError($this->translator->translate('cart.form.validator.company'));
				if (!$values->ico) {
					$form['ico']->addError($this->translator->translate('cart.form.validator.filled'));
				}
				if (!$values->dic) {
					$form['dic']->addError($this->translator->translate('cart.form.validator.filled'));
				}
				if (!$values->icoVat) {
					$form['icoVat']->addError($this->translator->translate('cart.form.validator.filled'));
				}
			}
		}
	}

	public function formSucceeded(Form $form, ArrayHash $values)
	{
		if ($this->user->isLoggedIn() && $this->user->identity) {
			$billingAddress = $this->loadBillingAddress($values);
			$shippingAddress = $this->loadShippingAddress($values);
			$this->userFacade->setAddress($this->user->identity, $billingAddress, $shippingAddress);
			$this->userFacade->setDealerWant($this->user->identity, $values->dealer);
			
			if ($values->newsletter) {
				$this->newsletterFacade->subscribe($this->user->identity);
			} else {
				$this->newsletterFacade->unsubscribe($this->user->identity);
			}

			$this->onAfterSave($this->user->identity);
		}
	}

	private function loadBillingAddress(ArrayHash $values)
	{
		$address = new Address();
		$address->name = $values->name;
		$address->street = $values->street;
		$address->city = $values->city;
		$address->zipcode = $values->zipcode;
		$address->country = $values->country;
		$address->phone = $values->phone;
		if ($values->isCompany) {
			$address->ico = $values->ico;
			$address->icoVat = $values->icoVat;
			$address->dic = $values->dic;
		}
		return $address;
	}

	private function loadShippingAddress(ArrayHash $values)
	{
		if ($values->other_delivery) {
			$address = new Address();
			$address->name = $values->s_name;
			$address->street = $values->s_street;
			$address->city = $values->s_city;
			$address->zipcode = $values->s_zipcode;
			$address->country = $values->s_country;
			$address->phone = $values->s_phone;
			return $address;
		}
		return NULL;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [];
		if ($this->user->isLoggedIn() && $this->user->identity) {
			/* @var $identity EntityUser */
			$identity = $this->user->identity;
			$values['mail'] = $identity->mail;
			$values['newsletter'] = $identity->subscriber !== NULL;
			$values['dealer'] = $identity->wantBeDealer;
			if ($identity->billingAddress) {
				$values['name'] = $identity->billingAddress->name;
				$values['street'] = $identity->billingAddress->street;
				$values['city'] = $identity->billingAddress->city;
				$values['country'] = $identity->billingAddress->country;
				$values['zipcode'] = $identity->billingAddress->zipcode;
				$values['phone'] = $identity->billingAddress->phone;
				$values['isCompany'] = $identity->billingAddress->isCompany();
				$values['ico'] = $identity->billingAddress->ico;
				$values['icoVat'] = $identity->billingAddress->icoVat;
				$values['dic'] = $identity->billingAddress->dic;
			}
			if ($identity->shippingAddress) {
				$values['other_delivery'] = TRUE;
				$values['s_name'] = $identity->shippingAddress->name;
				$values['s_street'] = $identity->shippingAddress->street;
				$values['s_city'] = $identity->shippingAddress->city;
				$values['s_country'] = $identity->shippingAddress->country;
				$values['s_zipcode'] = $identity->shippingAddress->zipcode;
				$values['s_phone'] = $identity->shippingAddress->phone;
			}
		}
		return $values;
	}

}

interface IPersonalFactory
{

	/** @return Personal */
	function create();
}
