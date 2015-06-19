<?php

namespace App\Components\Product\Form;

use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Stock;
use App\Model\Facade\UnitFacade;
use Nette\Utils\ArrayHash;

class StockImage extends StockBase
{
	// <editor-fold desc="variables">

	/** @var UnitFacade @inject */
	public $unitFacade;

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form;
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());

		$form->addUploadImageWithPreview('image', 'Image')
				->setPreview('/foto/200-150/' . $this->stock->product->image, $this->stock->product->name)
				->setSize(200, 150)
				->addCondition(Form::FILLED)
				->addRule(Form::IMAGE, 'Image must be in valid image format');

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
		if ($values->image->isImage()) {
			$this->stock->product->image = $values->image;
		}

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
		$values = [
			'image' => $this->stock->product->image,
		];
		return $values;
	}

}

interface IStockImageFactory
{

	/** @return StockImage */
	function create();
}
