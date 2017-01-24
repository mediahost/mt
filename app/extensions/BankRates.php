<?php

namespace h4kuna\Exchange\Driver;

use App\Model\Entity\BankRate;
use DateTime;
use GuzzleHttp\Exception\RequestException;
use h4kuna\Exchange\Currency\Property;
use Kdyby\Doctrine\DBALException;
use Kdyby\Doctrine\EntityManager;
use Tracy\Debugger;

class BankRates extends Ecb\Day
{

	/** @var  EntityManager */
	private $em;

	private $connectionFail = FALSE;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
	}

	protected function loadFromSource(DateTime $date = NULL)
	{
		try {
			$currencies = parent::loadFromSource($date);
			return $currencies;
		} catch (RequestException $e) {
			$this->connectionFail = TRUE;
			return $this->loadOldRates();
		}
	}

	protected function createProperty($row)
	{
		if ($this->connectionFail) {
			return $row;
		} else {
			$property = parent::createProperty($row);
			return $this->saveBankRate($property);
		}
	}

	private function saveBankRate(Property $property)
	{
		try {
			$rateRepo = $this->em->getRepository(BankRate::getClassName());
			$rate = $rateRepo->find($property->getCode());
			if ($rate) {
				$rate->setValue($property->getForeing());
			} else {
				$rate = new BankRate($property->getCode(), $property->getForeing());
			}
			$rateRepo->save($rate);

			if ($rate->fixed) {
				$row = [
					'currency' => $rate->code,
					'rate' => $rate->value,
				];
				$property = parent::createProperty($row);
			}
		} catch (DBALException $e) {
		}
		return $property;
	}

	private function loadOldRates()
	{
		$rateRepo = $this->em->getRepository(BankRate::getClassName());
		$currencies = [];
		foreach ($rateRepo->findAll() as $rate) {
			$currencies[] = new Property(1, $rate->code, $rate->value);
		}
		return $currencies;
	}

}
