<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 *	
 * @property int $id
 * @property string $name
 * @property bool $allowed
 * @property string $ids
 */
class PohodaStorage extends BaseEntity
{

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @var string
	 */
	protected $id;
	
	/** @ORM\Column(type="string", length=64) */
	protected $name;

	/** @ORM\Column(type="boolean", nullable=true) */
	protected $allowed = FALSE;

	/** @ORM\OneToMany(targetEntity="PohodaItem", mappedBy="storage") */
	protected $products;

	public function __construct($id)
	{
		$this->id = $id;
		parent::__construct();
	}

	public function __toString()
	{
		return (string) $this->name;
	}

}
