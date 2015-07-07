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

		$form = new Form();
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer());

		$imageSizeX = 200;
		$imageSizeY = 150;
		
		$form->addUploadImageWithPreview('image', 'Main Image')
				->setPreview("/foto/{$imageSizeX}-{$imageSizeY}/" . ($this->stock->product->image ? $this->stock->product->image : 'default.png'), $this->stock->product->name)
				->setSize($imageSizeX, $imageSizeY)
				->addCondition(Form::FILLED)
				->addRule(Form::IMAGE, 'Image must be in valid image format');

		$form->addUploadImageWithPreview('next', 'New other image', TRUE)
				->setTexting('Select new')
				->setPreview("/foto/{$imageSizeY}-0/default.png", 'Next image')
				->setSize($imageSizeX, $imageSizeY)
				->addCondition(Form::FILLED)
				->addRule(Form::IMAGE, 'Image must be in valid image format');

		$form->addSubmit('save', 'Upload');

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
		foreach ($values->next as $image) {
			$this->stock->product->otherImage = $image;
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
	
	public function render()
	{
		$this->template->images = $this->stock->product->images;
		parent::render();
	}

}

interface IStockImageFactory
{

	/** @return StockImage */
	function create();
}
