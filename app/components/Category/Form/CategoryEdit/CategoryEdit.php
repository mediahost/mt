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
		if ($this->category->isNew()) {
			$defaultLang = $this->languageService->defaultLanguage;
			$this->category->translateAdd($defaultLang)->name = $values->name;
		}
		$this->category->translateAdd($this->lang)->name = $values->name;
		$this->category->mergeNewTranslations();
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
