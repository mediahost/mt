<?php

namespace App\FrontModule\Presenters;

use App\Components\Basket\Form\GoodsList;
use App\Components\Basket\Form\IGoodsListFactory;
use App\Components\Basket\Form\IPaymentsFactory;
use App\Components\Basket\Form\IPersonalFactory;
use App\Components\Basket\Form\Payments;
use App\Components\Basket\Form\Personal;
use App\Extensions\HomeCredit;
use App\Helpers;
use App\Mail\Messages\Order\Payment\IErrorPaymentFactory;
use App\Mail\Messages\Order\Payment\ISuccessPaymentFactory;
use App\Model\Entity\Basket;
use App\Model\Entity\Category;
use App\Model\Entity\Order;
use App\Model\Entity\Shipping;
use App\Model\Facade\Exception\ItemsIsntOnStockException;
use Doctrine\ORM\NoResultException;
use Heureka\ShopCertification;
use Nette\Utils\Html;
use Pixidos\GPWebPay\Components\GPWebPayControl;
use Pixidos\GPWebPay\Components\GPWebPayControlFactory;
use Pixidos\GPWebPay\Exceptions\GPWebPayException;
use Pixidos\GPWebPay\Operation;
use Pixidos\GPWebPay\Request;
use Pixidos\GPWebPay\Response;
use Tracy\Debugger;

class CartPresenter extends BasePresenter
{

	/** @var IGoodsListFactory @inject */
	public $iGoodsListFactory;

	/** @var IPaymentsFactory @inject */
	public $iPaymentsFactory;

	/** @var IPersonalFactory @inject */
	public $iPersonalFactory;

	/** @var GPWebPayControlFactory @inject */
	public $gpWebPayFactory;

	/** @var HomeCredit @inject */
	public $homecredit;

	/** @var ISuccessPaymentFactory @inject */
	public $iSuccessPaymentFactory;

	/** @var IErrorPaymentFactory @inject */
	public $iErrorPaymentFactory;

	public function renderDefault()
	{
		$categoryRepo = $this->em->getRepository(Category::getClassName());
		$specialCategoriesIds = Category::getSpecialCategories();
		$specialCategoriesLinks = NULL;
		foreach ($specialCategoriesIds as $specialCategoryId) {
			$specialCategory = $categoryRepo->find($specialCategoryId);
			if ($specialCategory) {
				$specialCategory->setCurrentLocale($this->locale);
				$link = $this->link('Category:', ['c' => $specialCategory->getUrlId(), 'slug' => $specialCategory->getSlug()]);
				$specialCategoryLink = Html::el('a')->href($link)->setText($specialCategory->name);
				$specialCategoriesLinks = Helpers::concatStrings(', ', $specialCategoriesLinks, $specialCategoryLink);
			}
		}

		$basket = $this->basketFacade->basket;
		$shippingRepo = $this->em->getRepository(Shipping::getClassName());
		$shipping = $shippingRepo->findOneBy([
			'freePrice >' => 0,
			'shopVariant' => $this->shopVariant,
		], [
			'freePrice' => 'DESC',
		]);

		$currency = $this->exchange->getWeb()->getCode();
		$freeShippingPrice = $shipping->freePrice->withVat;
		$specialFreeShippingPrice = Shipping::getSpecialLimit($currency);
		$productsTotal = $basket->getItemsTotalPrice(NULL, $this->priceLevel, TRUE);
		$specialTotal = $basket->getSumOfItemsInSpecialCategory($this->priceLevel, TRUE);

		$buyMore = $freeShippingPrice - $productsTotal;
		$buySpecialMore = $specialFreeShippingPrice - $specialTotal;

		if (!$this->basketFacade->isEmpty()) {
			if ($buyMore > 0) {
				$this->template->buyMore = $this->exchange->format($buyMore, $this->shopVariant->currency, $currency);
			}
			if ($buySpecialMore > 0) {
				$this->template->buySpecialMore = $this->exchange->format($buySpecialMore, NULL, $currency);
				$this->template->specialCategoriesLinks = $specialCategoriesLinks;
			}
		}

//		$this->template->visitedStocks = $this->user->storage->getVisited();
	}

	public function actionUncomplete($cart)
	{
		$basketRepo = $this->em->getRepository(Basket::getClassName());
		$uncompleteBasket = $basketRepo->findOneByAccessHash($cart);

		if ($uncompleteBasket) {
			$uncompleteBasket->setShopVariant($this->shopVariant);
			$this->basketFacade->import($uncompleteBasket, FALSE);
			$this->flashMessage($this->translator->translate('cart.recovered'), 'success');
		} else {
			$this->flashMessage($this->translator->translate('cart.notFound'), 'warning');
		}

		$this->redirect('default');
	}

