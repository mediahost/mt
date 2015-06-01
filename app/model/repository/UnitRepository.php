<?php

namespace App\Model\Repository;

use App\Model\Entity\UnitTranslation;

class UnitRepository extends BaseRepository
{

	public function findByName($name)
	{
		$qb = $this->createQueryBuilder()
				->select('ut')
				->from(UnitTranslation::getClassName(), 'ut')
				->innerJoin('ut.translatable', 'u')
				->where('ut.name = ?1')
				->setParameter(1, $name);
		
		
		$units = [];
		$translations = $qb->getQuery()->getResult();
		foreach ($translations as $translation) {
			$unit = $translation->translatable;
			$units[$unit->id] = $unit;
		}
		return $units;
	}

}
