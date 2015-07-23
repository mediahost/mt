<?php

namespace App\Model\Repository;

use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\Tools\Pagination\Paginator;

class StockRepository extends BaseRepository
{

	public function findByName($name, $lang = NULL, $limit = null, $offset = null, &$totalCount = null)
	{
		$qb = $this->createQueryBuilder('s')
				->select('s, p')
				->leftJoin('s.product', 'p')
				->leftJoin('p.translations', 't')
				->where('t.name LIKE :name')
				->setParameter('name', '%' . $name . '%')
				->orderBy('t.name', 'ASC');

		if (is_string($lang)) {
			$qb->andWhere('t.locale = :lang')
					->setParameter('lang', $lang);
		} else if (is_array($lang)) {
			$orExpr = new Orx();
			foreach ($lang as $key => $langItem) {
				$idKey = 'lang' . $key;
				$orExpr->add('t.locale = :' . $idKey);
				$qb->setParameter($idKey, $langItem);
			}
			$qb->andWhere($orExpr);
		}

		if ($limit) {
			$paginator = new Paginator($qb);
			$totalCount = $paginator->count();
		}

		return $qb
						->getQuery()
						->setMaxResults($limit)
						->setFirstResult($offset)
						->getResult();
	}
	
	public function delete($stock, $deleteWithProduct = TRUE)
	{
		if ($deleteWithProduct && $stock->product) {
			parent::delete($stock->product);
		}
		return parent::delete($stock);
	}

}