	public function actionPayments()
	{
		$this->checkEmptyCart();
		$this->basketFacade->checkPayments();
	}

	public function actionAddress()
	{
		$this->checkEmptyCart();
		$this->checkSelectedPayments();
	}

	public function actionSummary()
	{
		if ($this->getParameter('do') === 'webPay-success') {
			return;
		}

		$this->checkEmptyCart();
		$this->checkSelectedPayments();
		$this->checkFilledAddress();

		if (!$this->basketFacade->isAllItemsInStore()) {
			$this->redirect('default');
		}

		$this->template->directPayment = $this->basketFacade->isDirectPayment();
		$this->template->termsLink = $this->link('Page:terms');
	}

	public function handleSend()
	{
		$this->checkEmptyCart();
		$this->checkSelectedPayments();
		$this->checkFilledAddress();

		try {
			$payByCard = $this->basketFacade->isCardPayment();
			$payByHomecredit = $this->basketFacade->isHomecreditSkPayment();

			$basket = $this->basketFacade->getBasket();
			$user = $this->user->id ? $this->user->identity : NULL;
			$order = $this->orderFacade->createFromBasket($basket, $user);
			$this->basketFacade->clearBasket();

			$this->getSessionSection()->orderId = $order->id;

			if ($payByCard) {
				$this['webPay']->handleCheckout();
			} else if ($payByHomecredit) {
				$this->homecredit->setOrder($order);
				$this->homecredit->setReturnLink($this->link('//:Front:Cart:done'));
				$this->redirectUrl($this->homecredit->getIShopLink());
			} else {
				$this->redirect('done');
			}
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
		$order->payment->origin->setCurrentLocale($this->locale);
		$this->template->order = $order;
	}

	private function sendHeurekaOvereno(Order $order)
	{
		$heurekaSettings = $this->settings->modules->heureka;
		if ($heurekaSettings->enabled) {
			try {
				$options = [
					'service' => ShopCertification::HEUREKA_SK,
				];
				$shopCertification = new ShopCertification($heurekaSettings->keyOvereno, $options);
				$shopCertification->setEmail($order->mail);
				$shopCertification->setOrderId($order->id);
				foreach ($order->items as $item) {
					$shopCertification->addProductItemId($item->stock->id);
				}
				$shopCertification->logOrder();
			} catch (ShopCertification\Exception $ex) {
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
		$this->basketFacade->checkPayments();
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
		$control->setShopVariant($this->shopVariant)
			->setAllowDiscount($this->settings->modules->discount->enabled)
			->setPriceLevel($this->priceLevel)
			->setAjax();
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

	/** @return GPWebPayControl */
	public function createComponentWebPay()
	{
		$orderId = $this->getSessionSection()->orderId;
		$orderRepo = $this->em->getRepository(Order::getClassName());
		if ($orderId) {
			$order = $orderRepo->find($orderId);
		}

		if (isset($order) && $order) {
			/* @var $order Order */
			$this->exchange->setWeb($order->currency);
			$totalPrice = $order->getTotalPriceToPay($this->exchange);
			switch ($order->currency) {
				case 'CZK':
					$curencyCode = Operation::CZK;
					break;
				case 'EUR':
				default:
					$curencyCode = Operation::EUR;
					break;
			}

			$operation = new Operation($order->id, $totalPrice, $curencyCode);
		} else {
			Debugger::log("Order to pay by card was't finded. '\$orderId = {$orderId}'", 'card_payment');
			$this->redirect('done');
		}

		$control = $this->gpWebPayFactory->create($operation);

		$control->onCheckout[] = function (GPWebPayControl $control, Request $request) {

		};

		$control->onSuccess[] = function (GPWebPayControl $control, Response $response) use ($order) {
			$this->orderFacade->payOrder($order, Order::PAYMENT_BLAME_CARD);
			$mail = $this->iSuccessPaymentFactory->create();
			$mail->addTo($order->mail)
				->setOrder($order)
				->send();
			$this->redirect('done');
		};
		$control->onError[] = function (GPWebPayControl $control, GPWebPayException $exception) use ($order) {
			Debugger::log('ORDER: ' . $order->id . '; ' . $exception->getMessage(), 'card_payment_errors');
			$mail = $this->iErrorPaymentFactory->create();
			$mail->addTo($order->mail)
				->setOrder($order)
				->send();
			$this->redirect('done');
		};

		return $control;
	}

}
