<?php

namespace App\AppModule\Presenters;

use App\Model\Entity\Product;
use Tracy\Debugger;

class ProductsPresenter extends BasePresenter
{

	/**
	 * @secured
	 * @resource('products')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		$productRepo = $this->em->getRepository(Product::getClassName());
		$products = $productRepo->findAll();
		$product = array_pop($products);
		
		$product->setCurrentLocale($this->lang);
		Debugger::barDump($product->seo, $this->lang);
		
		$product->setCurrentLocale('sk');
		Debugger::barDump($product->seo, 'sk');
	}
	
	public function handleCreate()
	{
		$product = new Product($this->lang);
		$product->name = 'my name';
		$product->seo->name = 'moje seo';
		$product->seo->description = 'můj popisek';
		
		$product->setCurrentLocale('sk');
		
		$product->name = 'moj meno';
		$product->seo->name = 'po slovensky name';
		$product->seo->description = 'po slovensky description';
		
		$product->mergeNewTranslations();
		
		$productRepo = $this->em->getRepository(Product::getClassName());
		$productRepo->save($product);
		$this->redirect('this');
	}

}
