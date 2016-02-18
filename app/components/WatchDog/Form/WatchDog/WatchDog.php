<?php

namespace App\Components\WatchDog\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Price;
use App\Model\Entity\Stock;
use App\Model\Facade\WatchDogFacade;
use h4kuna\Exchange\Exchange;
use Nette\Security\User;

class WatchDog extends BaseControl
{

	/** @var Stock */
	private $stock;

	/** @var int */
	private $priceLevel;

	/** @var WatchDogFacade @inject */
	public $watchDogFacade;

	/** @var User @inject */
	public $user;

	/** @var Exchange @inject */
	public $exchange;

	/** @var array */
	public $onAfterSubmit = [];

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());
		if ($this->isAjax) {
			$form->getElementPrototype()->class('ajax loadingNoOverlay');
		}

		$form->addCheckbox('checkAvailable', 'Check available');
		$form->addCheckbox('checkPrice', 'Check price');

		$form->addText('price', 'Price')
				->setAttribute('class', ['mask_currency', MetronicTextInputBase::SIZE_S]);

		$form->addText('mail', 'By Email to', NULL, 255)
				->setRequired('Please enter your e-mail')
				->addRule(Form::EMAIL, 'Fill right e-mail format');

		$form->addSubmit('submit', 'Confirm');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$price = $values->checkPrice ? Price::strToFloat($values->price) : NULL;
		$exchWebCode = $this->exchange->getWeb()->getCode();
		$exchDefaultCode = $this->exchange->getDefault()->getCode();
		if ($price && $exchWebCode !== $exchDefaultCode) {
			$changedPrice = $this->exchange->change($price, $exchWebCode, $exchDefaultCode);
			$price = round($changedPrice, Price::PRECISION);
		}
		$this->watchDogFacade->add($this->stock, $values->mail, $values->checkAvailable, $price, $this->priceLevel);
		$this->onAfterSubmit();
	}

	/** @return array */
	protected function getDefaults()
	{
		$priceWithVat = $this->stock->getPrice($this->priceLevel)->withVat;
		$values = [
			'checkAvailable' => TRUE,
			'checkPrice' => TRUE,
			'mail' => $this->user->identity->mail,
		];
		$watchDog = $this->watchDogFacade->findOne($this->stock, $this->user->identity->mail);
		if ($watchDog) {
			$values['checkAvailable'] = $watchDog->available;
			$values['checkPrice'] = $watchDog->price;
			if ($watchDog->price) {
				$price = new Price($this->stock->vat, $watchDog->price);
				$priceWithVat = $price->withVat;
			}
		}
		$values['price'] = $this->exchange->change($priceWithVat);
		return $values;
	}

	private function checkEntityExistsBeforeRender()
	{
		if (!$this->stock) {
			throw new BaseControlException('Use setStock(\App\Model\Entity\Stock) before render');
		}
	}

	public function render()
	{
		$defaultCurrency = $this->exchange->getDefault();
		$defaultSymbol = $defaultCurrency->getFormat()->symbol;
		$this->template->symbol = $defaultSymbol;
		parent::render();
	}

	public function renderModal()
	{
		$this->setTemplateFile('modal');
		$this->render();
	}

	// <editor-fold desc="setters & getters">

	public function setStock(Stock $stock, $priceLevel = NULL)
	{
		$this->stock = $stock;
		$this->priceLevel = $priceLevel;
		return $this;
	}

	// </editor-fold>
}

interface IWatchDogFactory
{

	/** @return WatchDog */
	function create();
}
