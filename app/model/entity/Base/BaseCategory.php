<?php

namespace App\Model\Entity;

use App\Helpers;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * Child Class must have property: $parent, $children
 * @ORM\MappedSuperclass()
 */
abstract class BaseCategory extends BaseEntity
{

	use Identifier;
	use \Knp\DoctrineBehaviors\Model\Sluggable\Sluggable;

	/** @ORM\Column(type="string", length=256, nullable=true) */
	protected $name;
	
	/** @ORM\OneToMany(targetEntity="Product", mappedBy="producer") */
	protected $products;

	public function __construct($name = NULL)
	{
		if ($name) {
			$this->name = $name;
		}
		$this->children = new ArrayCollection();
		parent::__construct();
	}
	
	public function addChild(BaseCategory $category)
	{
		$category->parent = $this;
		$this->children->add($category);
		return $this;
	}
	
	public function getPath($reverse = TRUE)
	{
		$parent = $this->parent;
		$path = [];
		$containExistingEdge = FALSE;
		while ($parent !== NULL && !$containExistingEdge) {
			if ($parent->id === $this->id || array_key_exists($parent->id, $path)) {
				$containExistingEdge = TRUE;
			} else {
				$path[$parent->id] = $parent;
			}
			$parent = $parent->parent;
		}
		if ($reverse) {
			return array_reverse($path);
		}
		return $path;
	}
	
	public function getUrl()
	{
		$glue = '/';
		$urlPath = [];
		foreach ($this->getPath() as $parent) {
			$urlPath[] = $parent->slug;
		}
		$urlPath[] = $this->slug;
		return Helpers::concatStrings($glue, $urlPath);
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
