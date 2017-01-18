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

	public function actionSynchronize($all = TRUE, $limit = NULL, $offset = NULL)
	{
		proc_nice(19);

		$optimalLimit = 100;
		$optimalMaxTimeLimit = 30;
		$maxTimeLimit = 300;
		$maxLimit = floor(($maxTimeLimit / $optimalMaxTimeLimit) * $optimalLimit);

		$limit = (int)($limit ? $limit : $optimalLimit);
		$actualLimitRate = $limit / $optimalLimit;
		$actualLimitRate = $actualLimitRate > 1 ? $actualLimitRate : 1;

		$timeLimit = $optimalMaxTimeLimit * $actualLimitRate;

		if ($timeLimit > $maxTimeLimit) {
			$this->status = parent::STATUS_ERROR;
			$this->message = 'Reached max limit - decrease your limit to ' . $maxLimit;
			return;
		}
		ini_set('max_execution_time', $timeLimit);

		if (!$this->settings->modules->pohoda->enabled) {
			throw new ForbiddenRequestException('Pohoda module is not allowed');
		}

		$lastDataChangeTime = $this->pohodaFacade->getLastSync(PohodaFacade::ANY_IMPORT, PohodaFacade::LAST_UPDATE);
		if ($lastDataChangeTime || $all) {
			try {
				$this->pohodaFacade->updateFullProducts($limit, $offset);
				$this->status = parent::STATUS_OK;
				$this->message = 'Synchronize was successful';
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
	}

}
