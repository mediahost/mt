<?php

namespace App\Components\Shop\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Address;
use App\Model\Entity\BankAccount;
use App\Model\Entity\Shop;
use App\Model\Entity\ShopVariant;
use App\Model\Entity\Unit;
use Nette\Utils\ArrayHash;

class ShopEdit extends BaseControl
{

	/** @var Shop */
	private $shop;

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

		$form->addGroup();
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

		$form->addGroup('Contact');
		$form->addText('phone', 'cart.form.phone', NULL, 20)
			->setRequired('cart.form.validator.filled')
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addText('mail', 'cart.form.mail', NULL, 255)
			->addRule(Form::FILLED, 'cart.form.validator.filled')
			->addRule(Form::EMAIL, 'cart.form.validator.mail')
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;

		$form->addGroup('cart.form.company');
		$form->addText('ico', 'cart.form.ico', NULL, 30)
			->setRequired()
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addText('dic', 'cart.form.dic', NULL, 30)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;

		$form->addGroup('Bank Account #1');
		$form->addText('bankNumber1', 'Bank Number', NULL, 100)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addText('bankCode1', 'Bank Code', NULL, 100)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addText('bankIban1', 'IBAN', NULL, 100)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addText('bankSwift1', 'SWIFT', NULL, 100)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addSelect2('bankCountry1', 'cart.form.country', Address::getCountries())
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;

		$form->addGroup('Bank Account #2');
		$form->addText('bankNumber2', 'Bank Number', NULL, 100)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addText('bankCode2', 'Bank Code', NULL, 100)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addText('bankIban2', 'IBAN', NULL, 100)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addText('bankSwift2', 'SWIFT', NULL, 100)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addSelect2('bankCountry2', 'cart.form.country', Address::getCountries())
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;

		$form->addSubmit('save', 'Save');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		if (!$this->shop->address) {
			$this->shop->address = new Address();
		}
		$this->shop->address->name = $values->name;
		$this->shop->address->street = $values->street;
		$this->shop->address->city = $values->city;
		$this->shop->address->zipcode = $values->zipcode;
		$this->shop->address->country = $values->country;
		$this->shop->address->phone = $values->phone;
		$this->shop->address->mail = $values->mail;
		$this->shop->address->ico = $values->ico;
		$this->shop->address->dic = $values->dic;

		if ($values->bankNumber1 || $values->bankIban1) {
			if (!$this->shop->bankAccount1) {
				$this->shop->bankAccount1 = new BankAccount();
			}
			$this->shop->bankAccount1->number = $values->bankNumber1;
			$this->shop->bankAccount1->code = $values->bankCode1;
			$this->shop->bankAccount1->iban = $values->bankIban1;
			$this->shop->bankAccount1->swift = $values->bankSwift1;
			$this->shop->bankAccount1->country = $values->bankCountry1;
		}

		if ($values->bankNumber2 || $values->bankIban2) {
			if (!$this->shop->bankAccount2) {
				$this->shop->bankAccount2 = new BankAccount();
			}
			$this->shop->bankAccount2->number = $values->bankNumber2;
			$this->shop->bankAccount2->code = $values->bankCode2;
			$this->shop->bankAccount2->iban = $values->bankIban2;
			$this->shop->bankAccount2->swift = $values->bankSwift2;
			$this->shop->bankAccount2->country = $values->bankCountry2;
		}

		$shopRepo = $this->em->getRepository(Shop::getClassName());
		$shopRepo->save($this->shop);

		$this->onAfterSave();
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [];
		if ($this->shop->address) {
			$values['name'] = $this->shop->address->name;
			$values['street'] = $this->shop->address->street;
			$values['city'] = $this->shop->address->city;
			$values['country'] = $this->shop->address->country;
			$values['zipcode'] = $this->shop->address->zipcode;
			$values['phone'] = $this->shop->address->phone;
			$values['mail'] = $this->shop->address->mail;
			$values['ico'] = $this->shop->address->ico;
			$values['dic'] = $this->shop->address->dic;
		}
		if ($this->shop->bankAccount1) {
			$values['bankNumber1'] = $this->shop->bankAccount1->number;
			$values['bankCode1'] = $this->shop->bankAccount1->code;
			$values['bankIban1'] = $this->shop->bankAccount1->iban;
			$values['bankSwift1'] = $this->shop->bankAccount1->swift;
		}
		if ($this->shop->bankAccount2) {
			$values['bankNumber2'] = $this->shop->bankAccount2->number;
			$values['bankCode2'] = $this->shop->bankAccount2->code;
			$values['bankIban2'] = $this->shop->bankAccount2->iban;
			$values['bankSwift2'] = $this->shop->bankAccount2->swift;
		}
		return $values;
	}

	private function checkEntityExistsBeforeRender()
	{
		if (!$this->shop) {
			throw new BaseControlException('Use setShop() before render');
		}
	}

	// <editor-fold desc="setters & getters">

	public function setShop(Shop $shop)
	{
		$this->shop = $shop;
		return $this;
	}

	// </editor-fold>
}

interface IShopEditFactory
{

	/** @return ShopEdit */
	function create();
}
