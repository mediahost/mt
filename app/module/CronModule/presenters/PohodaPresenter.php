<?php

namespace App\CronModule\Presenters;

use App\Model\Facade\PohodaFacade;
use Exception;
use Tracy\Debugger;

class PohodaPresenter extends BasePresenter
{

	const LOGNAME = 'pohoda_cron';

	/** @var PohodaFacade @inject */
	public $pohodaFacade;

	public function actionSynchronize($all = FALSE)
	{
		$lastDataChangeTime = $this->pohodaFacade->getLastSync(PohodaFacade::ANY_IMPORT, PohodaFacade::LAST_UPDATE);
		if ($lastDataChangeTime || $all) {
			try {
				$this->pohodaFacade->importProducts();
			} catch (Exception $ex) {
				$this->status = parent::STATUS_ERROR;
				$this->message = 'Synchronize failed';
				Debugger::log($ex->getMessage(), self::LOGNAME);
			}

			$this->status = parent::STATUS_OK;
			$this->message = 'Synchronize was successfull';
			$this->pohodaFacade->clearLastSync(PohodaFacade::ANY_IMPORT, PohodaFacade::LAST_UPDATE);
		} else {
			$this->status = parent::STATUS_OK;
			$this->message = 'No change from last import';
		}
	}

}
