<?php

namespace App\CronModule\Presenters;

use App\Extensions\Settings\SettingsStorage;
use App\Model\Entity\ProductSign;
use App\Model\Entity\Sign;
use Nette\Utils\DateTime;

class CleanerPresenter extends BasePresenter
{

	/** @var SettingsStorage @inject */
	public $settings;

	public function actionCleanOldEmptyBaskets()
	{
		$this->basketFacade->removeOldEmptyBaskets();

		$this->status = parent::STATUS_OK;
	}

	public function actionCleanOldBaskets()
	{
		$this->basketFacade->removeOldBaskets();

		$this->status = parent::STATUS_OK;
	}

	public function actionCleanOldSigns()
	{
		$signRepo = $this->em->getRepository(Sign::getClassName());
		$productSignRepo = $this->em->getRepository(ProductSign::getClassName());
		$signsSetting = $this->settings->modules->signs;

		// new sign
		if ($signsSetting->enabled) {
			$sign = $signRepo->find($signsSetting->values->new);
			if ($sign) {
				$productSignRepo->deleteBy([
					'sign' => $sign,
					'createdAt <=' => DateTime::from('-' . $signsSetting->clearOlder),
				]);
			}
		}

		$this->status = parent::STATUS_OK;
	}

}
