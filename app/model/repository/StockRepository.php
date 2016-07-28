<?php

namespace App\Model\Repository;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Doctrine\QueryException;
use Nette\Utils\Strings;

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
			$stock->product->active = FALSE;
			parent::save($stock->product);
			parent::delete($stock->product);
		}
		$stock->active = FALSE;
		parent::save($stock);
		return parent::delete($stock);
	}

	public function getLimitPricesBy($criteria, $priceName)
	{
		$priceName = self::ALIAS . '.' . $priceName;
		if (array_key_exists(self::CRITERIA_ORX_KEY, $criteria)) {
			$criteriaOr = $criteria[self::CRITERIA_ORX_KEY];
			unset($criteria[self::CRITERIA_ORX_KEY]);
		} else {
			$criteriaOr = [];
		}

		$qb = $this->createQueryBuilder(self::ALIAS)
			->whereCriteria($criteria);

		foreach ($criteriaOr as $orItem) {
			$this->appendAndOrCriteria($qb, $orItem[0], $orItem[1]);
		}

		$min = (new Expr())->min($priceName);
		$max = (new Expr())->max($priceName);
		$query = $qb
			->select($min, $max)
			->resetDQLPart('from')->from($this->getEntityName(), self::ALIAS)
			->getQuery();

		try {
			$result = $query->getSingleResult();
			return [$result[1], $result[2]];
		} catch (\Doctrine\ORM\Query\QueryException $e) {
			throw new QueryException($e, $query);
		}
	}

}
