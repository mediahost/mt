<?php

namespace App\Model\Entity\Traits;

use App\Model\Entity\Image;
use Doctrine\Common\Collections\ArrayCollection;
use Nette\Http\FileUpload;
use Nette\Utils\Strings;

/**
 * @property ArrayCollection $similars
 * @property ArrayCollection $similarsWithMe
 */
trait ProductImages
{

	/** @ORM\OneToOne(targetEntity="Image", cascade="all") */
	protected $image;

	/** @ORM\ManyToMany(targetEntity="Image") */
	protected $images;

	public function setImage(FileUpload $file)
	{
		if (!$this->logo instanceof Image) {
			$this->logo = new Image($file);
		} else {
			$this->logo->setFile($file);
		}
		$this->logo->requestedFilename = 'product_image_' . Strings::webalize(microtime());
		$this->logo->setFolder(Image::FOLDER_PRODUCTS);
		return $this;
	}

}
