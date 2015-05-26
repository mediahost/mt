<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @property Producer $parent
 * @property array $children
 * @property string $name
 * @property array $products
 * @property array $path
 * @property string $url
 * @property array $products
 */
class Producer extends BaseCategory
{

	/** @ORM\ManyToOne(targetEntity="Producer", inversedBy="children") */
	protected $parent;

	/** @ORM\OneToMany(targetEntity="Producer", mappedBy="parent") */
	protected $children;

}
