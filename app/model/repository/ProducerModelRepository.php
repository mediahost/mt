<?php

namespace App\Model\Repository;

use App\Model\Entity\ProducerLine;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\ResultSetMapping;

class ProducerModelRepository extends BaseRepository
{

	public function findPairs($criteria, $value = NULL, $orderBy = array(), $key = NULL)
	{
		if (!is_array($criteria)) {
			$key = $orderBy;
			$orderBy = $value;
			$value = $criteria;
			$criteria = array();
		}

		if (!is_array($orderBy)) {
			$key = $orderBy;
			$orderBy = array();
		}

		if ($key === 'lineId') {
			$rsm = new ResultSetMapping();
			$rsm->addScalarResult('line_id', $key);
			$rsm->addScalarResult($value, $value);
			$sql = 'SELECT line_id, ' . $value . ' FROM ' . $this->getClassMetadata()->getTableName();
			$query = $this->createNativeQuery($sql, $rsm);
			$result = [];
			foreach ($query->getResult(AbstractQuery::HYDRATE_ARRAY) as $item) {
				$result[$item[$key]] = $item[$value];
			}
			return $result;
		} else {
			return parent::findPairs($criteria, $value, $orderBy, $key);
		}
	}

	public function findOneByUrl($url)
	{
		if (is_string($url)) {
			$url = preg_split('@/@', $url, -1, PREG_SPLIT_NO_EMPTY);
		} else if (!is_array($url)) {
			return NULL;
		}

		$slug = array_pop($url);
		$line = NULL;
		if (count($url)) {
			$lineRepo = $this->_em->getRepository(ProducerLine::getClassName());
			$line = $lineRepo->findOneByUrl($url);
		}

		$qb = $this->createQueryBuilder('m')
			->where('m.slug = :slug')
			->setParameter('slug', $slug);
		if ($line) {
			$qb->andWhere('m.line = :line')
				->setParameter('line', $line);
		}

		return $qb->setMaxResults(1)->getQuery()->getOneOrNullResult();
	}

}
