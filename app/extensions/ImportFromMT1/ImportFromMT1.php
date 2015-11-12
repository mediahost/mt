<?php

namespace App\Extensions;

use App\Helpers;
use App\Model\Entity\Address;
use App\Model\Entity\Facebook;
use App\Model\Entity\Group;
use App\Model\Entity\Newsletter\Subscriber;
use App\Model\Entity\Order;
use App\Model\Entity\OrderState;
use App\Model\Entity\Payment;
use App\Model\Entity\Role;
use App\Model\Entity\Shipping;
use App\Model\Entity\Stock;
use App\Model\Entity\Twitter;
use App\Model\Entity\User;
use App\Model\Entity\Vat;
use App\Model\Facade\Exception\InsufficientQuantityException;
use App\Model\Facade\NewsletterFacade;
use App\Model\Facade\OrderFacade;
use App\Model\Facade\UserFacade;
use Doctrine\ORM\EntityManager;
use Exception;
use h4kuna\Exchange\Exchange;
use Kdyby\Translation\Translator;
use Nette\Object;
use Nette\Utils\DateTime;

class ImportFromMT1 extends Object
{

	const MAX_INSERTS = 1000;
	const MAX_ORDERS = 100;
	const ORDER_CANCELED_ID = 6;
	const TABLE_PRODUCT = '`product`';
	const TABLE_ORDER = '`order`';
	const TABLE_ORDER_ADDRESS = '`order_address`';
	const TABLE_ORDER_PAYMENT = '`order_payment`';
	const TABLE_ORDER_SHIPPING = '`order_shipping`';
	const TABLE_ORDER_PARTS = '`order_parts`';
	const TABLE_USER = '`user`';
	const TABLE_AUTH = '`auth`';
	const TABLE_PASSWORDS = '`user_passwords`';
	const TABLE_ADDRESS = '`user_address`';
	const TABLE_NEWSLETTER = '`newsletter_register`';
	const TABLE_NEWSLETTER_DEALER = '`newsletter_register_dealer`';

	// <editor-fold desc="constants & variables">

	/** @var string */
	private $dbName;

	/** @var array */
	private $groupsMapping = [];

	/** @var int */
	private $limit = 0;

	/** @var int */
	private $maxInserts = self::MAX_INSERTS;

	// </editor-fold>
	// <editor-fold desc="injects">

	/** @var EntityManager @inject */
	public $em;

	/** @var Translator @inject */
	public $translator;

	/** @var Exchange @inject */
	public $exchange;

	/** @var UserFacade @inject */
	public $userFacade;

	/** @var NewsletterFacade @inject */
	public $newsletterFacade;

	/** @var OrderFacade @inject */
	public $orderFacade;

	// </editor-fold>
	// <editor-fold desc="setters">

	public function setDbName($db)
	{
		$this->dbName = $db;
		return $this;
	}

	public function setMapping(array $mappings)
	{
		if (array_key_exists('groups', $mappings)) {
			foreach ($mappings['groups'] as $key => $value) {
				$this->groupsMapping[(int) $key] = (int) $value;
			}
		}
		return $this;
	}

	// </editor-fold>

	public function getDbName()
	{
		if ($this->checkDbName($this->dbName)) {
			return '`' . $this->dbName . '`';
		} else {
			throw new ImportFromMT1Exception('DB name isn\'t right. Change it in configuration');
		}
	}

	private function checkDbName($name)
	{
		$stmt = $this->em->getConnection()
				->executeQuery('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?', [$name]);
		return (bool) $stmt->fetch();
	}

	public function downloadProducts()
	{
		ini_set('max_execution_time', 600);

		$this->importProducts();

		return $this;
	}

	public function actualizeProducts()
	{
		ini_set('max_execution_time', 60);

		$this->updateProducts();

		return $this;
	}

	public function downloadOrders()
	{
		ini_set('max_execution_time', 200);

		$this->importOrders();

		return $this;
	}

