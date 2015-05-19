<?php

namespace Test\Examples\Model\Entity\Asociation\OneToManySelfReferencing;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * Owner
 * @ORM\Entity
 * 
 * @property string $name
 * @property ArrayCollection $children
 * @property Category $parent
 */
class Category extends BaseEntity
{

	use \Kdyby\Doctrine\Entities\Attributes\Identifier;

	/** @ORM\Column(type="string") */
	protected $name;

	/**
	 * @ORM\OneToMany(targetEntity="Category", mappedBy="parent")
	 * */
	protected $children;

	/**
	 * @ORM\ManyToOne(targetEntity="Category", inversedBy="children")
	 * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
	 * */
	protected $parent;

	public function __construct()
	{
		parent::__construct();
		$this->children = new ArrayCollection();
	}

}
