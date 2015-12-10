<?php

namespace App\Extensions;

use App\Helpers;
use Exception;
use Nette\DI\Container;
use Nette\Object;
use Nette\Utils\FileSystem;

class FilesManager extends Object
{
	// <editor-fold desc="constants & variables">
	
	const POHODA_IMPORT = 'pohoda-xml-import';
	const EXPORTS = 'exports';
	const EXPORT_STOCKS = 'stocks';
	const EXPORT_CATEGORIES = 'categories';

	/** @var string */
	private $rootFolder;

	// </editor-fold>
	// <editor-fold desc="injects">

	/** @var Container @inject */
	public $container;

	// </editor-fold>
	// <editor-fold desc="setters">

	public function setRootFolder($folder)
	{
		$this->rootFolder = $folder;
		return $this;
	}

	// </editor-fold>
	
	public function getExportFilename($type, $locale, $ext = 'xml')
	{
		switch ($type) {
			case self::EXPORT_STOCKS:
				$dir = $this->getDir(self::EXPORTS);
				$path = Helpers::getPath($dir, self::EXPORT_STOCKS, $locale);
				FileSystem::createDir($path);
				return $path . '/' . self::EXPORT_STOCKS . '.' . $ext;

			default:
				throw new FilesManagerException('Unknown type for export filename.');
		}
	}
	
	public function getDir($name)
	{
		$path = $this->getRootDir();
		switch ($name) {
			case self::EXPORTS:
				$path = Helpers::getPath($path, self::EXPORTS);
				break;
			case self::POHODA_IMPORT:
				$path = Helpers::getPath($path, self::POHODA_IMPORT);
				break;

			default:
				throw new FilesManagerException('Unknown name for dir.');
		}
		FileSystem::createDir($path);
		return $path;
	}

	private function getRootDir()
	{
		$root = Helpers::getPath($this->container->parameters['appDir'], '..', $this->rootFolder);
		FileSystem::createDir($root);
		return $root;
	}

}

class FilesManagerException extends Exception {
	
}
