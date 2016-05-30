<?php

namespace App\Model\Repository;

use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Kdyby\Doctrine\QueryBuilder;

class StockRepository extends BaseRepository
{

	public function findByName($name, $locale = NULL, $limit = null, $offset = null, &$totalCount = null)
	{
		$qb = $this->getQbForFindByName($name, $locale);

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

	public function findOneByName($name, $locale = NULL)
	{
		$qb = $this->getQbForFindByName($name, $locale);
		return $qb->setMaxResults(1)->getQuery()->getOneOrNullResult();
	}

	private function getQbForFindByName($name, $locale = NULL)
	{
		$qb = $this->createQueryBuilder('s')
				->select('s, p')
				->leftJoin('s.product', 'p')
				->leftJoin('p.translations', 't')
				->where('t.name LIKE :name')
				->setParameter('name', '%' . $name . '%')
				->orderBy('t.name', 'ASC');
		$this->extendQbWhereLocale($qb, $locale);

		return $qb;
	}

	private function extendQbWhereLocale(QueryBuilder &$qb, $locale)
	{
		if (is_string($locale)) {
			$qb->andWhere('t.locale = :locale')
				->setParameter('locale', $locale);
		} else if (is_array($locale)) {
			$orExpr = new Orx();
			foreach ($locale as $key => $localeItem) {
				$idKey = 'locale' . $key;
				$orExpr->add('t.locale = :' . $idKey);
				$qb->setParameter($idKey, $localeItem);
			}
			$qb->andWhere($orExpr);
		}
	}

	public function delete($stock, $deleteWithProduct = TRUE)
	{
		if ($deleteWithProduct && $stock->product) {
			parent::delete($stock->product);
		}
		return parent::delete($stock);
	}

}
