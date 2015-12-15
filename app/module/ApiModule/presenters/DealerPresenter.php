<?php

namespace App\ApiModule\Presenters;

use App\ExchangeHelper;
use App\Extensions\FilesManager;
use App\LocaleHelpers;
use App\Model\Entity\Address;
use App\Model\Entity\Basket;
use App\Model\Entity\Payment;
use App\Model\Entity\Rate;
use App\Model\Entity\Shipping;
use App\Model\Entity\Stock;
use App\Model\Entity\User;
use App\Model\Facade\Exception\FacadeException;
use App\Model\Facade\Exception\InsufficientQuantityException;
use App\Model\Facade\OrderFacade;
use App\Model\Facade\StockFacade;
use App\Model\Facade\UserFacade;
use Drahak\Restful\Application\Responses\TextResponse;
use Drahak\Restful\IResource;
use Drahak\Restful\Mapping\NullMapper;
use Drahak\Restful\Security\AuthenticationException;
use Drahak\Restful\Security\SecurityException;
use Nette\Http\Request;
use Nette\Utils\Strings;

class DealerPresenter extends BasePresenter
{

	const CLIENT_ID = 'client_id';

	/** @var UserFacade @inject */
	public $userFacade;

	/** @var StockFacade @inject */
	public $stockFacade;

	/** @var OrderFacade @inject */
	public $orderFacade;

	/** @var FilesManager @inject */
	public $filesManager;

	/** @var User */
	private $dealer;

	/** @var Request @inject */
	public $request;

	/** @var string */
	public $type = 'json';

	public function checkRequirements($element)
	{
		parent::checkRequirements($element);
		try {
			$clientId = $this->request->getPost(self::CLIENT_ID, $this->request->getQuery(self::CLIENT_ID));
			$this->checkClient($clientId);
		} catch (SecurityException $e) {
			$this->sendErrorResource($e, $this->typeMap[$this->type]);
		}
	}

	private function checkClient($clientId)
	{
		if ($clientId) {
			$this->dealer = $this->userFacade->findByClientId($clientId);
			if (!$this->dealer) {
				throw new AuthenticationException('Invalid client ID');
			}
			if (!$this->dealer->group || !$this->dealer->group->level) {
				throw new SecurityException('Invalid client settings. Please contact support.');
			}
		} else {
			throw new AuthenticationException('Missing client ID.');
		}
	}

	/**
	 * From pre saved XML
	 */
	public function actionReadStocks()
	{
		proc_nice(19);
		ini_set('max_execution_time', 60);

		if (!$this->settings->modules->dealer->enabled) {
			$this->resource->state = 'error';
			$this->resource->message = 'This module is not allowed';
		} else {
			$locale = $this->translator->getLocale();
			$filename = $this->filesManager->getExportFilename(FilesManager::EXPORT_DEALER_STOCKS, $locale);
			if (is_file($filename)) {
				$content = file_get_contents($filename);
				$response = new TextResponse($content, new NullMapper(), IResource::XML);
				$this->sendResponse($response);
			} else {
				$this->resource->state = 'error';
				$this->resource->message = 'Missing \'' . $locale . '\' translation for this export';
			}
		}
	}

	/**
	 * Generated XML
	 */
	public function actionReadAvailability($id = NULL, $currency = 'eur')
	{
		proc_nice(19);
		ini_set('max_execution_time', 60);

		if (!$this->settings->modules->dealer->enabled) {
			$this->resource->state = 'error';
			$this->resource->message = 'This module is not allowed';
		} else {
			switch ($currency) {
				case 'czk':
					$this->exchange->setWeb('CZK');
					break;
			}

			if ($id) {
				$stockRepo = $this->em->getRepository(Stock::getClassName());
				$stocks[] = $stockRepo->find($id);
			} else {
				$stocks = $this->stockFacade->getExportStocksDetails(TRUE);
			}

			$this->template->stocks = $stocks;
			$this->template->level = $this->dealer->group->level;

			$this->setView('availiblity');
		}
	}

