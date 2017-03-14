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

class VariantEdit extends BaseControl
{

	/** @var ShopVariant */
	private $variant;

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
			->setAttribute('placeholder', $this->variant->address->name)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addText('street', 'cart.form.street', NULL, 100)
			->setAttribute('placeholder', $this->variant->address->street)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addText('city', 'cart.form.city', NULL, 100)
			->setAttribute('placeholder', $this->variant->address->city)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addText('zipcode', 'cart.form.zipcode', NULL, 10)
			->setAttribute('placeholder', $this->variant->address->zipcode)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addSelect2('country', 'cart.form.country', Address::getCountries())
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;

		$form->addGroup('Contact');
		$form->addText('phone', 'cart.form.phone', NULL, 20)
			->setAttribute('placeholder', $this->variant->address->phone)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addText('phoneHome', 'Phone Home', NULL, 20)
			->setAttribute('placeholder', $this->variant->address->phoneHome)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addText('mail', 'cart.form.mail', NULL, 255)
			->setAttribute('placeholder', $this->variant->address->mail)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addText('mailHome', 'Mail Home', NULL, 255)
			->setAttribute('placeholder', $this->variant->address->mailHome)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;

		$form->addGroup('cart.form.company');
		$form->addText('ico', 'cart.form.ico', NULL, 30)
			->setAttribute('placeholder', $this->variant->address->ico)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addText('dic', 'cart.form.dic', NULL, 30)
			->setAttribute('placeholder', $this->variant->address->dic)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;

		$form->addGroup('Bank Account #1');
		$form->addText('bankNumber1', 'Bank Number', NULL, 100)
			->setAttribute('placeholder', $this->variant->bankAccount1 ? $this->variant->bankAccount1->number : NULL)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addText('bankCode1', 'Bank Code', NULL, 100)
			->setAttribute('placeholder', $this->variant->bankAccount1 ? $this->variant->bankAccount1->code : NULL)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addText('bankIban1', 'IBAN', NULL, 100)
			->setAttribute('placeholder', $this->variant->bankAccount1 ? $this->variant->bankAccount1->iban : NULL)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addText('bankSwift1', 'SWIFT', NULL, 100)
			->setAttribute('placeholder', $this->variant->bankAccount1 ? $this->variant->bankAccount1->swift : NULL)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addSelect2('bankCountry1', 'cart.form.country', Address::getCountries())
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;

		$form->addGroup('Bank Account #2');
		$form->addText('bankNumber2', 'Bank Number', NULL, 100)
			->setAttribute('placeholder', $this->variant->bankAccount2 ? $this->variant->bankAccount2->number : NULL)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addText('bankCode2', 'Bank Code', NULL, 100)
			->setAttribute('placeholder', $this->variant->bankAccount2 ? $this->variant->bankAccount2->code : NULL)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addText('bankIban2', 'IBAN', NULL, 100)
			->setAttribute('placeholder', $this->variant->bankAccount2 ? $this->variant->bankAccount2->iban : NULL)
			->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;
		$form->addText('bankSwift2', 'SWIFT', NULL, 100)
			->setAttribute('placeholder', $this->variant->bankAccount2 ? $this->variant->bankAccount2->swift : NULL)
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
		$address = $this->variant->getAddress(TRUE);
		if (!$address) {
			$address = new Address();
		}
		$address->name = $values->name;
		$address->street = $values->street;
		$address->city = $values->city;
		$address->zipcode = $values->zipcode;
		$address->country = $values->country;
		$address->phone = $values->phone;
		$address->mail = $values->mail;
		$address->phoneHome = $values->phoneHome;
		$address->mailHome = $values->mailHome;
		$address->ico = $values->ico;
		$address->dic = $values->dic;

		$this->variant->address = $address;

		if ($values->bankNumber1 || $values->bankIban1) {
			$bankAccount1 = $this->variant->getBankAccount1(TRUE);
			if (!$bankAccount1) {
				$bankAccount1 = new BankAccount();
			}
			$bankAccount1->number = $values->bankNumber1;
			$bankAccount1->code = $values->bankCode1;
			$bankAccount1->iban = $values->bankIban1;
			$bankAccount1->swift = $values->bankSwift1;
			$bankAccount1->country = $values->bankCountry1;
			$this->variant->bankAccount1 = $bankAccount1;
		}

		if ($values->bankNumber2 || $values->bankIban2) {
			$bankAccount2 = $this->variant->getBankAccount2(TRUE);
			if (!$bankAccount2) {
				$bankAccount2 = new BankAccount();
			}
			$bankAccount2->number = $values->bankNumber2;
			$bankAccount2->code = $values->bankCode2;
			$bankAccount2->iban = $values->bankIban2;
			$bankAccount2->swift = $values->bankSwift2;
			$bankAccount2->country = $values->bankCountry2;
			$this->variant->bankAccount2 = $bankAccount2;
		}

		$shopRepo = $this->em->getRepository(Shop::getClassName());
		$shopRepo->save($this->variant);

		$this->onAfterSave();
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [];
		$address = $this->variant->getAddress(TRUE);
		if ($address) {
			$values['name'] = $address->name;
			$values['street'] = $address->street;
			$values['city'] = $address->city;
			$values['country'] = $this->variant->address->country;
			$values['zipcode'] = $address->zipcode;
			$values['phone'] = $address->phone;
			$values['mail'] = $address->mail;
			$values['phoneHome'] = $address->phoneHome;
			$values['mailHome'] = $address->mailHome;
			$values['ico'] = $address->ico;
			$values['dic'] = $address->dic;
		}
		$bankAccount1 = $this->variant->getBankAccount1(TRUE);
		if ($bankAccount1) {
			$values['bankNumber1'] = $bankAccount1->number;
			$values['bankCode1'] = $bankAccount1->code;
			$values['bankIban1'] = $bankAccount1->iban;
			$values['bankSwift1'] = $bankAccount1->swift;
			$values['bankCountry1'] = $this->variant->bankAccount1 ? $this->variant->bankAccount1->country : NULL;
		}
		$bankAccount2 = $this->variant->getBankAccount2(TRUE);
		if ($bankAccount2) {
			$values['bankNumber2'] = $bankAccount2->number;
			$values['bankCode2'] = $bankAccount2->code;
			$values['bankIban2'] = $bankAccount2->iban;
			$values['bankSwift2'] = $bankAccount2->swift;
			$values['bankCountry2'] = $this->variant->bankAccount2 ? $this->variant->bankAccount2->country : NULL;
		}
		return $values;
	}

	private function checkEntityExistsBeforeRender()
	{
		if (!$this->variant) {
			throw new BaseControlException('Use setVariant() before render');
		}
	}

	// <editor-fold desc="setters & getters">

	public function setVariant(ShopVariant $variant)
	{
		$this->variant = $variant;
		return $this;
	}

	// </editor-fold>
}

interface IVariantEditFactory
{

	/** @return VariantEdit */
	function create();
}
