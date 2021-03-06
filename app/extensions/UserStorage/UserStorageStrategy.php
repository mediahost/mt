<?php

namespace App\Extensions\UserStorage;

use App\Model\Entity\Basket;
use App\Model\Entity\Stock;
use App\Model\Entity\User;
use App\Model\Entity\Visit;
use App\Model\Facade\VisitFacade;
use App\Model\Repository\BasketRepository;
use App\Model\Repository\StockRepository;
use App\Model\Repository\VisitRepository;
use h4kuna\Exchange\Currency\IProperty;
use Kdyby\Doctrine\EntityManager;
use Nette\Http\Request;
use Nette\Object;
use Nette\Security\IIdentity;
use Nette\Security\IUserStorage;

class UserStorageStrategy extends Object implements IUserStorage
{

	/** @var IUserStorage */
	private $userStorage;

	/** @var GuestStorage */
	private $guestStorage;

	/** @var VisitRepository */
	private $visitRepo;

	/** @var StockRepository */
	private $stockRepo;

	/** @var BasketRepository */
	private $basketRepo;

	/** @var EntityManager */
	private $em;

	/** @var VisitFacade */
	private $visitFacade;

	public function __construct(EntityManager $em, VisitFacade $visitFacade)
	{
		$this->em = $em;
		$this->visitFacade = $visitFacade;
		$this->visitRepo = $em->getRepository(Visit::getClassName());
		$this->stockRepo = $em->getRepository(Stock::getClassName());
		$this->basketRepo = $em->getRepository(Basket::getClassName());
	}

	public function getIdentity()
	{
		if ($this->isAuthenticated()) {
			return $this->userStorage->getIdentity();
		} else {
			return $this->guestStorage->getIdentity();
		}
	}

	public function setIdentity(IIdentity $identity = NULL)
	{
		$this->userStorage->setIdentity($identity);
	}

	public function getLogoutReason()
	{
		return $this->userStorage->getLogoutReason();
	}

	public function isAuthenticated()
	{
		return $this->userStorage->isAuthenticated();
	}

	public function setAuthenticated($state)
	{
		$this->userStorage->setAuthenticated($state);
	}

	public function setExpiration($time, $flags = 0)
	{
		$this->userStorage->setExpiration($time, $flags);
	}

	public function setUser(IUserStorage $storage)
	{
		$this->userStorage = $storage;
		return $this;
	}

	public function setGuest(IUserStorage $storage)
	{
		$this->guestStorage = $storage;
		return $this;
	}

	public function getBasket()
	{
		if ($this->isAuthenticated()) {
			$basket = $this->userStorage->identity->basket;
			if (!$basket) {
				$basket = $this->createBasket($this->userStorage->identity);
			}
			return $basket;
		} else {
			$basket = NULL;
			$basketId = $this->guestStorage->getBasketId();
			if ($basketId) {
				$basket = $this->basketRepo->find($basketId);
			}
			if (!$basket) {
				$basket = $this->createBasket();
				$this->guestStorage->setBasketId($basket->id);
			}
			return $basket;
		}
	}

	public function removeBasket()
	{
		if ($this->isAuthenticated()) {
			$basket = $this->userStorage->identity->basket;
		} else {
			$basket = NULL;
			$basketId = $this->guestStorage->getBasketId();
			$this->guestStorage->setBasketId(NULL);
			if ($basketId) {
				$basket = $this->basketRepo->find($basketId);
			}
		}

		if ($basket) {
			$this->basketRepo->delete($basket);
		}

		return $this;
	}

	private function createBasket(User $user = NULL)
	{
		$basket = new Basket($user);
		$this->em->persist($basket);
		$this->em->flush();
		return $basket;
	}

	private function saveUser(User $user)
	{
		$basket = $user->basket;
		if ($basket) {
			$this->em->persist($basket);
		}
		$this->em->persist($user);
		$this->em->flush();
		return $user;
	}

	public function addVisit(Stock $stock)
	{
		if ($this->isAuthenticated()) {
			$user = $this->userStorage->identity;
		} else {
			$user = NULL;
			if (array_key_exists($stock->id, $this->guestStorage->visits)) {
				$this->guestStorage->deleteVisit($stock);
			}
			$this->guestStorage->addVisit($stock);
		}

		$this->visitFacade->add($stock, $user);
	}

	public function getVisited($limit = 5)
	{
		$stocks = [];
		if ($this->isAuthenticated()) {
			$visited = $this->visitFacade->getUserVisits($this->userStorage->identity, $limit);
			foreach ($visited as $visited) {
				$stocks[$visited->stock->id] = $visited->stock;
			}
		} else {
			$ids = array_keys($this->guestStorage->visits);
			array_slice($ids, 0, $limit);
			$stocks = $this->stockRepo->findAssoc(['id' => $ids], 'id');
		}

		return $stocks;
	}

	/**
	 * @param string $locale
	 * @return UserStorageStrategy
	 */
	public function setLocale($locale)
	{
		if ($this->isAuthenticated()) {
			$this->userStorage->identity->locale = $locale;
			$this->em->persist($this->userStorage->identity)
					->flush();
		} else {
			$this->guestStorage->identity->locale = $locale;
		}

		return $this;
	}

	/**
	 * @param IProperty $currency
	 * @return UserStorageStrategy
	 */
	public function setCurrency($currency)
	{
		if ($this->isAuthenticated()) {
			$this->userStorage->identity->currency = $currency->getCode();
			$this->em->persist($this->userStorage->identity)
					->flush();
		} else {
			$this->guestStorage->identity->currency = $currency->getCode();
		}

		return $this;
	}

	public function fromGuestToUser()
	{
		$user = $this->userStorage->identity;
		$guest = $this->guestStorage->identity;

		$basketId = $this->guestStorage->getBasketId();
		$this->guestStorage->setBasketId(NULL);
		if ($basketId) {
			$basket = $this->basketRepo->find($basketId);
			$user->import($guest, $basket);
		} else {
			$user->import($guest);
		}

		$this->saveUser($user);

		if (isset($basket)) {
			$this->basketRepo->delete($basket);
		}

		return $this;
	}

}
