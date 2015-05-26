<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

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
class Category extends BaseCategory
{

	/** @ORM\ManyToOne(targetEntity="Category", inversedBy="children") */
	protected $parent;

	/** @ORM\OneToMany(targetEntity="Category", mappedBy="parent") */
	protected $children;

}
