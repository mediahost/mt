<?php

namespace App\AppModule\Presenters;

use App\Components\Category\Form\CategoryEdit;
use App\Components\Category\Form\ICategoryEditFactory;
use App\Model\Entity\Category;

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
	public function actionDefault($categoryId)
	{
		if ($categoryId) {
			$categoryRepo = $this->em->getRepository(Category::getClassName());
			$this->category = $categoryRepo->find($categoryId);
			if (!$this->category) {
				$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('Category')]);
				$this->flashMessage($message, 'warning');
				$this->redirect('default');
			}
			$this->category->setCurrentLocale($this->locale);
			$this['categoryForm']->setCategory($this->category);
		}
	}

	public function renderDefault()
	{
		$this->template->category = $this->category;
		if ($this->isAjax()) {
			$this->redrawControl();
		}
	}

	/**
	 * @secured
	 * @resource('categories')
	 * @privilege('add')
	 */
	public function actionAdd()
	{
		$this->category = new Category();
		$this['categoryForm']->setCategory($this->category);
	}

	// <editor-fold desc="forms">

	/** @return CategoryEdit */
	public function createComponentCategoryForm()
	{
		$control = $this->iCategoryEditFactory->create();
		$control->onAfterSave = function (Category $savedCategory, $addNext) {
			$message = $this->translator->translate('successfullySaved', NULL, [
				'type' => $this->translator->translate('Category'), 'name' => (string) $savedCategory
			]);
			$this->flashMessage($message, 'success');
			if ($addNext) {
				$this->redirect('add');
			} else {
				$this->redirect('default', ['id' => $savedCategory->id]);
			}
		};
		return $control;
	}

	// </editor-fold>
}
