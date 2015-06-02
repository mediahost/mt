<?php

namespace App\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity
 *
 * @property Producer $parent
 * @property array $children
 * @property-read bool $hasChildren
 * @property string $name
 * @property array $products
 * @property array $path
 * @property string $url
 * @property array $products
 */
class Producer extends BaseEntity
{
	use Identifier;
	use CategoryUrl;
	use CategoryBase;
	use Model\Sluggable\Sluggable;

	/** @ORM\Column(type="string", length=256, nullable=true) */
	protected $name;

	/** @ORM\ManyToOne(targetEntity="Producer", inversedBy="children") */
	protected $parent;

	/** @ORM\OneToMany(targetEntity="Producer", mappedBy="parent") */
	protected $children;
	
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
	
	public function addChild(Producer $category)
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
