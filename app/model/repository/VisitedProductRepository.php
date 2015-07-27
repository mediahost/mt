<?php

namespace App\Model\Repository;

use App\Model\Entity\Stock;
use App\Model\Entity\User;

class VisitedProductRepository extends BaseRepository
{

	public function findOneByUserAndStock(User $user, Stock $stock)
	{
		return $this->findOneBy([
					'user' => $user,
					'stock' => $stock,
		]);
	}

}