	public function actualizeOrders()
	{
		ini_set('max_execution_time', 200);

		$this->updateOrders();

		return $this;
	}

	public function downloadUsers()
	{
		ini_set('max_execution_time', 1500);

		$this->importUsersBasic();
		$this->importUsersSigns();
		$this->importUsersAddresses();
		$this->importSubscribers();

		return $this;
	}

	public function actualizeUsers()
	{
		ini_set('max_execution_time', 60);

		$this->updateUsers();

		return $this;
	}

	private function importProducts()
	{
		$this->maxInserts = self::MAX_INSERTS;
		$conn = $this->em->getConnection();
		$dbName = $this->getDbName();
		$tableProducts = $dbName . '.' . self::TABLE_PRODUCT;

		$userRepo = $this->em->getRepository(User::getClassName());
		$admin = $userRepo->findOneByMail('superadmin');

		$maxId = (int) $conn->executeQuery("SELECT MAX(id) FROM `stock`")->fetchColumn();
		$offset = 0;
		$limit = $this->maxInserts;
		$stmt = $conn->executeQuery(
				"SELECT id "
				. "FROM {$tableProducts} p "
				. "WHERE active = ? AND deleted = ? "
				. "AND id > ? "
				. "ORDER BY id "
				. "LIMIT {$limit} OFFSET {$offset}"
				, [1, 0, $maxId]);

		$stockTable = $this->em->getClassMetadata(Stock::getClassName())->getTableName();

		foreach ($stmt->fetchAll() as $oldData) {
			$this->checkLimit();
			$startNum = (int) $oldData['id'];
			$conn->executeQuery('ALTER TABLE `' . $stockTable . '` AUTO_INCREMENT=' . $startNum);

			$stock = new Stock();
			$stock->setQuantity(0);
			$stock->createdBy = $admin;
			$stock->updatedBy = $admin;
			$stock->product->createdBy = $admin;
			$stock->product->updatedBy = $admin;
			$this->em->persist($stock);
			$this->em->flush();
		}

		return $this;
	}

	private function updateProducts()
	{
		return $this;
	}

