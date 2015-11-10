<?php

namespace App\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model;
use Nette\Http\FileUpload;
use Nette\Utils\Image as ImageUtils;
use Nette\Utils\Strings;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\CategoryRepository")
 * @ORM\EntityListeners({"App\Listeners\Model\Entity\CategoryListener"})
 *
 * @property Category $parent
 * @property array $children
 * @property-read bool $hasChildren
 * @property string $name
 * @property string $html
 * @property array $path
 * @property string $url
 * @property array $products
 * @property Image $image
 * @property Image $slider
 */
class Category extends BaseTranslatable
{

	use CategoryUrl;
	use CategoryBase;
	use Model\Translatable\Translatable;

	/** @ORM\ManyToOne(targetEntity="Category", inversedBy="children") */
	protected $parent;

	/** @ORM\OneToMany(targetEntity="Category", mappedBy="parent") */
	protected $children;
	
	/** @ORM\ManyToMany(targetEntity="Product", mappedBy="categories") */
	protected $products;

	/** @ORM\OneToOne(targetEntity="Image", cascade="all") */
	protected $image;

	/** @ORM\OneToOne(targetEntity="Image", cascade="all") */
	protected $slider;

	public function __construct($name = NULL, $currentLocale = NULL)
	{
		parent::__construct($currentLocale);
		$this->children = new ArrayCollection();
		if ($name) {
			$this->name = $name;
		}
	}
	
	public function addChild(Category $category)
	{
		$category->parent = $this;
		$this->children->add($category);
		return $this;
	}

	public function setImage($file)
	{
		if (!$this->image instanceof Image) {
			$this->image = new Image($file);
		} else if ($file instanceof FileUpload || $file instanceof ImageUtils) {
			$this->image->setSource($file);
		}
		$this->image->requestedFilename = 'category_image_' . Strings::webalize(microtime());
		$this->image->setFolder(Image::FOLDER_CATEGORIES);
		return $this;
	}

	public function setSlider($file)
	{
		if (!$this->slider instanceof Image) {
			$this->slider = new Image($file);
		} else if ($file instanceof FileUpload || $file instanceof ImageUtils) {
			$this->slider->setSource($file);
		}
		$this->slider->requestedFilename = 'category_slider_' . Strings::webalize(microtime());
		$this->slider->setFolder(Image::FOLDER_CATEGORIES);
		return $this;
	}

	public function __toString()
	{
		return (string) $this->name;
	}

	public function isNew()
	{
		return $this->id === NULL;
	}
	
	public static function getSpecialCategories()
	{
		$idNahradneDiely = 74;
		$idPrislusenstvo = 177;
		$idUsbKluce = 331;
		$idPreServis = 339;
		return [
			$idNahradneDiely,
			$idPrislusenstvo,
			$idUsbKluce,
			$idPreServis,
		];
	}

}
