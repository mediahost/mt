<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Nette\Http\FileUpload;
use Nette\Utils\DateTime;
use Nette\Utils\Random;

/**
 * @ORM\Entity
 * @ORM\EntityListeners({"App\Listeners\Model\Entity\FileListener"})
 *
 * @property string $filename
 * @property FileUpload $filename
 * @property string $folder
 * @property string $requestedFilename
 * @property-read bool $changed
 */
class File extends BaseEntity
{

	const FOLDER_DEFAULT = 'others';
	const FOLDER_ATTACHMENTS = 'attachments';

	use Identifier;

	/** @ORM\Column(type="string", length=256, nullable=false) */
	protected $filename;

	/** @ORM\Column(type="datetime") */
	protected $lastChange;

	/** @var FileUpload */
	private $file = NULL;

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
		return (string) $this->filename;
	}

	public function setSource($source)
	{
		if ($source instanceof FileUpload) {
			$this->setFile($source);
		} else if (is_string($source)) {
			$this->filename = $source;
		}
		return $this;
	}

	public function setFile(FileUpload $file, $requestedFilename = NULL)
	{
		$this->file = $file;
		$this->requestedFilename = $requestedFilename;
		$this->actualizeLastChange();
		return $this;
	}

	public function getFile()
	{
		return $this->file;
	}

	private function actualizeLastChange()
	{
		$this->lastChange = new DateTime();
		return $this;
	}

	public function setFolder($folder = self::FOLDER_DEFAULT)
	{
		switch ($folder) {
			case self::FOLDER_ATTACHMENTS:
			case self::FOLDER_DEFAULT:
				$this->folderToSave = $folder;
				break;
			default:
				$this->folderToSave = self::FOLDER_DEFAULT;
				break;
		}
		$this->folderToSave .= DIRECTORY_SEPARATOR . Random::generate();
		return $this;
	}

	public function getRequestedFilename()
	{
		if ($this->requestedFilename) {
			return $this->requestedFilename;
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
			return (bool) $this->file->isOk();
		}
		return FALSE;
	}

}
