<?php

namespace App\CronModule\Presenters;

use App\Extensions\FilesManager;
use App\Extensions\PaymentNotification\PaymentNotificationParser;
use Nette\Configurator;
use Nette\Utils\Callback;
use Nette\Utils\DateTime;
use Nette\Utils\FileSystem;

class SkSporitelnaPaymentsPresenter extends BasePresenter
{

	const FOLDER_SK_SPORITELNA = 'sk-sporitelna';
	const FOLDER_PARSED = 'parsed';

	/** @var PaymentNotificationParser @inject */
	public $paymentNotificationParser;

	/** @var FilesManager @inject */
	public $filesManager;

	protected function startup()
	{
		parent::startup();
		$this->exchange->setWeb('eur');
	}

	public function actionProcessPayments()
	{
		$this->paymentNotificationParser->onResolve[] = Callback::closure($this->orderFacade, 'payOrderByNotification');

		$folder = $this->filesManager->getDir(FilesManager::BANK_PAYMENTS) . '/' . self::FOLDER_SK_SPORITELNA;
		foreach (scandir($folder) as $file) {
			$filename = $folder . '/' . $file;
			if (is_file($filename)) {
				$this->paymentNotificationParser->parseXml($filename, PaymentNotificationParser::RESOLVER_SK_SPORITELNA);
				$this->moveToParsed($filename, $folder);
			}
		}
		$this->deleteOldParsedFiles($folder);

		$this->status = self::STATUS_OK;
	}

	private function moveToParsed($filename, $folder)
	{
		FileSystem::copy($filename, $folder . '/' . self::FOLDER_PARSED . '/' . time() . '.xml');
		if (!Configurator::detectDebugMode()) {
			FileSystem::delete($filename);
		}
	}

	private function deleteOldParsedFiles($folder, $extension = 'xml')
	{
		foreach (scandir($folder . '/' . self::FOLDER_PARSED) as $parsedFile) {
			$filename = $folder . '/' . self::FOLDER_PARSED . '/' . $parsedFile;
			if (preg_match('/^(?P<time>\d+)\.' . $extension . '$/i', $parsedFile, $matches)) {
				if (DateTime::from($matches['time']) <= DateTime::from('now - 14 days')) {
					FileSystem::delete($filename);
				}
			}
		}
	}

}
