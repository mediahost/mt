<?php

namespace App\Components\Category\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Forms\Form;
use App\Forms\Renderers\MetronicFormRenderer;
use App\Model\Entity\Category;
use App\Model\Entity\Heureka\Category as HeurekaCategory;
use App\Model\Facade\HeurekaFacade;
use Nette\Utils\ArrayHash;

class CategoryEdit extends BaseControl
{

	/** @var Category */
	private $category;

	/** @var HeurekaFacade @inject */
	public $heurekaFacade;

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

		$defaultLanguage = $this->translator->getDefaultLocale();

		$form->addText('name', 'Name')
			->setRequired('Name is required')
			->setAttribute('placeholder', $this->category->translate($defaultLanguage)->name);

		$heurekaCategories = $this->heurekaFacade->getFullnames($this->translator->getLocale());
		$form->addSelect2('heurekaCategory', 'Heureka Category', [NULL => '--- No Category ---'] + $heurekaCategories);

		$form->addUploadImageWithPreview('image', 'Image')
			->setPreview('/foto/300-0/' . ($this->category->image ? $this->category->image : 'default.png'), $this->category->name)
			->setSize(300, 300)
			->addCondition(Form::FILLED)
			->addRule(Form::IMAGE, 'Image must be in valid image format');

		$form->addUploadImageWithPreview('slider', 'Slider')
			->setPreview('/foto/500-200/' . ($this->category->slider ? $this->category->slider : 'default.png'), $this->category->name)
			->setSize(500, 200)
			->addCondition(Form::FILLED)
			->addRule(Form::IMAGE, 'Image must be in valid image format');

		$form->addWysiHtml('html', 'Text', 10)
			->getControlPrototype()->class[] = 'page-html-content';

		$form->addSubmit('save', 'Save');
		if ($this->category->isNew()) {
			$form->addSubmit('saveAdd', 'Save & Add next');
		}

		$form->setDefaults($this->getDefaults());
		$form->onSuccess[] = $this->formSucceeded;
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$this->load($values);
		$this->save();

		$componentsArray = (array)$form->getComponents();
		$isSubmitedByAdd = array_key_exists('saveAdd', $componentsArray) ? $form['saveAdd']->submittedBy : FALSE;
		$this->onAfterSave($this->category, $isSubmitedByAdd);
	}

	private function load(ArrayHash $values)
	{
		$lang = $this->category->isNew() ? $this->translator->getDefaultLocale() : $this->translator->getLocale();
		$this->category->translateAdd($lang)->name = $values->name;
		$this->category->translateAdd($lang)->html = $values->html;
		$this->category->mergeNewTranslations();

		if ($values->heurekaCategory) {
			$heurekaCategoryRepo = $this->em->getRepository(HeurekaCategory::getClassName());
			$heurekaCategory = $heurekaCategoryRepo->find($values->heurekaCategory);
			if ($heurekaCategory) {
				$this->category->heurekaCategory = $heurekaCategory;
			}
		}

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
		if ($this->category->heurekaCategory) {
			$values['heurekaCategory'] = $this->category->heurekaCategory->id;
		}
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
