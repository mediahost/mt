<?php

namespace App\Components\Product\Form;

use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Shop;
use App\Model\Entity\Stock;
use App\Model\Facade\VatFacade;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;

class StockBasic extends StockBase
{
	// <editor-fold desc="variables">

	/** @var VatFacade @inject */
	public $vatFacade;

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form();
		$form->setTranslator($this->translator)
			->setRenderer(new MetronicFormRenderer());
		$form->getElementPrototype()->class('ajax');

		$product = $this->stock->product;
		$product->setCurrentLocale($this->translator->getLocale());
		$shopRepo = $this->em->getRepository(Shop::getClassName());

		$nameMax = 90;
		$form->addText('name', 'Name', NULL, $nameMax)
			->addRule(Form::MAX_LENGTH, 'Max length in Pohoda is %d', $nameMax)
			->setAttribute('placeholder', $product->name);
		$form->addText('pohodaCode', 'Code for Pohoda', NULL, 20)
			->setOption('description', 'Identification for synchronizing')
			->addRule(Form::FILLED, 'Product must be synchronized');
		$form->addText('gift', 'Gift', NULL, 50);
		$form->addText('barcode', 'Barcode', NULL, 50);
		foreach ($shopRepo->findAll() as $shop) {
			$name = $this->translator->translate('Active for %shop%', NULL, ['shop' => $shop]);
			$form->addCheckSwitch('active' . $shop->priceLetter, $name);
		}
		$form->addCheckSwitch('heurekaShow', 'Show on Heureka');
		$form->addCheckSwitch('zboziShow', 'Show on Zbozi');

		$form->addWysiHtml('perex', 'Perex', 4)
			->getControlPrototype()->class[] = 'page-html-content';

		$tags = $this->shopFacade->getShopVariant()->getReplacementTags();
		$allowedTags = Html::el()->setText($this->translator->translate('Allowed tags') . ':');
		$separator = Html::el('br');
		foreach ($tags as $key => $tag) {
			$tagOrderNumber = Html::el()->setText($key . ' » ' . $tag);
			$allowedTags
				->add($separator)
				->add($tagOrderNumber);
		}
		$form->addWysiHtml('description', 'Description', 10)
			->setOption('description', $allowedTags)
			->getControlPrototype()->class[] = 'page-html-content';

		$form->addSubmit('save', 'Save');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->load($values);
		$this->save();
		$this->onAfterSave($this->stock);
	}

	private function load(ArrayHash $values)
	{
		$shopRepo = $this->em->getRepository(Shop::getClassName());

		$this->stock->barcode = $values->barcode;
		$this->stock->gift = $values->gift;
		$this->stock->pohodaCode = $values->pohodaCode;
		$this->stock->heurekaShow = $values->heurekaShow;
		$this->stock->zboziShow = $values->zboziShow;
		$active = FALSE;
		foreach ($shopRepo->findAll() as $shop) {
			$attrName = 'active' . $shop->priceLetter;
			$this->stock->$attrName = $values->$attrName;
			$active |= $values->$attrName;
		}
		$this->stock->active = $active;

		$this->stock->product->translateAdd($this->translator->getLocale())->name = $values->name;
		$this->stock->product->translateAdd($this->translator->getLocale())->perex = $values->perex;
		$this->stock->product->translateAdd($this->translator->getLocale())->description = $values->description;
		$this->stock->product->mergeNewTranslations();

		return $this;
	}

	private function save()
	{
		$stockRepo = $this->em->getRepository(Stock::getClassName());
		$stockRepo->save($this->stock);
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$shopRepo = $this->em->getRepository(Shop::getClassName());

		$values = [
			'pohodaCode' => $this->stock->pohodaCode,
			'barcode' => $this->stock->barcode,
			'gift' => $this->stock->gift,
			'heurekaShow' => $this->stock->heurekaShow,
			'zboziShow' => $this->stock->zboziShow,
		];
		foreach ($shopRepo->findAll() as $shop) {
			$attrName = 'active' . $shop->priceLetter;
			$values[$attrName] = $this->stock->$attrName;
		}
		if ($this->stock->product) {
			$this->stock->product->setCurrentLocale($this->translator->getLocale());
			$values += [
				'name' => $this->stock->product->name,
				'perex' => $this->stock->product->perex,
				'description' => $this->stock->product->description,
			];
		}
		return $values;
	}

}

interface IStockBasicFactory
{

	/** @return StockBasic */
	function create();
}
