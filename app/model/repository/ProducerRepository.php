<?php

namespace App\Model\Repository;

use App\Model\Entity\Producer;
use App\Model\Entity\ProducerLine;

class ProducerRepository extends BaseRepository
{
	public function findOneByUrl($url)
	{
		if (is_string($url)) {
			$url = preg_split('@/@', $url, -1, PREG_SPLIT_NO_EMPTY);
		} else if (!is_array($url)) {
			return NULL;
		}

		$slug = array_pop($url);

		$qb = $this->createQueryBuilder('p')
			->where('p.slug = :slug')
			->setParameter('slug', $slug);

		return $qb->setMaxResults(1)->getQuery()->getOneOrNullResult();
	}

	public function delete($entity)
	{
		if ($entity instanceof Producer) {
			$lineRepo = $this->_em->getRepository(ProducerLine::getClassName());
			foreach ($entity->lines as $line) {
				$lineRepo->delete($line);
			}
		}

		return parent::delete($entity);
	}

}
