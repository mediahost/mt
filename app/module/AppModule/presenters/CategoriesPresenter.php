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
		
//		$this->createDemoCategories();
		
		$this->template->categories = $categoryRepo->findAll();
	}
	
	private function createDemoCategories()
	{
		$categoryRepo = $this->em->getRepository(Category::getClassName());
		
		$category1 = new Category('Ladies');
		$category1->translate('cs')->name = 'Ženy';
		$category1->translate('sk')->name = 'Ženy';
		$category1->mergeNewTranslations();
		$categoryRepo->save($category1);
		
		$category2 = new Category('Shoes');
		$category2->parent = $category1;
		$category2->mergeNewTranslations();
		$categoryRepo->save($category2);
		
		$category2 = new Category('Jeans');
		$category2->parent = $category1;
		$category2->mergeNewTranslations();
		$categoryRepo->save($category2);
		
		$category2 = new Category('T-Shirts');
		$category2->parent = $category1;
		$category2->mergeNewTranslations();
		$categoryRepo->save($category2);
		
		$category1 = new Category('Mens');
		$category1->translate('cs')->name = 'Muži';
		$category1->translate('sk')->name = 'Muži';
		$category1->mergeNewTranslations();
		$categoryRepo->save($category1);
		
		$category2 = new Category('Shoes');
		$category2->parent = $category1;
		$category2->mergeNewTranslations();
		$categoryRepo->save($category2);
		
		$category3 = new Category('Classic');
		$category3->parent = $category2;
		$category3->mergeNewTranslations();
		$categoryRepo->save($category3);
		
		$category4 = new Category('Classic 1');
		$category4->parent = $category3;
		$category4->mergeNewTranslations();
		$categoryRepo->save($category4);
		
		$category4 = new Category('Classic 2');
		$category4->parent = $category3;
		$category4->mergeNewTranslations();
		$categoryRepo->save($category4);
		
		$category3 = new Category('Sport');
		$category3->parent = $category2;
		$category3->mergeNewTranslations();
		$categoryRepo->save($category3);
		
		$category4 = new Category('Sport 1');
		$category4->parent = $category3;
		$category4->mergeNewTranslations();
		$categoryRepo->save($category4);
		
		$category4 = new Category('Sport 2');
		$category4->parent = $category3;
		$category4->mergeNewTranslations();
		$categoryRepo->save($category4);
		
		$category2 = new Category('Trainers');
		$category2->parent = $category1;
		$category2->mergeNewTranslations();
		$categoryRepo->save($category2);
		
		$category2 = new Category('Jeans');
		$category2->parent = $category1;
		$category2->mergeNewTranslations();
		$categoryRepo->save($category2);
		
		$category2 = new Category('Chinos');
		$category2->parent = $category1;
		$category2->mergeNewTranslations();
		$categoryRepo->save($category2);
		
		$category2 = new Category('T-Shirts');
		$category2->parent = $category1;
		$category2->mergeNewTranslations();
		$categoryRepo->save($category2);
		
		$category1 = new Category('Kids');
		$category1->mergeNewTranslations();
		$categoryRepo->save($category1);
		
		$category1 = new Category('Accessories');
		$category1->mergeNewTranslations();
		$categoryRepo->save($category1);
		
		$category1 = new Category('Sports');
		$category1->mergeNewTranslations();
		$categoryRepo->save($category1);
	}

}
