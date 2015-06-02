<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\StockRepository")
 *
 * @property string $name
 */
class Variant extends BaseEntity
{
	
	const TYPE_1 = 'type1';
	const TYPE_2 = 'type2';
	const TYPE_3 = 'type3';
	const DEFAULT_TYPE = self::TYPE_1;

	use Identifier;

	/** @ORM\Column(type="string", length=10, nullable=false) */
	protected $type;

	/** @ORM\Column(type="string", length=100, nullable=false) */
	protected $value;

	public function __construct($type = self::DEFAULT_TYPE, $value = NULL)
	{
		parent::__construct();
		switch ($type) {
			case self::TYPE_1:
			case self::TYPE_2:
			case self::TYPE_3:
				$this->type = $type;
				break;
			default:
				$this->type = self::DEFAULT_TYPE;
				break;
		}
		if ($value) {
			$this->value = $value;
		}
	}

	public function __toString()
	{
		switch ($this->type) {
			case self::TYPE_1:
			case self::TYPE_2:
			case self::TYPE_3:
			default:
				return (string) $this->value;
		}
	}

}