	private function importOrders()
	{
		$this->maxInserts = self::MAX_ORDERS;
		$conn = $this->em->getConnection();
		$dbName = $this->getDbName();
		$tableOrders = $dbName . '.' . self::TABLE_ORDER;
		$tableOrderAddresses = $dbName . '.' . self::TABLE_ORDER_ADDRESS;
		$tableOrderPayment = $dbName . '.' . self::TABLE_ORDER_PAYMENT;
		$tableOrderShipping = $dbName . '.' . self::TABLE_ORDER_SHIPPING;
		$tableOrderParts = $dbName . '.' . self::TABLE_ORDER_PARTS;

		$orderTable = $this->em->getClassMetadata(Order::getClassName())->getTableName();
		$stockRepo = $this->em->getRepository(Stock::getClassName());
		$vatRepo = $this->em->getRepository(Vat::getClassName());
		$stateRepo = $this->em->getRepository(OrderState::getClassName());
		$userRepo = $this->em->getRepository(User::getClassName());
		$paymentRepo = $this->em->getRepository(Payment::getClassName());
		$shippingRepo = $this->em->getRepository(Shipping::getClassName());
		$admin = $userRepo->findOneByMail('superadmin');

		$maxId = (int) $conn->executeQuery("SELECT MAX(id) FROM `{$orderTable}`")->fetchColumn();
		$offset = 0;
		$limit = $this->maxInserts;
		$canceledStatusId = self::ORDER_CANCELED_ID;
		$stmt = $conn->executeQuery(
				"SELECT * "
				. "FROM {$tableOrders} o "
				. "WHERE deleted = ? AND order_status_id != ? "
				. "AND code > ? "
				. "ORDER BY code "
				. "LIMIT {$limit} OFFSET {$offset}"
				, [0, $canceledStatusId, $maxId]);

		foreach ($stmt->fetchAll() as $oldData) {
			$this->checkLimit();
			$orderID = (int) $oldData['id'];
			$orderCode = (int) $oldData['code'];
			$conn->executeQuery('ALTER TABLE `' . $orderTable . '` AUTO_INCREMENT=' . $orderCode);

			$addrBilling = $conn->executeQuery(
							"SELECT * "
							. "FROM {$tableOrderAddresses} oa "
							. "WHERE type = ? AND order_id = ? "
							, ['billing', $orderID])->fetch();
			$addrDelivery = $conn->executeQuery(
							"SELECT * "
							. "FROM {$tableOrderAddresses} oa "
							. "WHERE type = ? AND order_id = ? "
							, ['billing', $orderID])->fetch();

			$billingAddress = new Address();
			try {
				if ($addrBilling['company']) {
					$billingAddress->name = $addrBilling['company'];
				} else {
					$billingAddress->name = Helpers::concatStrings(' ', $addrBilling['firstname'], $addrBilling['surname']);
				}
				$billingAddress->street = $addrBilling['street'];
				$billingAddress->city = $addrBilling['city'];
				switch ($addrBilling['country']) {
					case 'CZE':
						$billingAddress->country = 'CZ';
						break;
					default:
						$billingAddress->country = 'SK';
						break;
				}
				$billingAddress->zipcode = $addrBilling['zipcode'];
				$billingAddress->phone = $addrBilling['phone'];
				$billingAddress->ico = $addrBilling['ico'];
				$billingAddress->dic = $addrBilling['dic'];
				$billingAddress->icoVat = $addrBilling['icdph'];
			} catch (Exception $ex) {
				throw new WrongSituationException('Wrong billing address for order with ID: ' . $orderID);
			}

			$shippingAddress = new Address();
			try {
				if ($addrDelivery['company']) {
					$shippingAddress->name = $addrDelivery['company'];
				} else {
					$shippingAddress->name = Helpers::concatStrings(' ', $addrDelivery['firstname'], $addrDelivery['surname']);
				}
				$shippingAddress->street = $addrDelivery['street'];
				$shippingAddress->city = $addrDelivery['city'];
				switch ($addrDelivery['country']) {
					case 'CZE':
						$shippingAddress->country = 'CZ';
						break;
					default:
						$shippingAddress->country = 'SK';
						break;
				}
				$shippingAddress->zipcode = $addrDelivery['zipcode'];
				$shippingAddress->phone = $addrDelivery['phone'];
			} catch (Exception $ex) {
				
			}

			$orderPayment = $conn->executeQuery(
							"SELECT * "
							. "FROM {$tableOrderPayment} op "
							. "WHERE order_id = ? "
							, [$orderID])->fetch();
			if ($orderPayment) {
				/* @var $payment Payment */
				switch ($orderPayment['payment_id']) {
					case '3':
						$payment = $paymentRepo->find(Payment::ON_DELIVERY);
						break;
					case '5':
						$payment = $paymentRepo->find(Payment::BANK_ACCOUNT);
						break;
					case '6':
						$payment = $paymentRepo->find(Payment::PERSONAL);
						break;
					default:
						throw new WrongSituationException('Unknown payment (ID: ' . $orderPayment['payment_id'] . ') for order with ID: ' . $orderID);
				}
				$payment->setPrice($orderPayment['price'], $orderPayment['vat_included']);
			} else {
				throw new WrongSituationException('Missing payment for order with ID: ' . $orderID);
			}

			$orderShipping = $conn->executeQuery(
							"SELECT * "
							. "FROM {$tableOrderShipping} op "
							. "WHERE order_id = ? "
							, [$orderID])->fetch();
			if ($orderShipping) {
				/* @var $shipping Shipping */
				switch ($orderShipping['shipping_id']) {
					case '1':
						$shipping = $shippingRepo->find(Shipping::SLOVAK_POST);
						break;
					case '10':
						$shipping = $shippingRepo->find(Shipping::DPD);
						break;
					case '13':
						$shipping = $shippingRepo->find(Shipping::PERSONAL);
						break;
					default:
						throw new WrongSituationException('Unknown payment (ID: ' . $orderShipping['shipping_id'] . ') for order with ID: ' . $orderID);
				}
				$shipping->setPrice($orderShipping['price'], $orderShipping['vat_included']);
			} else {
				throw new WrongSituationException('Missing payment for order with ID: ' . $orderID);
			}

			if (!isset($addrBilling['mail'])) {
				throw new WrongSituationException('No email for order with ID: ' . $orderID);
			} else {
				$user = $userRepo->findOneByMail($addrBilling['mail']);
			}

			switch ($oldData['lang']) {
				case 'sk':
				case 'cs':
					$locale = $oldData['lang'];
					break;
				default:
					$locale = $this->translator->getLocale();
					break;
			}

			$order = new Order($locale, $user);
			$order->mail = $addrBilling['mail'];
			$order->ip = $oldData['ip'];
			$order->note = $oldData['private_notice'];
			$order->createdAt = DateTime::from($oldData['create_date']);
			$order->setPayment($payment);
			$order->setShipping($shipping);
			$order->state = $stateRepo->find(OrderState::ORDERED_IN_SYSTEM);

			if ($billingAddress->isFilled()) {
				$order->billingAddress = $billingAddress;
			}
			if ($shippingAddress->isFilled()) {
				$order->shippingAddress = $shippingAddress;
			}

			switch ($oldData['currency']) {
				case 'CZK':
					$order->setCurrency('CZK', $oldData['rate']);
					break;
				case 'EUR':
				default:
					$order->setCurrency('EUR', NULL);
					break;
			}

			if ($oldData['payment_date'] &&
					preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $oldData['payment_date']) &&
					$oldData['payment_date'] != '0000-00-00') {
				$order->paymentDate = DateTime::from($oldData['payment_date']);
			}
			switch ($oldData['payment_date_type']) {
				case '2':
					$order->paymentBlame = Order::PAYMENT_BLAME_VUB;
					break;
				case '3':
					$order->paymentBlame = Order::PAYMENT_BLAME_CSOB;
					break;
				case '1':
					$order->paymentBlame = Order::PAYMENT_BLAME_MANUAL;
					break;
			}

			$parts = $conn->executeQuery(
							"SELECT * "
							. "FROM {$tableOrderParts} oa "
							. "WHERE order_id = ? "
							, [$orderID])->fetchAll();
			foreach ($parts as $part) {
				$productId = $part['product_id'];
				$requestedQuantity = $part['quantity'];
				if ($productId && $stock = $stockRepo->find($productId)) {
					$stock->increaseQuantity($requestedQuantity);
					if (!$stock->vat) {
						$message = "Stock with ID '{$productId}' hasn't correct data in order with ID '{$orderID}' (CODE: {$orderCode})";
						throw new WrongSituationException($message);
					}
					$stockRepo->save($stock);
					$stock->setDefaltPrice($part['price'], (bool) $part['vat_included']);
				} else {
					$stock = new Stock();
					$stock->setQuantity($requestedQuantity);
					$stock->active = FALSE;
					$stock->createdBy = $admin;
					$stock->updatedBy = $admin;
					$stock->product->active = FALSE;
					$stock->product->createdBy = $admin;
					$stock->product->updatedBy = $admin;
					$productTranslation = $stock->product->translateAdd($this->translator->getDefaultLocale());
					$productTranslation->name = $part['name'];
					$stock->product->mergeNewTranslations();
					$productVat = $part['vat'] > 0 ? $part['vat'] : 0;
					$vat = $vatRepo->findOneByValue($productVat);
					if (!$vat) {
						$vat = new Vat(NULL, $productVat);
						$this->em->persist($vat);
					}
					$stock->vat = $vat;
					$stock->setDefaltPrice($part['price'], (bool) $part['vat_included']);
					$stockRepo->save($stock);
				}
				$price = $stock->getPrice();

				try {
					$order->setItem($stock, $price, $part['quantity'], $order->locale);
				} catch (InsufficientQuantityException $ex) {
					$message = "Stock with ID '{$stock->id}'"
					. " has only {$stock->inStore}"
					. " in store and required {$part['quantity']}"
					. " in order with ID '{$orderID}' (CODE: {$orderCode})";
					throw new WrongSituationException($message);
				}
			}

			$this->em->persist($order);
			$this->em->flush();
		}

