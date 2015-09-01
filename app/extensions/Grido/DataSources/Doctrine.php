<?php

namespace App\Extensions\Grido\DataSources;

use Grido\DataSources\Doctrine as GridoDoctrine;

/**
 * Doctrine data source extension about update method
 */
class Doctrine extends GridoDoctrine
{
	/*	 * ******************************** inline editation helpers *********************************** */

	/**
	 * Default callback for an inline editation save.
	 * @param mixed $id
	 * @param array $values
	 * @param string $idCol
	 * @return bool
	 */
//	public function update($id, array $values, $idCol)
//	{
//		return TRUE;
//	}

	/**
	 * Default callback used when an editable column has customRender.
	 * @param mixed $id
	 * @param string $idCol
	 */
	public function getRow($id, $idCol)
	{
		$qb = clone $this->qb;

		$row = current($qb->getRootAliases()) . '.' . $idCol;
		$qb->andWhere($row . ' = :idValue');
		$qb->setParameter('idValue', $id);

		return $qb->getQuery()->getOneOrNullResult();
	}

}
