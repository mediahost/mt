<?php

namespace App\Model\Entity;

use App\Extensions\FotoHelpers;
use App\Helpers;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Nette\Http\FileUpload;
use Nette\Utils\DateTime;

/**
 * @ORM\Entity
 * @ORM\EntityListeners({"App\Listeners\Model\Entity\ImageListener"})
 *
 * @property string $filename
 * @property FileUpload $filename
 * @property-read bool $changed
 */
class Image extends BaseEntity
{

	const FOLDER_PRODUCTS = 'products/images';
	const FOLDER_USERS = 'users/images';
	const DEFAULT_IMAGE = 'default.png';

	use Identifier;

	/** @ORM\Column(type="string", length=256, nullable=false) */
	protected $filename;

	/** @ORM\Column(type="date") */
	protected $lastChange;

	/** FileUpload */
	public $file;

	/** string */
	public $requestedFilename;

	public function __construct($file)
	{
		if ($file instanceof FileUpload) {
			$this->setFile($file);
		} else if (is_string($file)) {
			$this->filename = $file;
		}
		parent::__construct();
	}

	public function __toString()
	{
		return (string) $this->filename ? $this->filename : Image::DEFAULT_IMAGE;
	}

	public function setFile(FileUpload $file, $requestedFilename = NULL)
	{
		$this->file = $file;
		$this->requestedFilename = $requestedFilename;
		$this->lastChange = new DateTime();
		return $this;
	}

	public function isChanged()
	{
		return (bool) $this->file->isImage();
	}
	
	public static function returnSizedFilename($image, $sizeX = NULL, $sizeY = NULL)
	{
		$size = NULL;
		if ($sizeX && $sizeY) {
			$size = $sizeX . FotoHelpers::getSizeSeparator() . $sizeY;
		}
		$filename = Image::DEFAULT_IMAGE;
		if ($image instanceof Image) {
			$filename = (string) $image;
		}
		return Helpers::getPath($size, $filename);
	}

}
