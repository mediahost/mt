<?php

namespace App\Components\Product\Form;

use App\Forms\Controls\TextInputBased\MetronicTextInputBase;
use App\Forms\Form;
use App\Forms\Renderers\MetronicHorizontalFormRenderer;
use App\Model\Entity\Category;
use App\Model\Entity\Stock;
use App\Model\Entity\Unit;
use App\Model\Entity\Vat;
use App\Model\Facade\CategoryFacade;
use App\Model\Facade\PohodaFacade;
use App\Model\Facade\VatFacade;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Utils\ArrayHash;

/**
 * Form ONLY for add stock
 */
class StockAdd extends StockBase
{
	// <editor-fold desc="variables">

	/** @var VatFacade @inject */
	public $vatFacade;

	/** @var CategoryFacade @inject */
	public $categoryFacade;

	/** @var PohodaFacade @inject */
	public $pohodaFacade;

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form();
		$form->setTranslator($this->translator)
				->setRenderer(new MetronicHorizontalFormRenderer());

		$unitRepo = $this->em->getRepository(Unit::getClassName());
		$defaultUnit = $unitRepo->find(1);
		$defaultUnit->setCurrentLocale($this->translator->getLocale());

		$categories = $this->categoryFacade->getCategoriesList($this->translator->getLocale());

		$form->addGroup();
		$form->addText('name', 'Product title', NULL, 150)
				->setAttribute('class', MetronicTextInputBase::SIZE_XL)
				->setRequired('Insert product name');
		$form->addText('pohodaCode', 'Code for Pohoda', NULL, 20)
				->setAttribute('placeholder', 'automaticly generated')
				->setAttribute('class', MetronicTextInputBase::SIZE_XL)
				->setOption('description', 'Identification for synchronizing');
		$form->addCheckSwitch('active', 'Active')
				->setDefaultValue(TRUE);

		$form->addGroup('Price');
		$form->addCheckSwitch('with_vat', 'Vat included', 'YES', 'NO')
				->setDefaultValue(TRUE);
		$form->addText('price', 'Price')
				->setAttribute('class', ['mask_currency', MetronicTextInputBase::SIZE_S])
				->setRequired();
		$form->addSelect2('vat', 'Vat', $this->vatFacade->getValues())
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_XS;

		$form->addGroup('Quantity');
		$form->addTouchSpin('quantity', 'Quantity')
				->setMax(1000)
				->setPostfix($defaultUnit)
				->setSize(MetronicTextInputBase::SIZE_M)
				->setDefaultValue(0);

		$form->addGroup('Category');
		$form->addSelect2('main_category', 'Main category', $categories)
						->setPrompt('Select some category')
						->setRequired('Select some category')
						->getControlPrototype()->class[] = MetronicTextInputBase::SIZE_XL;

		$form->addGroup('Description');
		$form->addWysiHtml('perex', 'Perex', 4)
						->getControlPrototype()->class[] = 'page-html-content';
		$form->addWysiHtml('description', 'Description', 10)
						->getControlPrototype()->class[] = 'page-html-content';

		$form->addSubmit('save', 'Save');

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		try {
			$this->load($values);
			$this->save();
			$this->onAfterSave($this->stock);
		} catch (UniqueConstraintViolationException $ex) {
			$form->addError('Unable to generate a unique code. Try send form again.');
		}
	}

	private function load(ArrayHash $values)
	{
		$this->loadStock($values);
		$this->loadProduct($values);

		return $this;
	}

	private function loadStock(ArrayHash $values)
	{
		$this->stock->pohodaCode = $values->pohodaCode ? $values->pohodaCode : $this->pohodaFacade->getNewCode();
		$this->stock->quantity = $values->quantity > 1 ? $values->quantity : 0;
		$this->stock->active = $values->active;

		$vatRepo = $this->em->getRepository(Vat::getClassName());
		$vat = $vatRepo->find($values->vat);
		$this->stock->vat = $vat;
		$this->stock->setDefaltPrice($values->price, $values->with_vat);

		return $this;
	}

	private function loadProduct(ArrayHash $values)
	{
		$this->stock->product->setCurrentLocale($this->translator->getDefaultLocale());
		$this->stock->product->name = $values->name;
		$this->stock->product->perex = $values->perex;
		$this->stock->product->description = $values->description;
		$this->stock->product->mergeNewTranslations();

		$categoryRepo = $this->em->getRepository(Category::getClassName());
		$category = $categoryRepo->find($values->main_category);
		$this->stock->product->mainCategory = $category;

		$unitRepo = $this->em->getRepository(Unit::getClassName());
		$unit = $unitRepo->findOneByName(Unit::DEFAULT_NAME);
		$this->stock->product->unit = $unit;

		$this->stock->product->active = $values->active;

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
		$values = [];
		return $values;
	}

}

interface IStockAddFactory
{

	/** @return StockAdd */
	function create();
}
