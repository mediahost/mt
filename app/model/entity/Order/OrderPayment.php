<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @property Order $order
 */
class OrderPayment extends OrderPaymentBase
{

	/** @ORM\OneToOne(targetEntity="Order", mappedBy="payment") */
	protected $order;
	
	public function import(Payment $payment)
	{
		$this->name = $payment->name;
		$this->price = $payment->price;
		
		return $this;
	}

}
