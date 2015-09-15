<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @property Order $order
 */
class OrderShipping extends OrderPaymentBase
{

	/** @ORM\OneToOne(targetEntity="Order", mappedBy="shipping") */
	protected $order;

	/** @ORM\ManyToOne(targetEntity="Shipping") */
	protected $origin;
	
	public function import(Shipping $shipping)
	{
		$this->name = $shipping->name;
		$this->price = $shipping->price;
		$this->origin = $shipping;
		
		return $this;
	}

}
