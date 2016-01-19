<?php

namespace App\CronModule\Presenters;

use App\Model\Facade\PohodaFacade;
use Nette\Application\ForbiddenRequestException;
use Tracy\Debugger;

class PohodaPresenter extends BasePresenter
{

	const LOGNAME = 'pohoda_cron';

	/** @var PohodaFacade @inject */
	public $pohodaFacade;

	public function actionSynchronize($all = FALSE, $offset = 0)
	{
		proc_nice(19);
		ini_set('max_execution_time', 800);
		
		if (!$this->settings->modules->pohoda->enabled) {
			throw new ForbiddenRequestException('Pohoda module is not allowed');
		}
		
		// TODO: je potřeba prověřit, zda v celém procesu funguje kontrola tímto časem správně
		$lastDataChangeTime = $this->pohodaFacade->getLastSync(PohodaFacade::ANY_IMPORT, PohodaFacade::LAST_UPDATE);
		if ($lastDataChangeTime || $all) {
			try {
				$this->pohodaFacade->updateFullProducts($all ? NULL : $lastDataChangeTime, $offset);
				$this->status = parent::STATUS_OK;
				$this->message = 'Synchronize was successfull';
				$this->pohodaFacade->clearLastSync(PohodaFacade::ANY_IMPORT, PohodaFacade::LAST_UPDATE);
				$this->pohodaFacade->setLastSync(PohodaFacade::ANY_IMPORT, PohodaFacade::LAST_CONVERT);
			} catch (Exception $ex) {
				$this->status = parent::STATUS_ERROR;
				$this->message = 'Synchronize failed';
				Debugger::log($ex->getMessage(), self::LOGNAME);
			}
		} else {
			$this->status = parent::STATUS_OK;
			$this->message = 'No change from last import';
		}
		Debugger::log($this->message, self::LOGNAME);
	}

}