		return $this;
	}

	private function updateOrders()
	{
		$conn = $this->em->getConnection();
		$dbName = $this->getDbName();
		$tableOrders = $dbName . '.' . self::TABLE_ORDER;

		$stateRepo = $this->em->getRepository(OrderState::getClassName());
		$orderRepo = $this->em->getRepository(Order::getClassName());
		$orders = $orderRepo->findBy([], ['updatedAt' => 'ASC'], self::MAX_ORDERS);

		foreach ($orders as $order) {
			$orderStatusId = $conn->executeQuery(
							"SELECT order_status_id "
							. "FROM {$tableOrders} o "
							. "WHERE code = ? "
							. "LIMIT 1"
							, [$order->id])->fetchColumn();

			switch ($orderStatusId) {
				case '1':
					$state = $stateRepo->find(OrderState::ORDERED_IN_SYSTEM);
					break;
				case '2':
					$state = $stateRepo->find(OrderState::IN_PROCEEDINGS);
					break;
				case '3':
					$state = $stateRepo->find(OrderState::SENT_SHIPPERS);
					break;
				case '4':
					$state = $stateRepo->find(OrderState::OK_RECIEVED);
					break;
				case '5':
					$state = $stateRepo->find(OrderState::READY_TO_TAKE);
					break;
				case '6':
					$state = $stateRepo->find(OrderState::CANCELED);
					break;
				case '7':
					$state = $stateRepo->find(OrderState::OK_TAKEN);
					break;
				default:
					$message = "Order with ID {$order->id} has unknows state ID '{$orderStatusId}'";
					throw new WrongSituationException($message);
			}
			if ($state && $order->state->id != $state->id) {
				$oldState = $order->state;
				$order->state = $state;
				$order->updatedAt = new DateTime();
				$this->em->persist($order);
				$this->em->flush();

				$this->orderFacade->relockAndRequantityProducts($order, $oldState);
			}
		}

		return $this;
	}

	private function getGroupByOldId($oldId)
	{
		if (array_key_exists($oldId, $this->groupsMapping)) {
			$id = $this->groupsMapping[$oldId];
			$groupRepo = $this->em->getRepository(Group::getClassName());
			return $groupRepo->find($id);
		}
		return NULL;
	}

	private function importUsersBasic()
	{
		$userRepo = $this->em->getRepository(User::getClassName());
		$roleRepo = $this->em->getRepository(Role::getClassName());
		$customer = $roleRepo->findOneByName(Role::USER);
		$conn = $this->em->getConnection();
		$dbName = $this->getDbName();
		$tableUsers = $dbName . '.' . self::TABLE_USER;

		$stmt = $conn->executeQuery(
				"SELECT * "
				. "FROM {$tableUsers} u");

		if (!$customer) {
			return $this;
		}

		foreach ($stmt->fetchAll() as $data) {
			$userId = $userRepo->findIdByMail($data['mail']);
			$change = FALSE;
			if (!$userId) {
				$this->checkLimit();
				$user = new User($data['mail']);
				$user->addRole($customer);
				$user->setLocale($this->translator->getDefaultLocale())
						->setCurrency($this->exchange->getDefault()->getCode());
			} else {
				continue;
			}

			if (!$user) {
				continue;
			}

			$wantBeDealer = (bool) $data['dealer_want'];
			if ($wantBeDealer !== $user->wantBeDealer) {
				$user->setWantBeDealer($wantBeDealer);
				$change = TRUE;
			}

			$group = $this->getGroupByOldId($data['dealer_level']);
			if ($group && (!$user->group || $group->id != $user->group->id)) {
				$user->addGroup($group);
				$change = TRUE;
			}

			if ($user->isNew() || $change) {
				$userRepo->save($user);
			}
		}
		return $this;
	}

	private function importUsersSigns()
	{
		$userRepo = $this->em->getRepository(User::getClassName());
		$conn = $this->em->getConnection();
		$dbName = $this->getDbName();
		$tableUsers = $dbName . '.' . self::TABLE_USER;
		$tablePasswords = $dbName . '.' . self::TABLE_PASSWORDS;
		$tableAuth = $dbName . '.' . self::TABLE_AUTH;

		$stmtAuth = $conn->executeQuery(
				"SELECT a.key, u.mail, a.source "
				. "FROM {$tableAuth} a "
				. "JOIN {$tableUsers} u ON a.user_id = u.id "
				. "WHERE (a.source = ? OR a.source = ?) AND a.verified = ?", ['facebook', 'twitter', '1']);

		foreach ($stmtAuth->fetchAll() as $authData) {
			$userId = $userRepo->findIdByMail($authData['mail']);
			if ($userId) {
				$user = $userRepo->find($userId);
				if (!$user) {
					continue;
				}
				switch ($authData['source']) {
					case 'facebook':
						$this->addFacebookConn($user, $authData['key']);
						break;
					case 'twitter':
						$this->addTwitterConn($user, $authData['key']);
						break;
				}
				$this->em->persist($user);
			}
		}
		$this->em->flush();

		$stmtPasswds = $conn->executeQuery(
				"SELECT p.password, u.mail "
				. "FROM {$tablePasswords} p "
				. "JOIN {$tableUsers} u ON p.user_id = u.id ");
		$passwds = $stmtPasswds->fetchAll();

		foreach ($passwds as $passwData) {
			/* @var $user User */
			$userId = $userRepo->findIdByMail($passwData['mail']);
			if ($userId) {
				$user = $userRepo->find($userId);
				if (!$user) {
					continue;
				}
				if (!$user->verifyPassword($passwData['password'])) {
					$this->checkLimit();
				}
				$user->setPassword($passwData['password']);
				$this->em->persist($user);
			}
		}
		$this->em->flush();

		return $this;
	}

	private function importUsersAddresses()
	{
		$conn = $this->em->getConnection();
		$dbName = $this->getDbName();
		$tableUsers = $dbName . '.' . self::TABLE_USER;
		$tableAddress = $dbName . '.' . self::TABLE_ADDRESS;

		$start = 0;
		$limit = self::MAX_INSERTS / 2;
		$stmtBilling = $conn->executeQuery(
				"SELECT a.*, u.mail "
				. "FROM {$tableUsers} u "
				. "JOIN {$tableAddress} a ON u.billing_address_id = a.id "
				. "ORDER BY create_date DESC "
				. "LIMIT {$limit} OFFSET {$start}");
		foreach ($stmtBilling->fetchAll() as $billingAddress) {
			$this->addAddress($billingAddress['mail'], $billingAddress, TRUE);
		}

		$stmtDelivery = $conn->executeQuery(
				"SELECT a.*, u.mail "
				. "FROM {$tableUsers} u "
				. "JOIN {$tableAddress} a ON u.delivery_address_id = a.id "
				. "ORDER BY create_date DESC "
				. "LIMIT {$limit} OFFSET {$start}");
		foreach ($stmtDelivery->fetchAll() as $deliveryAddress) {
			$this->addAddress($deliveryAddress['mail'], $deliveryAddress, FALSE);
		}

		return $this;
	}

	private function importSubscribers()
	{
		$conn = $this->em->getConnection();
		$subscriberRepo = $this->em->getRepository(Subscriber::getClassName());
		$dbName = $this->getDbName();
		$tableNewsletter = $dbName . '.' . self::TABLE_NEWSLETTER;
		$tableNewsletterDealer = $dbName . '.' . self::TABLE_NEWSLETTER_DEALER;

		$stmt1 = $conn->executeQuery(
				"SELECT n.mail "
				. "FROM {$tableNewsletter} n");
		foreach ($stmt1->fetchAll() as $data) {
			$subscriber = $subscriberRepo->findOneByMail($data['mail']);
			if (!$subscriber) {
				$this->newsletterFacade->subscribe($data['mail'], Subscriber::TYPE_USER);
				$this->checkLimit();
			}
		}

		$stmt2 = $conn->executeQuery(
				"SELECT n.mail "
				. "FROM {$tableNewsletterDealer} n");
		foreach ($stmt2->fetchAll() as $data) {
			$subscriber = $subscriberRepo->findOneByMail($data['mail']);
			if (!$subscriber) {
				$this->newsletterFacade->subscribe($data['mail'], Subscriber::TYPE_DEALER);
				$this->checkLimit();
			}
		}

		return $this;
	}

	private function updateUsers()
	{
		return $this;
	}

	private function addFacebookConn(User $user, $key)
	{
		$fbRepo = $this->em->getRepository(Facebook::getClassName());
		if (!$fbRepo->find($key)) {
			$this->checkLimit();
			$user->facebook = new Facebook($key);
		}
		return $this;
	}

	private function addTwitterConn(User $user, $key)
	{
		$twRepo = $this->em->getRepository(Twitter::getClassName());
		if (!$twRepo->find($key)) {
			$this->checkLimit();
			$user->twitter = new Twitter($key);
		}
		return $this;
	}

	private function addAddress($mail, $data, $isBilling = TRUE)
	{
		$userRepo = $this->em->getRepository(User::getClassName());
		/* @var $user User */
		$userId = $userRepo->findIdByMail($mail);
		if ($userId) {
			$user = $userRepo->find($userId);
			if (!$user) {
				return $this;
			}
			$address = new Address();
			$isCompany = !empty($data['company']);
			$address->name = $isCompany ? $data['company'] : ($data['firstname'] . ' ' . $data['surname']);
			$address->street = $data['street'];
			$address->city = $data['city'];
			$address->country = $data['country'] === 'CZE' ? 'CZ' : 'SK';
			$address->zipcode = $data['zipcode'];
			$address->phone = $data['phone'];
			$address->ico = $data['ico'];
			$address->dic = $data['dic'];
			$address->icoVat = $data['icdph'];
			$address->note = $data['info'];
			if ($isBilling) {
				if (!$this->isAddressSame($user->billingAddress, $address)) {
					$this->checkLimit();
					$this->userFacade->setAddress($user, $address, NULL, FALSE);
				}
			} else {
				if (!$this->isAddressSame($user->shippingAddress, $address)) {
					$this->checkLimit();
					$this->userFacade->setAddress($user, NULL, $address, FALSE);
				}
			}
		}
		return $this;
	}

	private function isAddressSame($address1, $address2)
	{
		if ($address1 instanceof Address && $address2 instanceof Address) {
			if ($address1->name == $address2->name &&
					$address1->street == $address2->street &&
					$address1->city == $address2->city &&
					$address1->country == $address2->country &&
					$address1->zipcode == $address2->zipcode &&
					$address1->phone == $address2->phone &&
					$address1->ico == $address2->ico &&
					$address1->dic == $address2->dic &&
					$address1->icoVat == $address2->icoVat &&
					$address1->note == $address2->note) {
				return TRUE;
			}
		}
		return FALSE;
	}

	private function checkLimit()
	{
		if ($this->limit >= $this->maxInserts) {
			throw new LimitExceededException('Maximum insertion is exceeded');
		}
		$this->limit++;
	}

}

class ImportFromMT1Exception extends Exception
{
	
}

class LimitExceededException extends Exception
{
	
}

class WrongSituationException extends Exception
{
	
}
