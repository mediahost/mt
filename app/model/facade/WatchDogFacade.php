<?php

namespace App\Model\Facade;

use App\Mail\Messages\WatchDog\IAvailableFactory;
use App\Mail\Messages\WatchDog\ILoweredPriceFactory;
use App\Model\Entity\Price;
use App\Model\Entity\Stock;
use App\Model\Entity\WatchDog;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Nette\Object;
use Nette\Utils\DateTime;

class WatchDogFacade extends Object
{

	/** @var EntityManager @inject */
	public $em;

	/** @var EntityRepository */
	private $watchDogRepo;

	/** @var IAvailableFactory @inject */
	public $createAvailableMessage;

	/** @var ILoweredPriceFactory @inject */
	public $createLoweredPriceMessage;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->watchDogRepo = $this->em->getRepository(WatchDog::getClassName());
	}

	public function findOne(Stock $stock, $mail)
	{
		$watchDog = $this->watchDogRepo->findOneBy([
			'mail' => $mail,
			'stock' => $stock,
		]);
		return $watchDog;
	}

	public function add(Stock $stock, $mail, $checkAvailable = NULL, $price = NULL, $priceLevel = NULL)
	{
		$watchDog = $this->findOne($stock, $mail);
		if (!$watchDog) {
			$watchDog = new WatchDog($mail, $stock);
		}

		$watchDog->available = !!$checkAvailable;
		$watchDog->price = NULL;
		if ($price) {
			$price = new Price($stock->vat, $price, FALSE);
			$watchDog->price = $price->withoutVat;
			$watchDog->priceLevel = $priceLevel;
		}

		$this->watchDogRepo->save($watchDog);

		return $this;
	}

	public function onStored(Stock $stock)
	{
		$watchDogs = $this->watchDogRepo->findBy([
			'stock' => $stock,
			'available' => TRUE,
			'sendedAt' => NULL,
		]);
		foreach ($watchDogs as $watchDog) {
			$message = $this->createAvailableMessage->create();
			$message->addTo($watchDog->mail)
					->addParameter('stock', $stock);
			$message->send();

			$watchDog->sendedAt = new DateTime();
			$this->em->persist($watchDog);
		}
		$this->em->flush();
		return $this;
	}

	public function onChangedPrice(Stock $stock)
	{
		$watchDogs = $this->watchDogRepo->findBy([
			'stock' => $stock,
			'price NOT' => NULL,
			'sendedAt' => NULL,
		]);
		foreach ($watchDogs as $watchDog) {
			if ($stock->getPrice($watchDog->priceLevel)->withoutVat < $watchDog->price) {
				$message = $this->createLoweredPriceMessage->create();
				$message->addTo($watchDog->mail)
						->addParameter('stock', $stock);
				$message->send();

				$watchDog->sendedAt = new DateTime();
				$this->em->persist($watchDog);
			}
		}
		$this->em->flush();
		return $this;
	}

}
