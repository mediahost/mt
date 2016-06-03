<?php

namespace App\Model\Repository;

use App\Model\Entity\ProducerLine;

class ProducerModelRepository extends BaseRepository
{
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
