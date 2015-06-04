<?php

namespace App\AppModule\Presenters;

use App\Model\Entity\Category;
use App\Model\Entity\Discount;
use App\Model\Entity\Group;
use App\Model\Entity\Price;
use App\Model\Entity\Product;
use App\Model\Entity\Vat;

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

		if (!count($productRepo->findAll())) {
			$this->createDemoProducts();
		}

		$this->template->products = $productRepo->findAll();
	}

	private function createDemoProducts()
	{
		$categoryRepo = $this->em->getRepository(Category::getClassName());
		if (!count($categoryRepo->findAll())) {
			$this->flashMessage('Init categories before init products.');
			$this->flashMessage('Right now you can go to products.');
			$this->redirect('Categories:');
		}
		
		$vatRepo = $this->em->getRepository(Vat::getClassName());
		$vat = $vatRepo->find(1);
		if (!$vat) {
			$vat = new Vat(20);
			$vatRepo->save($vat);
		}

		$groupRepo = $this->em->getRepository(Group::getClassName());
		if (count($groupRepo->findAll()) < 3) {
			$group1 = new Group('Group 1');
			$group2 = new Group('Group 2');
			$group3 = new Group('Group 3');
			$groupRepo->save($group1);
			$groupRepo->save($group2);
			$groupRepo->save($group3);
		} else {
			$group1 = $groupRepo->find(1);
			$group2 = $groupRepo->find(2);
			$group3 = $groupRepo->find(3);
		}

		$discount1 = new Discount(5);
		$discount2 = new Discount(30, Discount::MINUS_VALUE);
		$discount3 = new Discount(950, Discount::FIXED_PRICE);

		$productRepo = $this->em->getRepository(Product::getClassName());

		$product = new Product();
		$product->translate('en')->name = 'First Product';
		$product->translate('en')->seo->name = 'my seo';
		$product->translate('en')->seo->description = 'my description';
		$product->translate('cs')->name = 'První produkt';
		$product->translate('cs')->seo->name = 'my seo';
		$product->translate('cs')->seo->description = 'my description';
		$product->mergeNewTranslations();
		$product->price = new Price($vat, 1000);
		$product->mainCategory = $categoryRepo->find(8);
		$product->addDiscount($discount1, $group1);
		$product->addDiscount($discount2, $group2);
		$product->addDiscount($discount3, $group3);

		$productRepo->save($product);

		$product = new Product();
		$product->translate('en')->name = 'Second Product';
		$product->translate('en')->seo->name = 'my seo';
		$product->translate('en')->seo->description = 'my description';
		$product->translate('cs')->name = 'Druhý produkt';
		$product->translate('cs')->seo->name = 'my seo';
		$product->translate('cs')->seo->description = 'my description';
		$product->mergeNewTranslations();
		$product->price = new Price($vat, 900);
		$product->mainCategory = $categoryRepo->find(8);

		$productRepo->save($product);

		$product = new Product();
		$product->translate('en')->name = 'Third Product';
		$product->translate('en')->seo->name = 'my seo';
		$product->translate('en')->seo->description = 'my description';
		$product->translate('cs')->name = 'Třetí produkt';
		$product->translate('cs')->seo->name = 'my seo';
		$product->translate('cs')->seo->description = 'my description';
		$product->mergeNewTranslations();
		$product->price = new Price($vat, 800);
		$product->mainCategory = $categoryRepo->find(8);

		$productRepo->save($product);

		$product = new Product();
		$product->translate('en')->name = 'Fourth Product';
		$product->translate('en')->seo->name = 'my seo';
		$product->translate('en')->seo->description = 'my description';
		$product->translate('cs')->name = 'Čtvrtý produkt';
		$product->translate('cs')->seo->name = 'my seo';
		$product->translate('cs')->seo->description = 'my description';
		$product->mergeNewTranslations();
		$product->price = new Price($vat, 700);
		$product->mainCategory = $categoryRepo->find(8);

		$productRepo->save($product);

		$product = new Product();
		$product->translate('en')->name = 'Fifth Product';
		$product->translate('en')->seo->name = 'my seo';
		$product->translate('en')->seo->description = 'my description';
		$product->translate('cs')->name = 'Pátý produkt';
		$product->translate('cs')->seo->name = 'my seo';
		$product->translate('cs')->seo->description = 'my description';
		$product->mergeNewTranslations();
		$product->price = new Price($vat, 600);
		$product->mainCategory = $categoryRepo->find(9);

		$productRepo->save($product);

		$product = new Product();
		$product->translate('en')->name = 'Sixth Product';
		$product->translate('en')->seo->name = 'my seo';
		$product->translate('en')->seo->description = 'my description';
		$product->translate('cs')->name = 'Šestý produkt';
		$product->translate('cs')->seo->name = 'my seo';
		$product->translate('cs')->seo->description = 'my description';
		$product->mergeNewTranslations();
		$product->price = new Price($vat, 500);
		$product->mainCategory = $categoryRepo->find(9);

		$productRepo->save($product);

		$product = new Product();
		$product->translate('en')->name = 'Seventh Product';
		$product->translate('en')->seo->name = 'my seo';
		$product->translate('en')->seo->description = 'my description';
		$product->translate('cs')->name = 'Sedmý produkt';
		$product->translate('cs')->seo->name = 'my seo';
		$product->translate('cs')->seo->description = 'my description';
		$product->mergeNewTranslations();
		$product->price = new Price($vat, 400);
		$product->mainCategory = $categoryRepo->find(9);

		$productRepo->save($product);

		$product = new Product();
		$product->translate('en')->name = 'Eighth Product';
		$product->translate('en')->seo->name = 'my seo';
		$product->translate('en')->seo->description = 'my description';
		$product->translate('cs')->name = 'Osmý produkt';
		$product->translate('cs')->seo->name = 'my seo';
		$product->translate('cs')->seo->description = 'my description';
		$product->mergeNewTranslations();
		$product->price = new Price($vat, 300);
		$product->mainCategory = $categoryRepo->find(9);

		$productRepo->save($product);

		$product = new Product();
		$product->translate('en')->name = 'Nineth Product';
		$product->translate('en')->seo->name = 'my seo';
		$product->translate('en')->seo->description = 'my description';
		$product->translate('cs')->name = 'Devátý produkt';
		$product->translate('cs')->seo->name = 'my seo';
		$product->translate('cs')->seo->description = 'my description';
		$product->mergeNewTranslations();
		$product->price = new Price($vat, 200);
		$product->mainCategory = $categoryRepo->find(9);

		$productRepo->save($product);

		$product = new Product();
		$product->translate('en')->name = 'Tenth Product';
		$product->translate('en')->seo->name = 'my seo';
		$product->translate('en')->seo->description = 'my description';
		$product->translate('cs')->name = 'Desátý produkt';
		$product->translate('cs')->seo->name = 'my seo';
		$product->translate('cs')->seo->description = 'my description';
		$product->mergeNewTranslations();
		$product->price = new Price($vat, 100);
		$product->mainCategory = $categoryRepo->find(9);

		$productRepo->save($product);

		$product = new Product();
		$product->translate('en')->name = 'Eleventh Product';
		$product->translate('en')->seo->name = 'my seo';
		$product->translate('en')->seo->description = 'my description';
		$product->translate('cs')->name = 'Jedenáctý produkt';
		$product->translate('cs')->seo->name = 'my seo';
		$product->translate('cs')->seo->description = 'my description';
		$product->mergeNewTranslations();
		$product->price = new Price($vat, 50);
		$product->mainCategory = $categoryRepo->find(9);

		$productRepo->save($product);
	}

}
