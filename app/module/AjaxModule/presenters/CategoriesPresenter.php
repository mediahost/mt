<?php

namespace App\AjaxModule\Presenters;

use App\Model\Entity\Category;
use Doctrine\ORM\ORMException;
use Kdyby\Doctrine\DBALException;

class CategoriesPresenter extends BasePresenter
{

	public function actionGetSubcategories($parent)
	{
		$parentId = $parent === '#' ? NULL : $parent;

		$categoryRepo = $this->em->getRepository(Category::getClassName());
		$categories = $categoryRepo->findBy(['parent' => $parentId], ['priority' => 'ASC']);

		if (count($categories)) {
			foreach ($categories as $category) {
				/* @var $category Category */
				$category->setCurrentLocale($this->locale);
				$item = [];
				$item['id'] = (string) $category->id;
				$item['text'] = (string) $category;
				$item['children'] = $category->hasChildren;
				$item['type'] = 'loaded';
				$item['order'] = $category->priority;
				$this->addRawData(NULL, $item);
			}
		} else {
			$this->setError('This category has no child.');
		}
	}

	/**
	 * @secured
	 * @resource('categories')
	 * @privilege('create')
	 */
	public function actionCreateCategory($name, $parent)
	{
		$parentId = $parent === '#' ? NULL : $parent;
		$categoryRepo = $this->em->getRepository(Category::getClassName());

		if (empty($name)) {
			$message = $this->translator->translate('cantBeEmpty', NULL, ['name' => $this->translator->translate('Name')]);
			$this->setError($message);
			return;
		}

		$category = new Category($name);
		$category->mergeNewTranslations();

		if ($parentId) {
			$parent = $categoryRepo->find($parentId);
			if (!$parent) {
				$message = $this->translator->translate('wasntFoundShe', NULL, ['name' => $this->translator->translate('Parent category')]);
				$this->setError($message);
				return;
			}
			$category->parent = $parent;
		} else {
			$category->parent = NULL;
		}

		$categoryRepo->save($category);
		$this->addData('id', $category->id);
	}

	/**
	 * @secured
	 * @resource('categories')
	 * @privilege('rename')
	 */
	public function actionRenameCategory($id, $name)
	{
		$categoryRepo = $this->em->getRepository(Category::getClassName());

		if (empty($name)) {
			$message = $this->translator->translate('cantBeEmpty', NULL, ['name' => $this->translator->translate('Name')]);
			$this->setError($message);
			return;
		}
		if (empty($this->locale)) {
			$message = $this->translator->translate('cantBeEmpty', NULL, ['name' => $this->translator->translate('Lang')]);
			$this->setError($message);
			return;
		}

		try {
			/* @var $category Category */
			$category = $categoryRepo->find($id);
			$category->translateAdd($this->locale)->name = $name;
			$category->mergeNewTranslations();

			$categoryRepo->save($category);
			$this->addData('name', $category->translate($this->locale)->name);
		} catch (ORMException $e) {
			$message = $this->translator->translate('cantBeEmptyIt', NULL, ['name' => $this->translator->translate('ID')]);
			$this->setError($message);
		}
	}

	/**
	 * @secured
	 * @resource('categories')
	 * @privilege('reorder')
	 */
	public function actionReorderCategory($id, $old, $new)
	{
		$categoryRepo = $this->em->getRepository(Category::getClassName());

		if (!$id) {
			$message = $this->translator->translate('cantBeEmpty', NULL, ['name' => $this->translator->translate('Id')]);
			$this->setError($message);
		}

		try {
			$entity = $categoryRepo->find($id);
			$this->categoryFacade->reorder($entity, $new, $old);
			$this->addData('order', $entity->priority);
		} catch (ORMException $e) {
			$message = $this->translator->translate('cantBeEmpty', NULL, ['name' => $this->translator->translate('ID')]);
			$this->setError($message);
		}
	}

	/**
	 * @secured
	 * @resource('categories')
	 * @privilege('delete')
	 */
	public function actionDeleteCategory($id)
	{
		$categoryRepo = $this->em->getRepository(Category::getClassName());

		try {
			/* @var $category Category */
			$category = $categoryRepo->find($id);
			if (!$category) {
				$message = $this->translator->translate('wasntFoundShe', NULL, ['name' => $this->translator->translate('Category')]);
				$this->setError($message);
				return;
			}
			$categoryRepo->delete($category);
			$this->addData('id', $category->id);
		} catch (ORMException $e) {
			$message = $this->translator->translate('cantBeEmptyIt', NULL, ['name' => $this->translator->translate('ID')]);
			$this->setError($message);
		} catch (DBALException $e) {
			$message = $this->translator->translate('cannotDeleteShe', NULL, ['name' => $this->translator->translate('Category')]);
			$this->setError($message);
		}
	}

}
