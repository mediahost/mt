<?php

namespace App\Model\Repository;

use App\Model\Entity\ModelParameter;
use App\Model\Entity\ParameterPrice;

class ModelParameterRepository extends BaseRepository
{

	public function delete($entity)
	{
		if ($entity instanceof ModelParameter) {
			$parameterPriceRepo = $this->_em->getRepository(ParameterPrice::getClassName());
			foreach ($parameterPriceRepo->findBy([
				'parameter' => $entity,
			]) as $price) {
				$parameterPriceRepo->delete($price);
			}
		}

		return parent::delete($entity);
	}

}
