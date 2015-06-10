<?php

namespace App\Model\Entity\Traits;

use App\Model\Entity\Image;
use Doctrine\Common\Collections\ArrayCollection;
use Nette\Http\FileUpload;
use Nette\Utils\Strings;

/**
 * @property Image $image
 * @property ArrayCollection $images
 */
trait ProductImages
{

	/** @ORM\OneToOne(targetEntity="Image", cascade="all") */
	protected $image;

	/** @ORM\ManyToMany(targetEntity="Image") */
	protected $images;

	public function setImage(FileUpload $file)
	{
		if (!$this->image instanceof Image) {
			$this->image = new Image($file);
		} else {
			$this->image->setFile($file);
		}
		$this->image->requestedFilename = 'product_image_' . Strings::webalize(microtime());
		$this->image->setFolder(Image::FOLDER_PRODUCTS);
		return $this;
	}

}
