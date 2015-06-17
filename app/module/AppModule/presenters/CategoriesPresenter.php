<?php

namespace App\AppModule\Presenters;

use App\Components\Category\Form\CategoryEdit;
use App\Components\Category\Form\ICategoryEditFactory;
use App\Model\Entity\Category;
use App\TaggedString;

class CategoriesPresenter extends BasePresenter
{

	/** @var Category */
	private $category;

	/** @var ICategoryEditFactory @inject */
	public $iCategoryEditFactory;

	/**
	 * @secured
	 * @resource('categories')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		$categoryRepo = $this->em->getRepository(Category::getClassName());
		$this->template->categories = $categoryRepo->findAll();
	}

	/**
	 * @secured
	 * @resource('categories')
	 * @privilege('edit')
	 */
	public function actionEdit($id)
	{
		$categoryRepo = $this->em->getRepository(Category::getClassName());
		$this->category = $categoryRepo->find($id);
		if (!$this->category) {
			$this->flashMessage('Category wasn\'t find', 'warning');
			$this->redirect('default');
		}
		$this->category->setCurrentLocale($this->lang);
		$this['categoryForm']->setCategory($this->category);
	}

	public function renderEdit()
	{
		$this->template->category = $this->category;
	}

	// <editor-fold desc="forms">

	/** @return CategoryEdit */
	public function createComponentCategoryForm()
	{
		$control = $this->iCategoryEditFactory->create();
		$control->setLang($this->lang);
		$control->onAfterSave = function (Category $savedCategory) {
			$message = new TaggedString('Category \'%s\' was successfully saved.', (string) $savedCategory);
			$this->flashMessage($message, 'success');
			$this->redirect('default');
		};
		return $control;
	}

	// </editor-fold>
}
