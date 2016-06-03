<?php

namespace App\Model\Repository;

use App\Model\Entity\Producer;
use App\Model\Entity\ProducerLine;
use App\Model\Entity\ProducerModel;

class ProducerLineRepository extends BaseRepository
{
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
