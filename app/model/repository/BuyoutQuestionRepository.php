<?php

namespace App\Model\Repository;

use App\Model\Entity\Buyout\ModelQuestion;
use App\Model\Entity\Buyout\Question;

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

}
