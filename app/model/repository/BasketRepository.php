<?php

namespace App\Model\Repository;

use Nette\Utils\DateTime;

class BasketRepository extends BaseRepository
{

	public function findUnfinished($withItems = TRUE)
	{
		// init time of start module
		$init = '2016-01-12 17:00:00';
		// hours from last change of basket
		$minusTime = '24 hours';

		$criteria = [
			'changeItemsAt >=' => new DateTime($init),
			'changeItemsAt <=' => new DateTime('-' . $minusTime),
			'mail NOT' => NULL,
			'sendedMailAt' => NULL,
		];

		$qb = $this->createQueryBuilder('b')
			->whereCriteria($criteria);

		if ($withItems) {
			$qb->join('b.items', 'i');
		}

		return $qb->getQuery()
			->getResult();
	}

	public function findEmpty($olderThan = NULL, $mustBeEmpty = TRUE)
	{
		if ($mustBeEmpty) {
			$emptyCriteria = [
				'user' => NULL,
				'shipping' => NULL,
				'payment' => NULL,
				'shippingAddress' => NULL,
				'billingAddress' => NULL,
				'mail' => NULL,
				'sendedMailAt' => NULL,
				'accessHash' => NULL,
			];
		} else {
			$emptyCriteria = [];
		}

		if ($olderThan) {
			$emptyCriteria['updatedAt <='] = new DateTime('-' . $olderThan);
		}

		$qb = $this->createQueryBuilder('b')
			->whereCriteria($emptyCriteria);

		return $qb->getQuery()
			->setMaxResults(10000)
			->getResult();
	}

	public function findOlders($olderThan)
	{
		return $this->findEmpty($olderThan, FALSE);
	}

}
