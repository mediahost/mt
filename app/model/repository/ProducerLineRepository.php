<?php

namespace App\Model\Repository;

use App\Model\Entity\ProducerLine;
use App\Model\Entity\ProducerModel;

class ProducerLineRepository extends BaseRepository
{

	public function delete($entity)
	{
		if ($entity instanceof ProducerLine) {
			$modelRepo = $this->_em->getRepository(ProducerModel::getClassName());
			foreach ($entity->models as $model) {
				$modelRepo->delete($model);
			}
		}

		return parent::delete($entity);
	}

}
