<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 *
 * @property string $code
 * @property float $value
 */
class BankRate extends BaseEntity
{

	/**
	 * @ORM\Id
	 * @ORM\Column(type="string", length=10)
	 * @var string
	 */
	protected $code;

	/** @ORM\Column(type="float") */
	protected $value;

	public function __construct($code, $value)
	{
		$this->code = $code;
		$this->value = $value;
		parent::__construct();
	}

	public function __toString()
	{
		return (string) ($this->code . ':' . $this->value);
	}

}