	/**
	 * Insert new order by POST parameters
	 */
	public function actionCreateOrder()
	{
		if (!$this->settings->modules->dealer->enabled) {
			$this->resource->state = 'error';
			$this->resource->message = 'This module is not allowed';
		} else {
			// set language
			$locale = $this->request->getPost('locale', $this->translator->getDefaultLocale());
			$availableLocales = LocaleHelpers::getLocalesFromTranslator($this->translator);
			if (in_array($locale, $availableLocales)) {
				$this->translator->setLocale($locale);
			}

			// set currency with rate
			$this->loadCurrencyRates();
			$currency = $this->request->getPost('currency', $this->exchange->getWeb()->getCode());
			if (array_key_exists(Strings::upper($currency), $this->exchange)) {
				$this->exchange->setWeb($currency);
			}

			// create order
			$basket = new Basket();

			$stocks = [];
			$stockIds = $this->request->getPost('stocks', []);

			try {
				$stockRepo = $this->em->getRepository(Stock::getClassName());
				foreach ($stockIds as $stockId => $stockQuantity) {
					if ($stockId) {
						$stock = $stockRepo->find($stockId);
						if ($stock) {
							$stocks[$stockId] = $stocks;
							$basket->setItem($stock, $stockQuantity);
						}
					}
				}
			} catch (InsufficientQuantityException $ex) {
				$this->resource->state = 'error';
				$this->resource->message = 'This quantity is not in our store';
				if ($stock) {
					$this->resource->detail = "ID: {$stock->id}; Required: {$stockQuantity}; In store: {$stock->inStore}";
				}
				return;
			}

			if (!count($stocks)) {
				$this->resource->state = 'error';
				$this->resource->message = 'No products to order';
				return;
			}

			$basket->mail = $this->dealer->mail;

			if (!$basket->billingAddress) {
				$basket->billingAddress = new Address();
			}
			$userBillingAddress = $this->dealer->billingAddress;
			if (!$userBillingAddress) {
				$this->resource->state = 'error';
				$this->resource->message = 'Missing billing address for your account. Open our web and fill your account info.';
				return;
			}
			$basket->billingAddress->import($this->dealer->billingAddress, TRUE);

			if (!$basket->shippingAddress) {
				$basket->shippingAddress = new Address();
			}
			$userShippingAddress = $this->dealer->getShippingAddress(TRUE);
			$basket->shippingAddress->import($userShippingAddress, TRUE);

			// set shipping and payment
			$shipping = NULL;
			$shippingId = $this->request->getPost('shipping');
			if ($shippingId) {
				$shipping = $this->em->getRepository(Shipping::getClassName())->findOneBy(['id' => $shippingId, 'active' => TRUE]);
			}
			$payment = NULL;
			$paymentId = $this->request->getPost('payment');
			if ($paymentId) {
				$payment = $this->em->getRepository(Payment::getClassName())->findOneBy(['id' => $paymentId, 'active' => TRUE]);
			}
			if (!$shipping) {
				$this->resource->state = 'error';
				$this->resource->message = 'Wrong shipping ID';
				return;
			} else if (!$payment) {
				$this->resource->state = 'error';
				$this->resource->message = 'Wrong payment ID';
				return;
			} else if (!$shipping->isAllowedPayment($payment)) {
				$this->resource->state = 'error';
				$this->resource->message = 'This payment isn\'t allowed for this shipping';
				return;
			}
			$basket->shipping = $shipping;
			$basket->payment = $payment;

			try {
				$order = $this->orderFacade->createFromBasket($basket, $this->dealer);
				$this->resource->state = 'ok';
				$this->resource->message = 'Order was created';
				$this->resource->orderId = $order->id;
				$this->resource->totalPrice = $order->getTotalPrice($this->exchange);
			} catch (FacadeException $ex) {
				$this->resource->state = 'error';
				$this->resource->message = 'Other error in order process. Please contact us.';
			}
		}
	}

	private function loadCurrencyRates()
	{
		$rateRepo = $this->em->getRepository(Rate::getClassName());
		$rates = $rateRepo->findValuePairs();

		$defaultCode = $this->exchange->getDefault()->getCode();
		foreach ($this->exchange as $code => $currency) {
			$isDefault = strtolower($code) === strtolower($defaultCode);
			$isInDb = array_key_exists($code, $rates);
			if (!$isDefault && $isInDb) {
				$rateRelated = ExchangeHelper::getRelatedRate($rates[$code], $currency);
				$this->exchange->addRate($code, $rateRelated);
			}
		}
	}

	protected function beforeRender()
	{
		$this->sendResource($this->typeMap[$this->type]);
		parent::beforeRender();
	}

}
