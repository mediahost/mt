<?php

namespace App\Components\Auth;

use App\Components\BaseControl;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Forms\Renderers\MetronicHorizontalFormRenderer;
use App\Model\Entity\Address;
use App\Model\Entity\Role;
use App\Model\Entity\User;
use App\Model\Facade\UserFacade;
use App\Model\Storage\SignUpStorage;
use Nette\Forms\IControl;
use Nette\Utils\ArrayHash;

class SignUp extends BaseControl
{
	// <editor-fold desc="events">

	/** @var array */
	public $onSuccess = [];

	// </editor-fold>
	// <editor-fold desc="injects">

	/** @var IFacebookConnectFactory @inject */
	public $iFacebookConnectFactory;

	/** @var ITwitterConnectFactory @inject */
	public $iTwitterConnectFactory;

	/** @var UserFacade @inject */
	public $userFacade;

	/** @var SignUpStorage @inject */
	public $session;
	// </editor-fold>

	private $completeInfo = FALSE;

	/** @return Form */
	protected function createComponentForm()
	{
		$form = new Form();
		$form->setRenderer($this->completeInfo ? new MetronicHorizontalFormRenderer(4, 8) : new MetronicFormRenderer());
		$form->setTranslator($this->translator);

		if ($this->completeInfo) {
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

			$form->addGroup('cart.form.company');

			$form->addText('ico', 'cart.form.ico', NULL, 30)
							->setRequired()
							->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
			$form->addText('dic', 'cart.form.dic', NULL, 30)
							->setRequired()
							->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
			$form->addText('icoVat', 'cart.form.icoVat', NULL, 30)
							->setRequired()
							->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;

			$form->addGroup('cart.form.account');
		}

		$form->addServerValidatedText('mail', 'E-mail')
				->setRequired('Please enter your e-mail.')
				->setAttribute('placeholder', 'E-mail')
				->addRule(Form::EMAIL, 'E-mail has not valid format.')
				->addServerRule([$this, 'validateMail'], $this->translator->translate('%value% is already registered.'))
				->setOption('description', 'for example: example@domain.com');

		$helpText = $this->translator->translate('At least %count% characters long.', NULL, ['count' => $this->settings->passwords->minLength]);
		$form->addPassword('password', 'Password')
				->setAttribute('placeholder', 'Password')
				->setRequired('Please enter your password')
				->addRule(Form::MIN_LENGTH, 'Password must be at least %count% characters long.', $this->settings->passwords->minLength)
				->setOption('description', $helpText);

		$form->addPassword('passwordVerify', 'Re-type Your Password')
				->setAttribute('placeholder', 'Re-type Your Password')
				->setRequired('Please re-enter your password')
				->addRule(Form::EQUAL, 'Passwords must be equal.', $form['password']);

		if ($this->completeInfo) {
			$form['mail']->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
			$form['password']->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
			$form['passwordVerify']->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
			$form->addSubmit('continue', 'Send');
		} else {
			$form->addSubmit('continue', 'Continue');
		}

		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function validateMail(IControl $control, $arg = NULL)
	{
		return $this->userFacade->isUnique($control->getValue());
	}

	/**
	 * @param Form $form
	 * @param ArrayHash $values
	 */
	public function formSucceeded(Form $form, ArrayHash $values)
	{
		$entity = new User();
		$entity->setMail($values->mail)
				->setLocale($this->translator->getLocale())
				->setCurrency($this->exchange->getDefault()->getCode())
				->setPassword($values->password);
		$roleRepo = $this->em->getRepository(Role::getClassName());
		$entity->requiredRole = $roleRepo->findOneByName(Role::USER);
		$entity->wantBeDealer = $this->completeInfo;
		$entity->billingAddress = $this->loadBillingAddress($values);

		$this->session->verification = FALSE;

		$this->onSuccess($this, $entity);
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
		$address->ico = $values->ico;
		$address->icoVat = $values->icoVat;
		$address->dic = $values->dic;
		return $address;
	}

	public function renderLogin()
	{
		$this->setTemplateFile('login');
		parent::render();
	}

	public function renderSocial()
	{
		$this->setTemplateFile('social');
		parent::render();
	}

	// <editor-fold desc="controls">

	/** @return FacebookConnect */
	protected function createComponentFacebook()
	{
		return $this->iFacebookConnectFactory->create();
	}

	/** @return TwitterConnect */
	protected function createComponentTwitter()
	{
		return $this->iTwitterConnectFactory->create();
	}

	// </editor-fold>

	public function setCompleteInfo()
	{
		$this->completeInfo = TRUE;
		return $this;
	}

}

interface ISignUpFactory
{

	/** @return SignUp */
	function create();
}
