<?php

namespace App\Model\Repository;

use App\Model\Entity\Producer;
use App\Model\Entity\ProducerLine;
use App\Model\Entity\ProducerModel;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\ResultSetMapping;
use Tracy\Debugger;

class ProducerLineRepository extends BaseRepository
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

		if ($key === 'producerId') {
			$rsm = new ResultSetMapping();
			$rsm->addScalarResult('producer_id', $key);
			$rsm->addScalarResult($value, $value);
			$sql = 'SELECT producer_id, ' . $value . ' FROM ' . $this->getClassMetadata()->getTableName();
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
		$producer = NULL;
		if (count($url)) {
			$producerRepo = $this->_em->getRepository(Producer::getClassName());
			$producer = $producerRepo->findOneByUrl($url);
		}

		$qb = $this->createQueryBuilder('l')
			->where('l.slug = :slug')
			->setParameter('slug', $slug);
		if ($producer) {
			$qb->andWhere('l.producer = :producer')
				->setParameter('producer', $producer);
		}

		return $qb->setMaxResults(1)->getQuery()->getOneOrNullResult();
	}

	public function delete($entity)
	{
		if ($entity instanceof ProducerLine) {
			$modelRepo = $this->_em->getRepository(ProducerModel::getClassName());
			foreach ($entity->models as $model) {
				$modelRepo->delete($model);
			}
		}

		return parent::delete($entity);
	}

}
