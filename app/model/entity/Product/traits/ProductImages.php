<?php

namespace App\Model\Entity\Traits;

use App\Model\Entity\Image;
use Doctrine\Common\Collections\ArrayCollection;
use Nette\Http\FileUpload;
use Nette\Utils\Image as ImageUtils;
use Nette\Utils\Strings;

/**
 * @property Image $image
 * @property-read array $images
 * @property-write Image $otherImage
 */
trait ProductImages
{

	/** @ORM\OneToOne(targetEntity="Image", cascade="all") */
	protected $image;

	/**
	 * @ORM\ManyToMany(targetEntity="Image", cascade="all")
	 * @var ArrayCollection
	 */
	protected $images;

	public function setImage($file)
	{
		if (!$this->image instanceof Image) {
			$this->image = new Image($file);
		} else if ($file instanceof FileUpload || $file instanceof ImageUtils) {
			$this->image->setSource($file);
		}
		$this->image->requestedFilename = 'product_image_' . Strings::webalize(microtime());
		$this->image->setFolder(Image::FOLDER_PRODUCTS);
		return $this;
	}

	public function setOtherImage($file)
	{
		$image = new Image($file);
		$image->requestedFilename = 'product_image_' . Strings::webalize(microtime());
		$image->setFolder(Image::FOLDER_PRODUCTS);

		$this->images->add($image);

		return $this;
	}

	public function hasOtherImage(Image $image)
	{
		$containImage = function ($key, Image $item) use ($image) {
			return $item->id === $image->id;
		};
		return $this->images->exists($containImage);
	}

	public function removeOtherImage($id)
	{
		$removeById = function ($key, Image $image) use ($id) {
			if ((int)$image->id === (int)$id) {
				$this->images->removeElement($image);
			}
			return TRUE;
		};
		$this->images->forAll($removeById);
		return $this;
	}

	public function getSecondImage()
	{
		foreach ($this->images as $image) {
			return $image;
		}
		return NULL;
	}

}
