<?php

namespace App\Model\Entity;

use App\Extensions\FotoHelpers;
use App\Helpers;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Nette\Http\FileUpload;
use Nette\Utils\DateTime;
use Nette\Utils\Image as ImageUtils;
use Nette\Utils\Random;

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

	const FOLDER_DEFAULT = 'others';
	const FOLDER_PRODUCTS = 'products/images';
	const FOLDER_USERS = 'users/images';
	const FOLDER_CATEGORIES = 'category/images';
	const FOLDER_PRODUCERS = 'producers/images';
	const DEFAULT_IMAGE = 'default.png';

	use Identifier;

	/** @ORM\Column(type="string", length=256, nullable=false) */
	protected $filename;

	/** @ORM\Column(type="datetime") */
	protected $lastChange;

	/** @var FileUpload */
	private $file = NULL;

	/** @var ImageUtils */
	private $image = NULL;

	/** @var string */
	protected $requestedFilename;

	/** @var string */
	private $folderToSave = self::FOLDER_DEFAULT;

	public function __construct($source)
	{
		$this->setSource($source);
		parent::__construct();
	}

	public function __toString()
	{
		return (string) $this->filename ? $this->filename : Image::DEFAULT_IMAGE;
	}
	
	public function setSource($source)
	{
		if ($source instanceof FileUpload) {
			$this->setFile($source);
		} else if ($source instanceof ImageUtils) {
			$this->setImage($source);
		} else if (is_string($source)) {
			$this->filename = $source;
		}
		return $this;
	}

	public function setFile(FileUpload $file, $requestedFilename = NULL)
	{
		$this->file = $file;
		$this->image = NULL;
		$this->requestedFilename = $requestedFilename;
		$this->actualizeLastChange();
		return $this;
	}

	public function setImage(ImageUtils $image, $requestedFilename = NULL)
	{
		$this->image = $image;
		$this->file = NULL;
		$this->requestedFilename = $requestedFilename;
		$this->actualizeLastChange();
		return $this;
	}
	
	public function getSource()
	{
		if ($this->file) {
			return $this->file;
		} else if ($this->image) {
			return $this->image;
		} else {
			return NULL;
		}
	}

	private function actualizeLastChange()
	{
		$this->lastChange = new DateTime();
		return $this;
	}

	public function setFolder($folder = self::FOLDER_DEFAULT)
	{
		switch ($folder) {
			case self::FOLDER_PRODUCTS:
			case self::FOLDER_USERS:
			case self::FOLDER_CATEGORIES:
			case self::FOLDER_PRODUCERS:
			case self::FOLDER_DEFAULT:
				$this->folderToSave = $folder;
				break;
			default:
				$this->folderToSave = self::FOLDER_DEFAULT;
				break;
		}
		return $this;
	}

	public function getRequestedFilename()
	{
		if ($this->requestedFilename) {
			return $this->requestedFilename;
		} else if ($this->file instanceof FileUpload && $this->file->name) {
			return FotoHelpers::getFilenameWithoutExt($this->file->name);
		} else if ($this->file->name) {
			return Random::generate();
		}
	}

	public function getFolder()
	{
		return $this->folderToSave;
	}

	public function isChanged()
	{
		if ($this->file instanceof FileUpload) {
			return (bool) $this->file->isImage();
		} else if ($this->image instanceof ImageUtils) {
			return TRUE;
		}
		return FALSE;
	}

	public static function returnSizedFilename($image, $sizeX = NULL, $sizeY = NULL)
	{
		$size = NULL;
		if ($sizeX) {
			$sizeY = $sizeY ? $sizeY : '0';
			$size = $sizeX . FotoHelpers::getSizeSeparator() . $sizeY;
		}
		$filename = Image::DEFAULT_IMAGE;
		if ($image instanceof Image) {
			$filename = (string) $image;
		}
		return Helpers::getPath($size, $filename);
	}

}
