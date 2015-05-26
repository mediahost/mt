<?php

namespace App\AppModule\Presenters;

use App\Model\Entity\Category;

class CategoriesPresenter extends BasePresenter
{

	/**
	 * @secured
	 * @resource('categories')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		$categoryRepo = $this->em->getRepository(Category::getClassName());
		
//		$parent = $categoryRepo->find(5);
//		
//		$category = new Category('Sixth');
//		$category->parent = $parent;
//		$categoryRepo->save($category);
		
		$category = $categoryRepo->find(6);
		$category->name = 'Sixth 6';
		$categoryRepo->save($category);
		
		$this->template->categories = $categoryRepo->findAll();
	}

}
