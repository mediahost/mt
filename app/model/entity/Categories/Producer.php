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
 * @property array $lines
 */
class Producer extends BaseEntity
{
	use Identifier;
	use CategoryUrl;
	use CategoryBase;
	use Model\Sluggable\Sluggable;

	/** @ORM\Column(type="string", length=256) */
	protected $name;

	/** @ORM\ManyToOne(targetEntity="Producer", inversedBy="children") */
	protected $parent;

	/** @ORM\OneToMany(targetEntity="Producer", mappedBy="parent") */
	protected $children;
	
	/** @ORM\OneToMany(targetEntity="Product", mappedBy="producer") */
	protected $products;

	/** @ORM\OneToMany(targetEntity="ProducerLine", mappedBy="producer", cascade={"persist"}) */
	protected $lines;

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
	
	public function addLine(ProducerLine $line)
	{
		$line->producer = $this;
		$this->lines->add($line);
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

	public function isNew()
	{
		return $this->id === NULL;
	}

}
