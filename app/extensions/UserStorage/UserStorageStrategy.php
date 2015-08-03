<?php

namespace App\Extensions\UserStorage;

use App\Model\Entity\Stock;
use App\Model\Entity\VisitedProduct;
use App\Model\Repository\StockRepository;
use App\Model\Repository\VisitedProductRepository;
use DateTime;
use h4kuna\Exchange\Currency\IProperty;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use Nette\Security\IIdentity;
use Nette\Security\IUserStorage;

/**
 * @author Martin Šifra <me@martinsifra.cz>
 */
class UserStorageStrategy extends Object implements IUserStorage
{

	/**
	 * @var IUserStorage
	 */
	private $userStorage;

	/**
	 * @var GuestStorage
	 */
	private $guestStorage;

	/**
	 * @var VisitedProductRepository
	 */
	private $visitedRepo;

	/**
	 * @var StockRepository
	 */
	private $stockRepo;

	/**
	 * @var EntityManager
	 */
	private $em;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->visitedRepo = $em->getRepository(VisitedProduct::class);
		$this->stockRepo = $em->getRepository(Stock::class);
	}

	public function getIdentity()
	{
		if ($this->userStorage->isAuthenticated()) {
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

	public function addVisited(Stock $stock)
	{
		if ($this->userStorage->isAuthenticated()) {
			\Tracy\Debugger::barDump($stock->id);
			$visited = $this->visitedRepo->findOneByUserAndStock($this->userStorage->identity, $stock);

			if ($visited === NULL) {
				$visited = new VisitedProduct();
				$visited->setStock($stock)
						->setUser($this->userStorage->identity)
						->setVisited(new DateTime());
			} else {
				$latest = $this->visitedRepo->findLatest($this->userStorage->identity);

				if ($latest !== $visited) {
					$visited->setVisited(new DateTime());
				}
			}

			$this->em->persist($visited)
					->flush();
		} else {
			if (array_key_exists($stock->id, $this->guestStorage->visitedProducts)) {
				$this->guestStorage->deleteVisitedProduct($stock);
			}

			$this->guestStorage->addVisitedProduct($stock);

//			\Nette\Diagnostics\Debugger::barDump($this->guestStorage->visitedProducts);
		}
	}

	public function getVisited($limit = 5)
	{
		$ids = [];

		if ($this->userStorage->isAuthenticated()) {
			$visited = $this->visitedRepo->findBy([
				'user' => $this->userStorage->identity,
					], ['visited' => 'ASC'], $limit, 0);

			foreach ($visited as $visited) {
				$ids[] = $visited->stock->id;
			}
		} else {
			$ids = array_keys($this->guestStorage->visitedProducts);
			array_slice($ids, 0, $limit);
		}

		return $this->stockRepo->findAssoc(['id' => $ids,], 'id');
	}

	/**
	 * @param string $locale
	 * @return UserStorageStrategy
	 */
	public function setLocale($locale)
	{
		if ($this->userStorage->isAuthenticated()) {
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
		if ($this->userStorage->isAuthenticated()) {
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
		$this->userStorage->identity->import($this->guestStorage->identity);
		return $this;
	}

}
