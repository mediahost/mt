<?php

namespace App\AppModule\Presenters;

use App\Model\Entity\Discount;
use App\Model\Entity\Group;
use App\Model\Entity\Price;
use App\Model\Entity\Product;
use App\Model\Entity\Vat;
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
		/* @var $product Product */
		$product = array_pop($products);
		
		$product->setCurrentLocale($this->lang);
		Debugger::barDump($product->seo, $this->lang);
		
		$product->setCurrentLocale('sk');
		Debugger::barDump($product->seo, 'sk');
	}
	
	public function handleCreate()
	{
		$vatRepo = $this->em->getRepository(Vat::getClassName());
		$vat = $vatRepo->find(1);
		
		$groupRepo = $this->em->getRepository(Group::getClassName());
		$group1 = $groupRepo->find(1);
		$group2 = $groupRepo->find(2);
		$group3 = $groupRepo->find(3);
		
		$discount1 = new Discount(5);
		$discount2 = new Discount(30, Discount::MINUS_VALUE);
		$discount3 = new Discount(90, Discount::FIXED_PRICE);
		
		$productRepo = $this->em->getRepository(Product::getClassName());
//		$products = $productRepo->findAll();
//		/* @var $product Product */
//		$product = array_pop($products);
		
		$product = new Product($this->lang);
		$product->name = 'my name';
		$product->price = new Price($vat, 100);
		$product->addDiscount($discount1, $group1);
		$product->addDiscount($discount2, $group2);
		$product->addDiscount($discount3, $group3);
		$product->seo->name = 'moje seo';
		$product->seo->description = 'můj popisek';
		
		$product->setCurrentLocale('sk');
		
		$product->name = 'moj meno';
		$product->seo->name = 'po slovensky name';
		$product->seo->description = 'po slovensky description';
		
		$product->mergeNewTranslations();
		
		$productRepo->save($product);
		$this->redirect('this');
	}

}
