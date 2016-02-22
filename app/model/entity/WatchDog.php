<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 *
 * @property Stock $stock
 * @property string $mail
 * @property bool $available
 * @property float $price
 */
class WatchDog extends BaseEntity
{

	/**
	 * @ORM\Id 
	 * @ORM\Column(type="string", nullable=false) 
	 */
	protected $mail;

	/**
	 * @ORM\Id 
	 * @ORM\ManyToOne(targetEntity="Stock", inversedBy="watchDogs", cascade={"persist"}) 
	 */
	protected $stock;

	/** @ORM\Column(type="boolean", nullable=true) */
	protected $available;

	/** @ORM\Column(type="float", nullable=true) */
	protected $price;

	/** @ORM\Column(type="integer", nullable=true) */
	protected $priceLevel;

	/** @ORM\Column(type="datetime", nullable=true) */
	protected $sendedAt;

	public function __construct($mail, Stock $stock)
	{
		$this->mail = $mail;
		$this->stock = $stock;
		parent::__construct();
	}

}
