<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 *
 * @property Product $product
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
	
	public function __construct(ParameterType $type = NULL, ParameterValue $value = NULL)
	{
		parent::__construct();
		if ($type) {
			$this->type = $type;
		}
		if ($value) {
			$this->setValue($value);
		}
	}
	
	public function setValue(ParameterValue $value)
	{
		if (!$this->type) {
			throw new EntityException('Set type before set value.');
		} else if ($value->type->id !== $this->type->id) {
			throw new EntityException('Value type (' . $value->type . ') must be same as parameter type(' . $this->type . ')');
		} else {
			$this->value = $value;
		}
	}

	public function __toString()
	{
		return $this->type . '-' . $this->value;
	}

}
