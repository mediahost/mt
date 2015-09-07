<?php

namespace App\Components\Basket\Form;

use App\Components\BaseControl;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicHorizontalFormRenderer;
use App\Model\Entity\Address;
use App\Model\Facade\BasketFacade;
use App\Model\Facade\NewsletterFacade;
use Nette\Security\User;
use Nette\Utils\ArrayHash;

class Personal extends BaseControl
{

	/** @var BasketFacade @inject */
	public $basketFacade;

	/** @var NewsletterFacade @inject */
	public $newsletterFacade;

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
		$form->setRenderer(new MetronicHorizontalFormRenderer());
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

		$form->addText('mail', 'cart.form.mail', NULL, 255)
						->addRule(Form::FILLED, 'cart.form.validator.filled')
						->addRule(Form::EMAIL, 'cart.form.validator.mail')
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form['mail']->setDisabled($this->user->isLoggedIn());

		$form->addText('phone', 'cart.form.phone', NULL, 20)
						->setRequired('cart.form.validator.filled')
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;

		$form->addGroup('cart.form.shipping');
		
		$form->addText('s_name', 'cart.form.name', NULL, 100)
						->setRequired('cart.form.validator.filled')
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;

		$form->addSubmit('save', 'cart.continue');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, ArrayHash $values)
	{
		$billingAddress = $this->loadBillingAddress($values);
		$shippingAddress = $this->loadShippingAddress($values);
		$this->onAfterSave();
	}
	
	private function loadBillingAddress(ArrayHash $values)
	{
		$address = new Address();
		return $address;
	}
	
	private function loadhiipingAddress(ArrayHash $values)
	{
		$address = new Address();
		return $address;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [];
		if ($this->user->isLoggedIn() && $this->user->identity) {
			$identity = $this->user->identity;
			$values['mail'] = $identity->mail;
			if ($identity->billingAddress) {
				$values['name'] = $identity->billingAddress->name;
				$values['street'] = $identity->billingAddress->street;
				$values['city'] = $identity->billingAddress->city;
				$values['country'] = $identity->billingAddress->country;
				$values['zipcode'] = $identity->billingAddress->zipcode;
				$values['phone'] = $identity->billingAddress->phone;
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
