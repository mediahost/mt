<?php

namespace App\Extensions;

use App\Model\Entity\Address;
use App\Model\Entity\Facebook;
use App\Model\Entity\Group;
use App\Model\Entity\Newsletter\Subscriber;
use App\Model\Entity\Role;
use App\Model\Entity\Twitter;
use App\Model\Entity\User;
use App\Model\Facade\NewsletterFacade;
use App\Model\Facade\UserFacade;
use Doctrine\ORM\EntityManager;
use Exception;
use h4kuna\Exchange\Exchange;
use Kdyby\Translation\Translator;
use Nette\Object;

class ImportFromMT1 extends Object
{

	const TABLE_USER = 'user';
	const TABLE_AUTH = 'auth';
	const TABLE_PASSWORDS = 'user_passwords';
	const TABLE_ADDRESS = 'user_address';
	const TABLE_NEWSLETTER = 'newsletter_register';
	const TABLE_NEWSLETTER_DEALER = 'newsletter_register_dealer';

	// <editor-fold desc="constants & variables">

	/** @var string */
	private $dbName;

	/** @var array */
	private $groupsMapping = [];

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

	public function downloadUsers()
	{
		ini_set('max_execution_time', 120);

		$this->importUsersBasic();
		$this->importUsersSigns();
		$this->importUsersAddresses();
		$this->importSubscribers();

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
		$results = $stmt->fetchAll();

		foreach ($results as $data) {

			$user = $userRepo->findOneByMail($data['mail']);
			if (!$user && $customer) {
				$user = new User($data['mail']);
				$user->addRole($customer);
			}

			$user->setLocale($this->translator->getDefaultLocale())
					->setCurrency($this->exchange->getDefault()->getCode())
					->setWantBeDealer((bool) $data['dealer_want']);

			$group = $this->getGroupByOldId($data['dealer_level']);
			if ($group) {
				$user->addGroup($group);
			}

			$userRepo->save($user);
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
			$user = $userRepo->findOneByMail($authData['mail']);
			if ($user) {
				switch ($authData['source']) {
					case 'facebook':
						$this->addFacebookConn($user, $authData['key']);
						break;
					case 'twitter':
						$this->addTwitterConn($user, $authData['key']);
						break;
				}
				$userRepo->save($user);
			}
		}

		$stmtPasswds = $conn->executeQuery(
				"SELECT p.password, u.mail "
				. "FROM {$tablePasswords} p "
				. "JOIN {$tableUsers} u ON p.user_id = u.id ");
		$passwds = $stmtPasswds->fetchAll();

		foreach ($passwds as $passwData) {
			/* @var $user User */
			$user = $userRepo->findOneByMail($passwData['mail']);
			if ($user) {
				$user->setPassword($passwData['password']);
				$userRepo->save($user);
			}
		}

		return $this;
	}

	private function addFacebookConn(User $user, $key)
	{
		$fbRepo = $this->em->getRepository(Facebook::getClassName());
		if (!$fbRepo->find($key)) {
			$user->facebook = new Facebook($key);
		}
		return $this;
	}

	private function addTwitterConn(User $user, $key)
	{
		$twRepo = $this->em->getRepository(Twitter::getClassName());
		if (!$twRepo->find($key)) {
			$user->facebook = new Twitter($key);
		}
		return $this;
	}

	private function importUsersAddresses()
	{
		$conn = $this->em->getConnection();
		$dbName = $this->getDbName();
		$tableUsers = $dbName . '.' . self::TABLE_USER;
		$tableAddress = $dbName . '.' . self::TABLE_ADDRESS;

		$stmtBilling = $conn->executeQuery(
				"SELECT a.*, u.mail "
				. "FROM {$tableUsers} u "
				. "JOIN {$tableAddress} a ON u.billing_address_id = a.id");
		foreach ($stmtBilling->fetchAll() as $billingAddress) {
			$this->addAddress($billingAddress['mail'], $billingAddress, TRUE);
		}

		$stmtDelivery = $conn->executeQuery(
				"SELECT a.*, u.mail "
				. "FROM {$tableUsers} u "
				. "JOIN {$tableAddress} a ON u.delivery_address_id = a.id");
		foreach ($stmtDelivery->fetchAll() as $deliveryAddress) {
			$this->addAddress($deliveryAddress['mail'], $deliveryAddress, FALSE);
		}

		return $this;
	}

	private function addAddress($mail, $data, $isBilling = TRUE)
	{
		$userRepo = $this->em->getRepository(User::getClassName());
		$user = $userRepo->findOneByMail($mail);
		if ($user) {
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
				$billing = $address;
				$shipping = NULL;
			} else {
				$billing = NULL;
				$shipping = $address;
			}
			$this->userFacade->setAddress($user, $billing, $shipping, FALSE);
		}
		return $this;
	}

	private function importSubscribers()
	{
		$conn = $this->em->getConnection();
		$dbName = $this->getDbName();
		$tableNewsletter = $dbName . '.' . self::TABLE_NEWSLETTER;
		$tableNewsletterDealer = $dbName . '.' . self::TABLE_NEWSLETTER_DEALER;

		$stmt1 = $conn->executeQuery(
				"SELECT n.mail "
				. "FROM {$tableNewsletter} n");
		foreach ($stmt1->fetchAll() as $data) {
			$this->newsletterFacade->subscribe($data['mail'], Subscriber::TYPE_USER);
		}

		$stmt2 = $conn->executeQuery(
				"SELECT n.mail "
				. "FROM {$tableNewsletterDealer} n");
		foreach ($stmt2->fetchAll() as $data) {
			$this->newsletterFacade->subscribe($data['mail'], Subscriber::TYPE_DEALER);
		}

		return $this;
	}

}

class ImportFromMT1Exception extends Exception
{
	
}
