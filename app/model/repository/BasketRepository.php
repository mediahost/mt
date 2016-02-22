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

}
