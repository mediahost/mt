<?php

namespace App\Model\Repository;

use App\Model\Entity\Category;
use App\Model\Entity\Product;
use App\Model\Entity\ProductTranslation;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Kdyby\Doctrine\QueryBuilder;

class ProductRepository extends BaseRepository
{

	/**
	 * @param string|array $url
	 * @param string $locale
	 * @return Product
	 */
	public function findOneByUrl($url, $locale = NULL, $onlyActive = TRUE)
	{
		if (is_string($url)) {
			$url = preg_split('@/@', $url, -1, PREG_SPLIT_NO_EMPTY);
		} else if (!is_array($url)) {
			return NULL;
		}

		$slug = array_pop($url);
		$category = NULL;
		if (count($url)) {
			$categoryRepo = $this->_em->getRepository(Category::getClassName());
			$category = $categoryRepo->findOneByUrl($url, $locale);
		}

		$qb = $this->createQueryBuilder('p')
			->join('p.translations', 't')
			->where('t.slug = :slug')
			->setParameter('slug', $slug);
		if ($category) {
			$qb->andWhere('p.mainCategory = :category')
				->setParameter('category', $category);
		}
		if ($onlyActive) {
			$qb->andWhere('p.active = :active')
				->setParameter('active', TRUE);
		}
		$this->extendQbWhereLocale($qb, $locale);

		return $qb->setMaxResults(1)->getQuery()->getOneOrNullResult();
	}

	public function findByName($name, $locale = NULL, $limit = null, $offset = null, &$totalCount = null)
	{
		$qb = $this->createQueryBuilder('p')
			->join('p.translations', 't')
			->where('t.name LIKE :name')
			->setParameter('name', '%' . $name . '%')
			->orderBy('t.name', 'ASC');
		$this->extendQbWhereLocale($qb, $locale);

		if ($limit) {
			$paginator = new Paginator($qb);
			$totalCount = $paginator->count();
		}

		return $qb->getQuery()
			->setMaxResults($limit)
			->setFirstResult($offset)
			->getResult();
	}

	private function extendQbWhereLocale(QueryBuilder &$qb, $locale, $alias = 't')
	{
		if (is_string($locale)) {
			$qb->andWhere($alias . '.locale = :locale')
				->setParameter('locale', $locale);
		} else if (is_array($locale)) {
			$orExpr = new Orx();
			foreach ($locale as $key => $localeItem) {
				$idKey = 'locale' . $key;
				$orExpr->add($alias . '.locale = :' . $idKey);
				$qb->setParameter($idKey, $localeItem);
			}
			$qb->andWhere($orExpr);
		}
	}

	public function getParameterValues($code, array $ids = [], $specificValue = NULL)
	{
		$row = 'p.parameter' . $code;
		$qb = $this->createQueryBuilder('p')
			->select('p.parameter' . $code)
			->distinct()
			->where($row . ' IS NOT NULL');
		if (count($ids)) {
			$qb->andWhere('p.id IN (:ids)')
				->setParameter('ids', $ids);
		}
		if ($specificValue !== NULL) {
			$qb->andWhere($row . ' = :specificValue')
				->setParameter('specificValue', $specificValue);
		}
		$query = $qb->getQuery();

		$values = [];
		foreach ($query->getResult(AbstractQuery::HYDRATE_ARRAY) as $item) {
			$value = reset($item);
			$values[$value] = $value;
		}
		return $values;
	}

	public function getProducersIds()
	{
		$rsm = new ResultSetMapping();
		$rsm->addScalarResult('producer_id', 'producerId');
		$sql = 'SELECT DISTINCT producer_id FROM ' . $this->getClassMetadata()->getTableName();
		$query = $this->createNativeQuery($sql, $rsm);

		return array_map(function ($row) {
			return reset($row);
		}, $query->getResult(AbstractQuery::HYDRATE_ARRAY));
	}

	public function getAccessoriesProducersIds(array $productIds = [])
	{
		return $this->getAccessoriesIds('accessories_producer_ids', $productIds);
	}

	public function getAccessoriesLinesIds(array $productIds = [])
	{
		return $this->getAccessoriesIds('accessories_line_ids', $productIds);
	}

	public function getAccessoriesModelsIds(array $productIds = [])
	{
		return $this->getAccessoriesIds('accessories_model_ids', $productIds);
	}

	private function getAccessoriesIds($column, array $productIds = [])
	{
		$rsm = new ResultSetMapping();
		$rsm->addScalarResult($column, 'ids');

		$sql = 'SELECT DISTINCT ' . $column . ' ' .
			'FROM ' . $this->getClassMetadata()->getTableName() . ' ' .
			'WHERE ' . $column . ' != \'\'';
		if (count($productIds)) {
			$sql .= ' AND id IN (' . implode(',', $productIds) . ')';
		}
		$query = $this->createNativeQuery($sql, $rsm);

		$ids = [];
		$idFields = array_map(function ($row) {
			return explode(',', reset($row));
		}, $query->getResult(AbstractQuery::HYDRATE_ARRAY));

		foreach ($idFields as $field) {
			$ids = array_merge($ids, $field);
		}
		return array_unique($ids);
	}

}
