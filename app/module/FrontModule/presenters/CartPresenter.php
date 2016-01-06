<?php

namespace App\FrontModule\Presenters;

use App\Components\Basket\Form\GoodsList;
use App\Components\Basket\Form\IGoodsListFactory;
use App\Components\Basket\Form\IPaymentsFactory;
use App\Components\Basket\Form\IPersonalFactory;
use App\Components\Basket\Form\Payments;
use App\Components\Basket\Form\Personal;
use App\Helpers;
use App\Model\Entity\Basket;
use App\Model\Entity\Category;
use App\Model\Entity\Order;
use App\Model\Entity\Shipping;
use App\Model\Facade\Exception\ItemsIsntOnStockException;
use Doctrine\ORM\NoResultException;
use HeurekaOvereno;
use HeurekaOverenoException;
use Nette\Utils\Html;
use Tracy\Debugger;

class CartPresenter extends BasePresenter
{

	/** @var IGoodsListFactory @inject */
	public $iGoodsListFactory;

	/** @var IPaymentsFactory @inject */
	public $iPaymentsFactory;

	/** @var IPersonalFactory @inject */
	public $iPersonalFactory;

	public function renderDefault()
	{
		$categoryRepo = $this->em->getRepository(Category::getClassName());
		$specialCategoriesIds = Category::getSpecialCategories();
		$specialCategoriesLinks = NULL;
		foreach ($specialCategoriesIds as $specialCategoryId) {
			$specialCategory = $categoryRepo->find($specialCategoryId);
			if ($specialCategory) {
				$specialCategory->setCurrentLocale($this->locale);
				$link = $this->link('Category:', $specialCategory->id);
				$specialCategoryLink = Html::el('a')->href($link)->setText($specialCategory->name);
				$specialCategoriesLinks = Helpers::concatStrings(', ', $specialCategoriesLinks, $specialCategoryLink);
			}
		}

		$basket = $this->basketFacade->basket;
		$shippingRepo = $this->em->getRepository(Shipping::getClassName());
		$shipping = $shippingRepo->find(Shipping::DPD);

		$freeShippingPrice = $shipping->freePrice->withVat;
		$specialFreeShippingPrice = Shipping::SPECIAL_LIMIT;
		$productsTotal = $basket->getItemsTotalPrice(NULL, $this->priceLevel, TRUE);
		$specialTotal = $basket->getSumOfItemsInSpecialCategory($this->priceLevel, TRUE);

		$buyMore = $freeShippingPrice - $productsTotal;
		$buySpecialMore = $specialFreeShippingPrice - $specialTotal;

		if (!$this->basketFacade->isEmpty()) {
			if ($buyMore > 0) {
				$this->template->buyMore = $this->exchange->format($buyMore);
			}
			if ($buySpecialMore > 0) {
				$this->template->buySpecialMore = $this->exchange->format($buySpecialMore);
				$this->template->specialCategoriesLinks = $specialCategoriesLinks;
			}
		}
	}

	public function actionUncomplete($cart)
	{
		$basketRepo = $this->em->getRepository(Basket::getClassName());
		$uncompleteBasket = $basketRepo->findOneByAccessHash($cart);
		
		if ($uncompleteBasket) {
			$this->basketFacade->import($uncompleteBasket);
			$this->flashMessage($this->translator->translate('cart.recovered'), 'success');
		} else {
			$this->flashMessage($this->translator->translate('cart.notFound'), 'warning');
		}
		
		$this->redirect('default');
	}

	public function actionPayments()
	{
		$this->checkEmptyCart();
	}

	public function actionAddress()
	{
		$this->checkEmptyCart();
		$this->checkSelectedPayments();
	}

	public function actionSummary()
	{
		$this->checkEmptyCart();
		$this->checkSelectedPayments();
		$this->checkFilledAddress();

		if (!$this->basketFacade->isAllItemsInStore()) {
			$this->redirect('default');
		}

		$this->template->termsLink = $this->link('Page:terms');
	}

	public function handleSend()
	{
		$this->checkEmptyCart();
		$this->checkSelectedPayments();
		$this->checkFilledAddress();

		try {
			$basket = $this->basketFacade->getBasket();
			$user = $this->user->id ? $this->user->identity : NULL;
			$order = $this->orderFacade->createFromBasket($basket, $user);
			$this->basketFacade->clearBasket();

			$this->getSessionSection()->orderId = $order->id;

			$this->redirect('done');
		} catch (ItemsIsntOnStockException $ex) {
			$this->redirect('default');
		}
	}

	public function actionDone()
	{
		$orderId = $this->getSessionSection()->orderId;
		$orderRepo = $this->em->getRepository(Order::getClassName());

		try {
			if ($orderId) {
				$order = $orderRepo->find($orderId);
				if (!$order) {
					throw new NoResultException();
				}
				$this->sendHeurekaOvereno($order);
			} else {
				throw new NoResultException();
			}
		} catch (NoResultException $e) {
			$this->flashMessage($this->translator->translate('cart.order.wasntFoundWasExecuted'), 'info');
			$this->redirect('Homepage:');
		}

		$this->getSessionSection()->orderId = NULL;

		$heurekaSettings = $this->settings->modules->heureka;
		if ($heurekaSettings->enabled) {
			$this->template->heurekaConversionKey = $heurekaSettings->keyConversion;
		}
		$this->template->order = $order;
	}

	private function sendHeurekaOvereno(Order $order)
	{
		$heurekaSettings = $this->settings->modules->heureka;
		if ($heurekaSettings->enabled) {
			try {
				$overeno = new HeurekaOvereno($heurekaSettings->keyOvereno, HeurekaOvereno::LANGUAGE_SK);
				$overeno->setEmail($order->mail);
				$overeno->addOrderId($order->id);
				foreach ($order->items as $item) {
					$overeno->addProductItemId($item->stock->id);
				}
				$overeno->send();
			} catch (HeurekaOverenoException $ex) {
				Debugger::log($ex->getMessage(), 'heureka-overeno');
			}
		}
	}

	private function checkEmptyCart()
	{
		if ($this->basketFacade->isEmpty()) {
			$this->redirect('default');
		}
	}

	private function checkSelectedPayments()
	{
		if (!$this->basketFacade->hasPayments()) {
			$this->redirect('payments');
		}
	}

	private function checkFilledAddress()
	{
		if (!$this->basketFacade->hasAddress()) {
			$this->redirect('address');
		}
	}

	private function getSessionSection()
	{
		$section = $this->getSession(get_class($this));
		if (!$section->orderId) {
			$section->orderId = NULL;
		}
		return $section;
	}

	/** @return GoodsList */
	public function createComponentGoodsList()
	{
		$control = $this->iGoodsListFactory->create();
		$control->setPriceLevel($this->priceLevel);
		$control->setAjax();
		$control->onSend = function () {
			$this->redirect('payments');
		};
		return $control;
	}

	/** @return Payments */
	public function createComponentPayments()
	{
		$control = $this->iPaymentsFactory->create();
		$control->setPriceLevel($this->priceLevel);
		$control->setAjax();
		$control->onSend = function () {
			$this->redirect('address');
		};
		return $control;
	}

	/** @return Personal */
	public function createComponentPersonal()
	{
		$control = $this->iPersonalFactory->create();
		$control->onAfterSave = function () {
			$this->redirect('summary');
		};
		return $control;
	}

}
