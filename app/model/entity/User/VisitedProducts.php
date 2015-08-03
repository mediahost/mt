<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\VisitedProductRepository")
 */
class VisitedProduct extends BaseEntity
{

	/**
	 * @ORM\Id
	 * @ORM\ManyToOne(targetEntity="User", inversedBy="lastVisited")
	 * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
	 */
	protected $user;
	
	/**
	 * @ORM\Id
	 * @ORM\ManyToOne(targetEntity="Stock")
	 * @ORM\JoinColumn(name="stock_id", referencedColumnName="id", nullable=false)
	 */
	protected $stock;
	
	/**
	 * @ORM\Column(type="datetime", nullable=false)
	 */
	protected $visited;

	public function __toString()
	{
		return (string) 'ToDo';
	}

	public function toArray()
	{
		return ['ToDo'];
	}

}
