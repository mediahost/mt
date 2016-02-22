<?php

namespace App\Model\Repository;

use App\Model\Entity\Stock;
use App\Model\Entity\User;
use App\Model\Entity\Visit;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\AbstractQuery;
use Nette\Utils\DateTime;

class VisitRepository extends BaseRepository
{

	public function findPreviousVisit(Visit $visit, $ignoreTime = NULL)
	{
		$criteria = [
			'ip' => $visit->ip,
			'stock' => $visit->stock,
			'user' => $visit->user,
		];
		if ($ignoreTime) {
			$criteria['visitedAt >='] = DateTime::from('-' . $ignoreTime);
		}
		$orderBy = [
			'visitedAt' => Criteria::DESC,
		];
		return parent::findOneBy($criteria, $orderBy);
	}

	public function findByUserGroupByStockOrderByVisitedDesc(User $user, $limit = null, $offset = null)
	{
		$orderBy = ['visitedAt' => Criteria::DESC];
		$qb = $this->createQueryBuilder('e')
				->whereCriteria([
					'user' => $user,
				])
				->autoJoinOrderBy($orderBy)
				->groupBy('e.stock');

		$visits = $qb->getQuery()
				->setMaxResults($limit)
				->setFirstResult($offset)
				->getResult();

		if (!count($visits)) {
			return $visits;
		}

		// replaced by item with real latest date
		foreach ($visits as $key => $visit) {
			$latest = parent::findOneBy([
						'ip' => $visit->ip,
						'stock' => $visit->stock,
						'user' => $visit->user,
							], $orderBy);
			if ($latest->id !== $visit->id) {
				$visits[$key] = $latest;
			}
		}
		// reorder
		$dir = Criteria::DESC;
		$sortByVisitedAt = function (Visit $a, Visit $b) use ($dir) {
			$aDate = $a->visitedAt;
			$bDate = $b->visitedAt;
			if ($aDate === $bDate) {
				return 0;
			}
			switch ($dir) {
				case Criteria::DESC:
					$op = $aDate > $bDate;
					break;
				case Criteria::ASC:
				default:
					$op = $aDate < $bDate;
					break;
			}
			return $op ? -1 : 1;
		};
		usort($visits, $sortByVisitedAt);

		return $visits;
	}

	public function countStockVisits(Stock $stock, $inLastTime = NULL, $denyIp = NULL)
	{
		$criteria = [
			'stock' => $stock,
		];
		if ($inLastTime) {
			$criteria['visitedAt >='] = DateTime::from('-' . $inLastTime);
		}
		if ($denyIp) {
			$criteria['ip !='] = $denyIp;
		}
		$qb = $this->createQueryBuilder('e')
				->whereCriteria($criteria)
				->groupBy('e.ip');

		$result = $qb->getQuery()
				->getResult(AbstractQuery::HYDRATE_ARRAY);
		return count($result);
	}

}
