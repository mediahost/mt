<?php

namespace App\Components\Category\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Category;
use Nette\Utils\ArrayHash;

class CategoryEdit extends BaseControl
{

	/** @var Category */
	private $category;

	// <editor-fold desc="events">

	/** @var array */
	public $onAfterSave = [];

	// </editor-fold>

	/** @return Form */
	protected function createComponentForm()
	{
		$this->checkEntityExistsBeforeRender();

		$form = new Form;
		$form->setTranslator($this->translator);
		$form->setRenderer(new MetronicFormRenderer);
		
		$defaultLanguage = $this->languageService->defaultLanguage;

		$form->addText('name', 'Name')
				->setRequired('Name is required')
				->setAttribute('placeholder', $this->category->translate($defaultLanguage)->name);

		$form->addUploadImageWithPreview('image', 'Image')
				->setPreview('/foto/200-150/' . $this->category->image, $this->category->name)
				->setSize(200, 150)
				->addCondition(Form::FILLED)
				->addRule(Form::IMAGE, 'Image must be in valid image format');

		$form->addUploadImageWithPreview('slider', 'Slider')
				->setPreview('/foto/500-200/' . $this->category->slider, $this->category->name)
				->setSize(500, 200)
				->addCondition(Form::FILLED)
				->addRule(Form::IMAGE, 'Image must be in valid image format');

		$form->addWysiHtml('html', 'Text', 10)
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
		$this->onAfterSave($this->category);
	}

	private function load(ArrayHash $values)
	{
		$lang = $this->category->isNew() ? $this->languageService->defaultLanguage : $this->lang;
		$this->category->translateAdd($lang)->name = $values->name;
		$this->category->translateAdd($lang)->html = $values->html;
		$this->category->mergeNewTranslations();
		
		if ($values->image->isImage()) {
			$this->category->image = $values->image;
		}
		if ($values->slider->isImage()) {
			$this->category->slider = $values->slider;
		}
		return $this;
	}

	private function save()
	{
		$categoryRepo = $this->em->getRepository(Category::getClassName());
		$categoryRepo->save($this->category);
		return $this;
	}

	/** @return array */
	protected function getDefaults()
	{
		$values = [
			'name' => $this->category->name,
			'html' => $this->category->html,
			'image' => $this->category->image,
			'slider' => $this->category->slider,
		];
		return $values;
	}

	private function checkEntityExistsBeforeRender()
	{
		if (!$this->category) {
			throw new BaseControlException('Use setCategory(\App\Model\Entity\Category) before render');
		}
	}

	// <editor-fold desc="setters & getters">

	public function setCategory(Category $Category)
	{
		$this->category = $Category;
		return $this;
	}

	// </editor-fold>
}

interface ICategoryEditFactory
{

	/** @return CategoryEdit */
	function create();
}
