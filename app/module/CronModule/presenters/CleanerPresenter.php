<?php

namespace App\CronModule\Presenters;

use App\Model\Entity\Basket;

class CleanerPresenter extends BasePresenter
{

	const KEEP_EMPTY_BASKETS = '1 months';

	public function actionCleanOldEmptyBaskets()
	{
		$basketRepo = $this->em->getRepository(Basket::getClassName());
		$emptyBaskets = $basketRepo->findEmpty(self::KEEP_EMPTY_BASKETS);

		foreach ($emptyBaskets as $basket) {
			$this->em->remove($basket);
		}
		$this->em->flush();

		$this->status = parent::STATUS_OK;
	}

}
