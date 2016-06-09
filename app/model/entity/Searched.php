<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\SearchedRepository")
 *
 * @property string $text
 * @property string $ip
 * @property Product $product
 */
class Searched extends BaseEntity
{

	use Identifier;
	use Model\Timestampable\Timestampable;

	/** @ORM\Column(type="string") */
	protected $text;

	/** @ORM\Column(type="string", length=15) */
	protected $ip;

	/** @ORM\ManyToOne(targetEntity="Product") */
	protected $product;

	public function __toString()
	{
		return (string) $this->text;
	}

}
