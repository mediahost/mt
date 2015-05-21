<?php

namespace App\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\StockRepository")
 *
 * @property string $name
 */
class Stock extends BaseEntity
{

	use Identifier;

	/** @ORM\Column(type="string", nullable=false, unique=true) */
	protected $name;

	public function __construct()
	{
		parent::__construct();
	}

	public function __toString()
	{
		return (string) $this->name;
	}

}
