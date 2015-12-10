<?php

namespace App\ApiModule\Presenters;

use App\Extensions\FilesManager;
use App\Model\Entity\Stock;
use App\Model\Entity\User;
use App\Model\Facade\StockFacade;
use App\Model\Facade\UserFacade;
use Drahak\Restful\Application\Responses\TextResponse;
use Drahak\Restful\IResource;
use Drahak\Restful\Mapping\NullMapper;
use Drahak\Restful\Security\AuthenticationException;
use Drahak\Restful\Security\SecurityException;

class DealerPresenter extends BasePresenter
{

	const CLIENT_ID = 'client_id';

	/** @var UserFacade @inject */
	public $userFacade;

	/** @var StockFacade @inject */
	public $stockFacade;

	/** @var FilesManager @inject */
	public $filesManager;

	/** @var User */
	private $dealer;

	public function checkRequirements($element)
	{
		parent::checkRequirements($element);
		try {
			$this->checkClient($this->getParameter(self::CLIENT_ID));
		} catch (SecurityException $e) {
			$this->sendErrorResource($e);
		}
	}

	private function checkClient($clientId)
	{
		if ($clientId) {
			$this->dealer = $this->userFacade->findByClientId($clientId);
			if (!$this->dealer) {
				throw new AuthenticationException('Invalid client ID');
			}
			if (!$this->dealer->group || !$this->dealer->group->level) {
				throw new SecurityException('Invalid client settings. Please contact support.');
			}
		} else {
			throw new AuthenticationException('Missing client ID.');
		}
	}

	/**
	 * From pre saved XML
	 */
	public function actionReadStocks()
	{
		proc_nice(19);
		ini_set('max_execution_time', 60);

		if (!$this->settings->modules->dealer->enabled) {
			$this->resource->state = 'error';
			$this->resource->message = 'This module is not allowed';
		} else {
			$locale = $this->translator->getLocale();
			$filename = $this->filesManager->getExportFilename(FilesManager::EXPORT_DEALER_STOCKS, $locale);
			if (is_file($filename)) {
				$content = file_get_contents($filename);
				$response = new TextResponse($content, new NullMapper(), IResource::XML);
				$this->sendResponse($response);
			} else {
				$this->resource->state = 'error';
				$this->resource->message = 'Missing \'' . $locale . '\' translation for this export';
			}
		}
	}

	/**
	 * Generated XML
	 */
	public function actionReadAvailability($id = NULL, $currency = 'eur')
	{
		proc_nice(19);
		ini_set('max_execution_time', 60);

		if (!$this->settings->modules->dealer->enabled) {
			$this->resource->state = 'error';
			$this->resource->message = 'This module is not allowed';
		} else {
			switch ($currency) {
				case 'czk':
					$this->exchange->setWeb('CZK');
					break;
			}

			if ($id) {
				$stockRepo = $this->em->getRepository(Stock::getClassName());
				$stocks[] = $stockRepo->find($id);
			} else {
				$stocks = $this->stockFacade->getExportStocksDetails(TRUE);
			}

			$this->template->stocks = $stocks;
			$this->template->level = $this->dealer->group->level;

			$this->setView('availiblity');
		}
	}

}
