<?php

namespace Test\Parameters\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 *
 * @property string $name
 * @property string $parameter1
 * @property string $parameter2
 * @property string $parameter3
 * @property string $parameter4
 * @property string $parameter5
 * @property int $parameter6
 * @property int $parameter7
 * @property int $parameter8
 * @property int $parameter9
 * @property int $parameter10
 * @property bool $parameter11
 */
class ParameterInline extends BaseEntity
{
	
	use Identifier;

	/** @ORM\Column(type="string", length=255, nullable=true) */
	protected $name;

	/** @ORM\Column(type="string", length=255, nullable=true) */
	protected $parameter1;

	/** @ORM\Column(type="string", length=255, nullable=true) */
	protected $parameter2;

	/** @ORM\Column(type="string", length=255, nullable=true) */
	protected $parameter3;

	/** @ORM\Column(type="string", length=255, nullable=true) */
	protected $parameter4;

	/** @ORM\Column(type="string", length=255, nullable=true) */
	protected $parameter5;

	/** @ORM\Column(type="integer", nullable=true) */
	protected $parameter6;

	/** @ORM\Column(type="integer", nullable=true) */
	protected $parameter7;

	/** @ORM\Column(type="integer", nullable=true) */
	protected $parameter8;

	/** @ORM\Column(type="integer", nullable=true) */
	protected $parameter9;

	/** @ORM\Column(type="integer", nullable=true) */
	protected $parameter10;

	/** @ORM\Column(type="boolean", nullable=true) */
	protected $parameter11;
	
	public function __construct($name)
	{
		$this->name = $name;
		parent::__construct();
	}

	public function __toString()
	{
		return (string) $this->name;
	}

}
