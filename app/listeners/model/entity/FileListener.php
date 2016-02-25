<?php

namespace App\Listeners\Model\Entity;

use App\Extensions\FilesManager;
use App\Helpers;
use App\Model\Entity\File;
use Doctrine\ORM\Events;
use Kdyby\Events\Subscriber;
use Nette\Object;
use Nette\Utils\FileSystem;

class FileListener extends Object implements Subscriber
{

	/** @var FilesManager @inject */
	public $filesManager;

	public function getSubscribedEvents()
	{
		return array(
			Events::prePersist,
			Events::preUpdate,
			Events::postRemove,
		);
	}

	// <editor-fold desc="listeners redirectors">

	public function prePersist($params)
	{
		$this->saveFile($params);
	}

	public function preUpdate($params)
	{
		$this->saveFile($params);
	}

	public function postRemove($params)
	{
		$this->removeFile($params);
	}

	// </editor-fold>

	private function saveFile(File $file)
	{
		if ($file->changed) {
			$root = $this->filesManager->getDir(FilesManager::MAILS);
			$filename = Helpers::getPath($file->folder, $file->requestedFilename);
			$realFilename = Helpers::getPath($root, $filename);
			FileSystem::copy($file->file, $realFilename);
			if ($file->filename) {
				FileSystem::delete($realFilename);
			}
			$file->filename = $filename;
		}
	}

	private function removeFile(File $file)
	{
		if ($file->filename) {
			$root = $this->filesManager->getDir(FilesManager::MAILS);
			$realFilename = Helpers::getPath($root, $file->filename);
			FileSystem::delete($realFilename);
		}
	}

}
