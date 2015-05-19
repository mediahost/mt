<?php

namespace Test\Examples\Model\Entity\Asociation\OneToManyBidirectional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * Inverzed
 * @ORM\Entity
 *
 * @property string $name
 * @property ArrayCollection $features
 */
class Product extends BaseEntity
{

	use \Kdyby\Doctrine\Entities\Attributes\Identifier;

	/** @ORM\Column(type="string", nullable=false) */
	protected $name;

	/**
	 * @ORM\OneToMany(targetEntity="Feature", mappedBy="product")
	 */
	protected $features;

	public function __construct()
	{
		parent::__construct();
		$this->features = new ArrayCollection();
	}

}
