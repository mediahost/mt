<?php

namespace App\Model\Repository;

use App\Model\Entity\Product;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\Tools\Pagination\Paginator;

class ProductRepository extends BaseRepository
{

	/**
	 * @param string|array $url
	 * @param string $lang
	 * @return Product
	 */
	public function findOneByUrl($url, $lang = NULL)
	{
		if (is_string($url)) {
			$url = preg_split('@/@', $url, -1, PREG_SPLIT_NO_EMPTY);
		} else if (!is_array($url)) {
			return NULL;
		}

		$slug = array_pop($url);
		$category = NULL;
		if (count($url)) {
//			$category = $this->findOneByUrl($url, $lang); // search only product with right category
		}

		$qb = $this->createQueryBuilder('p')
				->join('p.translations', 't')
				->where('t.slug = :slug')
				->setParameter('slug', $slug);
		if ($category) {
//			$qb->andWhere('c.parent = :parent')
//					->setParameter('parent', $category);
		}
		if ($lang) {
			$qb->andWhere('t.locale = :lang')
					->setParameter('lang', $lang);
		}

		try {
			return $qb->setMaxResults(1)->getQuery()->getSingleResult();
		} catch (NoResultException $e) {
			return NULL;
		}
	}

	public function findByName($name, $lang = NULL, $limit = null, $offset = null, &$totalCount = null)
	{
		$qb = $this->createQueryBuilder('p')
				->join('p.translations', 't')
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

	public function findAllWithTranslation()
	{
		$qb = $this->createQueryBuilder('p')
				->select('p, t')
				->join('p.translations', 't');

		return $qb->getQuery()
						->getResult();
	}

}
