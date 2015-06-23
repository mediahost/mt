<?php

namespace App\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model;
use Nette\Http\FileUpload;
use Nette\Utils\Strings;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\CategoryRepository")
 *
 * @property Category $parent
 * @property array $children
 * @property-read bool $hasChildren
 * @property string $name
 * @property array $products
 * @property array $path
 * @property string $url
 * @property array $products
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

	public function setImage(FileUpload $file)
	{
		if (!$this->image instanceof Image) {
			$this->image = new Image($file);
		} else {
			$this->image->setFile($file);
		}
		$this->image->requestedFilename = 'category_image_' . Strings::webalize(microtime());
		$this->image->setFolder(Image::FOLDER_CATEGORIES);
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

}
