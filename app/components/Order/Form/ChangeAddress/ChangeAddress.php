<?php

namespace App\Components\Order\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Address;
use App\Model\Entity\Order;
use App\Model\Facade\OrderFacade;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;

class ChangeAddress extends BaseControl
{

	/** @var OrderFacade @inject */
	public $orderFacade;

	/** @var Order */
	private $order;

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

		$form->addGroup('cart.form.billing');
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
		$form->addText('phone', 'cart.form.phone', NULL, 20)
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_S;
		$form->addText('mail', 'cart.form.mail', NULL, 255)
						->addRule(Form::EMAIL, 'cart.form.validator.mail')
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_L;

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
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->load($form, $values);
		if (!$form->hasErrors()) {
			$this->save();
			$this->onAfterSave($this->order);
		}
	}

	private function load(Form $form, ArrayHash $values)
	{
		$this->order->mail = $values->mail;

		$billingAddress = new Address();
		$billingAddress->phone = $values->phone;
		$billingAddress->name = $values->name;
		$billingAddress->street = $values->street;
		$billingAddress->city = $values->city;
		$billingAddress->zipcode = $values->zipcode;
		$billingAddress->country = $values->country;
		if ($values->isCompany) {
			$billingAddress->ico = $values->ico;
			$billingAddress->dic = $values->dic;
			$billingAddress->icoVat = $values->icoVat;
		}
		if ($this->order->billingAddress) {
			$this->order->billingAddress->import($billingAddress);
		} else {
			$this->order->billingAddress = $billingAddress;
		}

		if ($values->other_delivery) {
			$shippingAddress = new Address();
			$shippingAddress->name = $values->s_name;
			$shippingAddress->street = $values->s_street;
			$shippingAddress->city = $values->s_city;
			$shippingAddress->zipcode = $values->s_zipcode;
			$shippingAddress->country = $values->s_country;
			$shippingAddress->phone = $values->s_phone;
			if ($this->order->shippingAddress) {
				$this->order->shippingAddress->import($shippingAddress);
			} else {
				$this->order->shippingAddress = $shippingAddress;
			}
		} else {
			$addressRepo = $this->em->getRepository(Address::getClassName());
			$addressRepo->delete($this->order->shippingAddress);
			$this->order->shippingAddress = NULL;
		}

		return $this;
	}

	private function save()
	{
		$orderRepo = $this->em->getRepository(Order::getClassName());
		$orderRepo->save($this->order);
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [];
		$mail = $this->order->mail;
		$billingAddress = $this->order->billingAddress;
		$shippingAddress = $this->order->shippingAddress;
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

	private function checkEntityExistsBeforeRender()
	{
		if (!$this->order) {
			throw new BaseControlException('Use setOrder(\App\Model\Entity\Order) before render');
		}
	}

	// <editor-fold desc="setters & getters">

	public function setOrder(Order $order)
	{
		$this->order = $order;
		return $this;
	}

	// </editor-fold>
}

interface IChangeAddressFactory
{

	/** @return ChangeAddress */
	function create();
}
