<?php

namespace App\Extensions;

use App\Helpers;
use Exception;
use Nette\Http\FileUpload;
use Nette\IOException;
use Nette\Object;
use Nette\Utils\FileSystem;
use Nette\Utils\Image;
use Nette\Utils\UnknownImageFileException;
use Tracy\Debugger;

class Foto extends Object
{

	/** @var string */
	private $rootFolder;

	/** @var string */
	private $originalFolder;

	/** @var string */
	private $defaultFilename;

	/** @var string */
	private $defaultFormat;

	public function setFolders($folder, $originalFolderName)
	{
		$this->rootFolder = $folder;
		FileSystem::createDir($folder);

		$this->originalFolder = Helpers::getPath($folder, $originalFolderName);
		FileSystem::createDir($this->originalFolder);

		return $this;
	}

	public function setDefaultImage($defaultFilename, $defaultFormat = NULL)
	{
		if (!$defaultFilename) {
			throw new FotoException('Default filename must be set.');
		}
		if (!is_file(Helpers::getPath($this->originalFolder, $defaultFilename))) {
			throw new FotoException('Default filename must exist in original folder \'' . $this->originalFolder . '\'.');
		}
		$this->defaultFilename = $defaultFilename;
		switch ($defaultFormat) {
			case 'jpg':
				$this->defaultFormat = Image::JPEG;
				break;
			case 'gif':
				$this->defaultFormat = Image::GIF;
				break;
			case 'png':
			default:
				$this->defaultFormat = Image::PNG;
				break;
		}

		return $this;
	}

	/**
	 * Display requested image
	 * @param string $name
	 * @param string $size in format 'width-height'
	 * @return NULL
	 */
	public function display($name = NULL, $size = NULL)
	{
		$filename = Helpers::getPath($this->originalFolder, $name);

		if (empty($name) || !is_file($filename)) {
			$name = $this->defaultFilename;
			$filename = Helpers::getPath($this->originalFolder, $name);
		}

		$sizeX = 0;
		$sizeY = 0;
		$resizeMethod = Image::FIT;
		if (preg_match('@^(\d+)' . preg_quote(FotoHelpers::getSizeSeparator()) . '(\d+)$@', $size, $matches)) {
			$sizeX = $matches[1];
			$sizeY = $matches[2];
			if ((int) $sizeY === 0) {
				$resizeMethod = Image::EXACT;
			}
		}

		if ($sizeX > 0) {
			$resizedPath = Helpers::getPath($this->rootFolder, $sizeX . FotoHelpers::getSizeSeparator() . $sizeY);
			FileSystem::createDir(Helpers::getPath($resizedPath, FotoHelpers::getFolderFromPath($name)));
			$resized = Helpers::getPath($resizedPath, $name);

			if (!file_exists($resized) || filemtime($filename) > filemtime($resized)) {
				$img = Image::fromFile($filename);

				switch ($resizeMethod) {
					case Image::EXACT:
						$sizeY = $sizeX;
						break;
					case Image::FIT:
					default:
						$sizeX = min($sizeX, $img->width);
						$sizeY = min($sizeY, $img->height);
						break;
				}

				$img->resize($sizeX, $sizeY, $resizeMethod);
				$img->save($resized);
			}

			$filename = $resized;
		}

		try {
			$finishImage = Image::fromFile($filename);
			$recognizedType = FotoHelpers::recognizeTypeFromFileExtension($filename);
			if ($recognizedType) {
				$finishImage->send($recognizedType);
			} else {
				$finishImage->send($this->defaultFormat);
			}
		} catch (Exception $ex) {
			Debugger::log($ex->getMessage(), 'image');
		}
		return NULL;
	}

	/**
	 * Save image and return used filename
	 * @param FileUpload|string $source
	 * @param type $filename filename for save
	 * @param type $folder set added folder to save image (for ex. products)
	 * @param int $defaultFormat when format not loaded from source
	 * @return string filename
	 * @throws FotoException
	 * @throws UnknownImageFileException
	 */
	public function create($source, &$filename, $folder = NULL, $defaultFormat = Image::PNG)
	{
		$format = NULL;
		if ($source instanceof Image) { // image
			$img = $source;
		} else if ($source instanceof FileUpload) { // uploaded
			$format = FotoHelpers::getFormatFromString($source->contents);
			$img = Image::fromString($source->contents);
		} else if (is_string($source)) { // filename or string
			if (file_exists($source)) {
				$img = Image::fromFile($source, $format);
		} else {
				$format = FotoHelpers::getFormatFromString($source->contents);
				$img = Image::fromString($source);
			}
		} else {
			throw new FotoException('This source format isn\'t supported');
		}
		if ($format === NULL) {
			$format = $defaultFormat;
		}

		$filename = FotoHelpers::getExtendedFilename($filename, $format);

		$folderFullPath = Helpers::getPath($this->originalFolder, $folder);
		FileSystem::createDir($folderFullPath);

		$this->delete(Helpers::getPath($folder, $filename));

		$fullFilename = Helpers::getPath($folderFullPath, $filename);
		$img->save($fullFilename);

		return $fullFilename;
	}

	public function delete($name, $deleteResized = TRUE)
	{
		$filename = Helpers::getPath($this->originalFolder, $name);
		FotoHelpers::deleteFile($filename);
		if ($deleteResized) {
			$this->deleteThumbnails($name);
		}
	}

	public function deleteThumbnails($name)
	{
		foreach (scandir($this->rootFolder) as $dir) {
			if (preg_match('@^\d+' . preg_quote(FotoHelpers::getSizeSeparator()) . '\d+$@', $dir)) {
				$filename = Helpers::getPath($this->rootFolder, $dir, $name);
				FotoHelpers::deleteFile($filename);
			}
		}
	}

}

class FotoHelpers extends Object
{

	/**
	 * Delete file or log it
	 * @param string $filename
	 * @return boolean
	 */
	public static function deleteFile($filename)
	{
		try {
			if ($filename) {
				FileSystem::delete($filename);
			}
		} catch (IOException $ex) {
			Debugger::log($filename . ' wasn\'t deleted.', 'image');
			return FALSE;
		}
		return TRUE;
	}

	public static function getFolderFromPath($path)
	{
		$splited = preg_split('~/~', $path, -1, PREG_SPLIT_NO_EMPTY);
		if (count($splited) > 1) {
			array_pop($splited);
			return Helpers::getPath($splited);
		}
		return NULL;
	}

	public static function getFormatFromString($s)
	{
		$types = [
			'image/jpeg' => Image::JPEG,
			'image/gif' => Image::GIF,
			'image/png' => Image::PNG
		];
		$type = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $s);
		return isset($types[$type]) ? $types[$type] : NULL;
	}

	public static function recognizeTypeFromFileExtension($filename)
	{
		switch (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
			case 'jpg':
			case 'jpeg':
				return Image::JPEG;
			case 'png':
				return Image::PNG;
			case 'gif':
				return Image::GIF;
			default:
				return NULL;
		}
	}

	public static function getExtendedFilename($filename, $format)
	{
		switch ($format) {
			case Image::JPEG:
				$ext = 'jpg';
				break;
			case Image::PNG:
				$ext = 'png';
				break;
			case Image::GIF:
				$ext = 'gif';
				break;
			default:
				throw new FotoException('This requested format isn\'t supported');
		}
		return $filename . '.' . $ext;
	}

	public static function getFilenameWithoutExt($filenameWithExt)
	{
		return preg_replace('/\.(\w+)$/', '', $filenameWithExt);
	}

	public static function getSizeSeparator()
	{
		return '-';
	}

}

class FotoException extends Exception
{
	
}
