<?php

namespace App\Model\Repository;

use App\Model\Entity\Buyout\ModelQuestion;
use App\Model\Entity\Buyout\Question;
use Doctrine\ORM\AbstractQuery;
use Exception;
use Kdyby\Doctrine\QueryException;

class BuyoutQuestionRepository extends BaseRepository
{

	public function delete($entity)
	{
		if ($entity instanceof Question) {
			$modelQuestionRepo = $this->_em->getRepository(ModelQuestion::getClassName());
			foreach ($modelQuestionRepo->findBy([
				'question' => $entity,
			]) as $modelQuestion) {
				$modelQuestionRepo->delete($modelQuestion);
			}
		}

		return parent::delete($entity);
	}

	public function findPairsTranslate($locale, $value = NULL, $orderBy = [], $key = NULL)
	{
		$criteria = [
			't.locale' => $locale,
		];

		if (!is_array($orderBy)) {
			$key = $orderBy;
			$orderBy = [];
		}

		if (empty($key)) {
			$key = $this->getClassMetadata()->getSingleIdentifierFieldName();
		}

		$query = $this->createQueryBuilder('q')
			->whereCriteria($criteria)
			->select($value, 'q.' . $key)
			->resetDQLPart('from')
			->from($this->getEntityName(), 'q', 'q.' . $key)
			->resetDQLPart('join')
			->join('q.translations', 't')
			->autoJoinOrderBy((array)$orderBy)
			->getQuery();

		try {
			return array_map(function ($row) {
				return reset($row);
			}, $query->getResult(AbstractQuery::HYDRATE_ARRAY));

		} catch (Exception $e) {
			throw new QueryException($e, $query);
		}
	}

}
