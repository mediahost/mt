<?php

namespace App\AjaxModule\Presenters;

use App\Model\Entity\Category;
use Doctrine\ORM\ORMException;
use Kdyby\Doctrine\DBALException;

class CategoriesPresenter extends BasePresenter
{

	public function actionGetSubcategories($parent, $lang = NULL)
	{
		$parentId = $parent === '#' ? NULL : $parent;

		$categoryRepo = $this->em->getRepository(Category::getClassName());
		$categories = $categoryRepo->findBy(['parent' => $parentId]);

		if (count($categories)) {
			foreach ($categories as $category) {
				/* @var $category Category */
				$category->setCurrentLocale($lang);
				$item = [];
				$item['id'] = (string) $category->id;
				$item['text'] = (string) $category;
				$item['children'] = $category->hasChildren;
				$item['type'] = 'loaded';
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
	public function actionCreateCategory($name, $parent, $lang = NULL)
	{
		$parentId = $parent === '#' ? NULL : $parent;
		$categoryRepo = $this->em->getRepository(Category::getClassName());

		if (empty($name)) {
			$this->setError('Name can\'t be empty.');
			return;
		}

		$category = new Category($name);
		$category->mergeNewTranslations();

		if ($parentId) {
			$parent = $categoryRepo->find($parentId);
			if (!$parent) {
				$this->setError('Parent category wasn\'t find.');
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
	public function actionRenameCategory($id, $name, $lang = NULL)
	{
		$categoryRepo = $this->em->getRepository(Category::getClassName());

		if (empty($name)) {
			$this->setError('Name can\'t be empty.');
			return;
		}
		if (empty($lang)) {
			$this->setError('Lang can\'t be empty.');
			return;
		}

		try {
			/* @var $category Category */
			$category = $categoryRepo->find($id);
			$category->translateAdd($lang)->name = $name;
			$category->mergeNewTranslations();

			$categoryRepo->save($category);
			$this->addData('name', $category->translate($lang)->name);
		} catch (ORMException $e) {
			$this->setError('ID can\'t be empty.');
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
				$this->setError('Category wasn\'t find.');
				return;
			}
			$categoryRepo->delete($category);
			$this->addData('id', $category->id);
		} catch (ORMException $e) {
			$this->setError('ID can\'t be empty.');
		} catch (DBALException $e) {
			$this->setError('Category can\'t be deleted.');
		}
	}

}
