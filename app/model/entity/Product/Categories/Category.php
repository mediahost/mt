<?php

namespace App\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity
 *
 * @property Category $parent
 * @property array $children
 * @property string $name
 * @property array $products
 * @property array $path
 * @property string $url
 * @property array $products
 */
class Category extends BaseTranslatable
{

	use CategoryUrl;
	use Model\Translatable\Translatable;
	use Model\Sluggable\Sluggable;

	/** @ORM\ManyToOne(targetEntity="Category", inversedBy="children") */
	protected $parent;

	/** @ORM\OneToMany(targetEntity="Category", mappedBy="parent") */
	protected $children;
	
	/** @ORM\OneToMany(targetEntity="Product", mappedBy="producer") */
	protected $products;

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

	protected function getSluggableFields()
	{
		return ['name'];
	}

	public function __toString()
	{
		return (string) $this->name;
	}

}
