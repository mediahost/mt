<?php

namespace App\Components\Payments\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Shipping;
use App\Model\Entity\Vat;
use App\Model\Facade\VatFacade;
use Nette\Security\User;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;
use Nette\Utils\Strings;

class ShippingEdit extends BaseControl
{

	/** @var User @inject */
	public $user;

	/** @var VatFacade @inject */
	public $vatFacade;

	/** @var Shipping */
	private $shipping;

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

		if ($this->user->isAllowed('payments', 'editAll')) {
			$form->addGroup('Superadmin part');
			$form->addCheckSwitch('active', 'Active', 'YES', 'NO');
			$form->addCheckSwitch('needAddress', 'Need Address', 'YES', 'NO');
			$form->addCheckSwitch('cond1', 'Apply condition #1', 'YES', 'NO')
				->setOption('description', 'If sum of products in special category is lower then special limit, then price has special value.');
			$form->addCheckSwitch('cond2', 'Apply condition #2', 'YES', 'NO')
				->setOption('description', 'If sum of products in special category is bigger then special limit, then price has zero value.');
			$form->addText('free', 'Free price')
				->setAttribute('class', ['mask_currency_' . Strings::lower($this->shipping->currency), MetronicTextInputBase::SIZE_S]);
			$form->addSelect2('locality', 'Locality', [NULL => 'All', 'cs' => 'CZ', 'sk' => 'SK'])
				->setAttribute('class', [MetronicTextInputBase::SIZE_S]);
			$form->addGroup('Admin part');
		}

		$form->addText('price', 'Price')
			->setAttribute('class', ['mask_currency_' . Strings::lower($this->shipping->currency), MetronicTextInputBase::SIZE_S])
			->setRequired();
		$form->addText('percentPrice', 'Percent Price')
			->setAttribute('class', ['mask_percentage', MetronicTextInputBase::SIZE_S])
			->setOption('description', 'If percentage is set than price will be zero.');

		$form->addSelect2('vat', 'Vat', $this->vatFacade->getValues($this->shipping->shopVariant->shop))
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

		$form->addSubmit('save', 'Save');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$shippingRepo = $this->em->getRepository(Shipping::getClassName());

		$this->load($values, $this->shipping);
		$shippingRepo->save($this->shipping);

		$infoConnected = $shippingRepo->findBy([
			'name' => $this->shipping->name,
		]);
		foreach ($infoConnected as $shipping) {
			$hasSameCurrency = $this->shipping->shopVariant->currency === $shipping->shopVariant->currency;
			$hasSameLocale = $this->shipping->shopVariant->locale === $shipping->shopVariant->locale;
			$this->load($values, $shipping, $hasSameCurrency, $hasSameLocale);
			$shippingRepo->save($shipping);
		}

		$this->onAfterSave($this->shipping);
	}

	private function load(ArrayHash $values, Shipping $shipping, $loadPrice = TRUE, $loadTranslation = TRUE)
	{
		if (isset($values->active)) {
			$shipping->active = $values->active;
		}
		if (isset($values->needAddress)) {
			$shipping->needAddress = $values->needAddress;
		}
		if (isset($values->cond1)) {
			$shipping->useCond1 = $values->cond1;
		}
		if (isset($values->cond2)) {
			$shipping->useCond2 = $values->cond2;
		}
		if ($loadPrice) {
			if ($values->percentPrice) {
				$shipping->setPercentPrice($values->percentPrice);
				$shipping->setPrice(0, $values->with_vat);
			} else {
				if ($shipping->id === $this->shipping->id) {
					$vatRepo = $this->em->getRepository(Vat::getClassName());
					$vat = $vatRepo->find($values->vat);
					$shipping->vat = $vat;
				}
				$shipping->setPrice($values->price, $values->with_vat);
				$shipping->setPercentPrice(NULL);
			}
			if (isset($values->free)) {
				$shipping->setFreePrice($values->free, $values->with_vat);
			}
		}

		if ($loadTranslation) {
			if (isset($values->locality)) {
				$shipping->locality = $values->locality;
			}

			$translation = $shipping->translateAdd($this->translator->getLocale());
			if (empty($values->html)) {
				$shipping->removeTranslation($translation);
			} else {
				$translation->html = $values->html;
				$shipping->mergeNewTranslations();
			}
		}

		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$this->shipping->setCurrentLocale($this->translator->getLocale());
		$values = [
			'active' => $this->shipping->active,
			'needAddress' => $this->shipping->needAddress,
			'cond1' => $this->shipping->useCond1,
			'cond2' => $this->shipping->useCond2,
			'html' => $this->shipping->html,
			'locality' => $this->shipping->locality,
			'percentPrice' => $this->shipping->getPercentPrice(),
		];
		if ($this->shipping->price) {
			$values += [
				'price' => $this->defaultWithVat ? $this->shipping->price->withVat : $this->shipping->price->withoutVat,
				'with_vat' => $this->defaultWithVat,
				'vat' => $this->shipping->vat->id,
			];
		}
		if ($this->shipping->freePrice) {
			$values += [
				'free' => $this->defaultWithVat ? $this->shipping->freePrice->withVat : $this->shipping->freePrice->withoutVat,
			];
		}
		return $values;
	}

	private function checkEntityExistsBeforeRender()
	{
		if (!$this->shipping) {
			throw new BaseControlException('Use setShipping(\App\Model\Entity\Shipping) before render');
		}
	}

	// <editor-fold desc="setters & getters">

	public function setShipping(Shipping $shipping)
	{
		$this->shipping = $shipping;
		return $this;
	}

	// </editor-fold>
}

interface IShippingEditFactory
{

	/** @return ShippingEdit */
	function create();
}
