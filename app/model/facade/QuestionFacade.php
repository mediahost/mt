<?php

namespace App\Model\Facade;

use App\Model\Entity\Buyout\ModelQuestion;
use App\Model\Entity\Buyout\Question;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Translation\Translator;
use Nette\Object;

class QuestionFacade extends Object
{

	/** @var EntityManager @inject */
	public $em;

	/** @var Translator @inject */
	public $translator;

	/**
	 * @param string $text
	 * @param string $locale
	 * @return mixed
	 */
	public function suggestByText($text, $locale = NULL)
	{
		$qb = $this->em->getRepository(Question::getClassName())
				->createQueryBuilder('q')
				->select('q.id', 't.text')
				->join('q.translations', 't')
				->where('t.text LIKE :text')
				->setParameter('text', '%' . $text . '%')
				->orderBy('t.text', 'ASC')
				->setFirstResult(0)
				->setMaxResults(30);

		if (is_string($locale)) {
			$qb->andWhere('(t.locale = :defaultLocale OR t.locale = :locale)')
					->setParameter('defaultLocale', $this->translator->getDefaultLocale())
					->setParameter('locale', $locale);
		} else if (is_array($locale)) {
			$orExpr = new Orx();
			foreach ($locale as $key => $langItem) {
				$idKey = 'lang' . $key;
				$orExpr->add('t.locale = :' . $idKey);
				$qb->setParameter($idKey, $langItem);
			}
			$qb->andWhere($orExpr);
		}

		return $qb->getQuery()
						->getResult(Query::HYDRATE_SCALAR);
	}

	/**
	 * @param string $text
	 * @param string $locale
	 * @return mixed
	 */
	public function findOneByText($text, $locale = NULL)
	{
		$qb = $this->em->getRepository(Question::getClassName())
				->createQueryBuilder('q')
				->join('q.translations', 't')
				->where('t.text = :text')
				->setParameter('text', $text)
				->orderBy('t.text', 'ASC');

		if (is_string($locale)) {
			$qb->andWhere('t.locale = :locale')
					->setParameter('locale', $locale);
		} else if (is_array($locale)) {
			$orExpr = new Orx();
			foreach ($locale as $key => $langItem) {
				$idKey = 'locale' . $key;
				$orExpr->add('t.locale = :' . $idKey);
				$qb->setParameter($idKey, $langItem);
			}
			$qb->andWhere($orExpr);
		}

		return $qb->getQuery()
						->getOneOrNullResult();
	}

	public function findByModel($model, $locale = NULL, $limit = NULL, $offset = 0, &$totalCount = NULL)
	{
		$qb = $this->em->getRepository(ModelQuestion::getClassName())
				->createQueryBuilder('mq')
				->join('mq.model', 'm')
				->join('mq.question', 'q')
				->join('q.translations', 't')
				->where('m = :model')
				->setParameter('model', $model)
				->orderBy('t.text', 'ASC');

		if (is_string($locale)) {
			$qb->andWhere('t.locale = :locale')
					->setParameter('locale', $locale);
		} else if (is_array($locale)) {
			$orExpr = new Orx();
			foreach ($locale as $key => $langItem) {
				$idKey = 'locale' . $key;
				$orExpr->add('t.locale = :' . $idKey);
				$qb->setParameter($idKey, $langItem);
			}
			$qb->andWhere($orExpr);
		}

		if ($limit) {
			$paginator = new Paginator($qb);
			$totalCount = $paginator->count();
		}

		return $qb->getQuery()
						->setMaxResults($limit)
						->setFirstResult($offset)
						->getResult();
	}

}
