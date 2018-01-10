<?php

namespace App\NotificationModule\Presenters;

use App\Extensions\FilesManager;
use App\Extensions\PaymentNotification\PaymentNotificationParser;
use App\Helpers;
use App\Model\Facade\OrderFacade;
use Nette\Utils\Callback;
use Nette\Utils\DateTime;
use Nette\Utils\FileSystem;
use PhpMimeMailParser\Parser;

class BankPresenter extends BasePresenter
{

	const FOLDER_SK_SPORITELNA = 'sk-sporitelna';
	const FOLDER_PARSED = 'parsed';

	/** @var PaymentNotificationParser @inject */
	public $paymentNotificationParser;

	/** @var OrderFacade @inject */
	public $orderFacade;

	/** @var FilesManager @inject */
	public $filesManager;

	public function actionDefault($email)
	{
		$this->paymentNotificationParser->onResolve[] = Callback::closure($this->orderFacade, 'payOrderByNotification');
		$this->paymentNotificationParser->parseMail($email);
		$this->terminate();
	}

	public function actionSkSporitelna()
	{
		$file = $this->getHttpRequest()->getFile('soubor');

		$parser = new Parser();
		$parser->setText($file->getContents());

		$bankDir = $this->filesManager->getDir(FilesManager::BANK_PAYMENTS);
		$sporDir = Helpers::getPath($bankDir, self::FOLDER_SK_SPORITELNA);
		$bankZipDir = Helpers::getPath($sporDir, 'zip');
		$bankXmlDir = Helpers::getPath($sporDir, 'xml');
		Helpers::mkdir($sporDir);
		Helpers::mkdir($bankZipDir);
		Helpers::mkdir($bankXmlDir);

		$parser->saveAttachments($bankZipDir . '/');

		foreach (scandir($bankZipDir) as $file) {
			$filename = Helpers::getPath($bankZipDir, $file);
			if (is_file($filename) && preg_match('/\.zip/i', $file)) {
				$zip = new \ZipArchive();
				$zip->open(Helpers::getPath($bankZipDir, $file), \ZipArchive::CREATE);
				$zip->extractTo($bankXmlDir);
				$zip->close();
				FileSystem::delete($filename);
			}
		}

		$this->paymentNotificationParser->onResolve[] = Callback::closure($this->orderFacade, 'payOrderByNotification');

		foreach (scandir($bankXmlDir) as $file) {
			$filename = Helpers::getPath($bankXmlDir, $file);
			if (is_file($filename)) {
				$this->paymentNotificationParser->parseXml($filename, PaymentNotificationParser::RESOLVER_SK_SPORITELNA);
				$this->moveToParsed($filename, $bankXmlDir);
			}
		}
		$this->deleteOldParsedFiles($bankXmlDir);

		$this->terminate();
	}

	private function moveToParsed($filename, $folder)
	{
		FileSystem::copy($filename, $folder . '/' . self::FOLDER_PARSED . '/' . time() . '.xml');
		FileSystem::delete($filename);
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
