<?php

namespace App\CronModule\Presenters;

use App\Model\Facade\PohodaFacade;
use Nette\Application\ForbiddenRequestException;

class PohodaPresenter extends BasePresenter
{

	const LOGNAME = 'pohoda_cron';

	/** @var PohodaFacade @inject */
	public $pohodaFacade;

	public function actionSynchronize($all = FALSE)
	{
		if (!$this->settings->modules->pohoda->enabled) {
			throw new ForbiddenRequestException('Pohoda module is not allowed');
		}
		
		$lastDataChangeTime = $this->pohodaFacade->getLastSync(PohodaFacade::ANY_IMPORT, PohodaFacade::LAST_UPDATE);
		if ($lastDataChangeTime || $all) {
//			try {
				$this->pohodaFacade->importFullProducts($lastDataChangeTime);
				$this->status = parent::STATUS_OK;
				$this->message = 'Synchronize was successfull';
//				$this->pohodaFacade->clearLastSync(PohodaFacade::ANY_IMPORT, PohodaFacade::LAST_UPDATE);
//			} catch (Exception $ex) {
//				$this->status = parent::STATUS_ERROR;
//				$this->message = 'Synchronize failed';
//				Debugger::log($ex->getMessage(), self::LOGNAME);
//			}
		} else {
			$this->status = parent::STATUS_OK;
			$this->message = 'No change from last import';
		}
	}

}
