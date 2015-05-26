<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 *
 * @property ParameterType $type
 * @property ParameterValue $value
 */
class Parameter extends BaseEntity
{

	use Identifier;
	
	/** @ORM\ManyToOne(targetEntity="Product", inversedBy="parameters") */
	protected $product;

	/** @ORM\ManyToOne(targetEntity="ParameterType", inversedBy="parameters") */
	protected $type;

	/** @ORM\ManyToOne(targetEntity="ParameterValue", inversedBy="parameters") */
	protected $value;

	public function __toString()
	{
		return $this->type . '-' . $this->value;
	}

}
