<?php

namespace App\Model\Entity\Traits;

use App\Model\Entity\Vat;

trait StockVat
{

	/** @ORM\ManyToOne(targetEntity="Vat") */
	protected $vat; // TODO: delete

	/** @ORM\ManyToOne(targetEntity="Vat") */
	protected $vatA;

	/** @ORM\ManyToOne(targetEntity="Vat") */
	protected $vatB;

	public function setVat(Vat $vat)
	{
		$vatAttr = 'vat' . $this->priceBase;
		$this->$vatAttr = $vat;
		return $this;
	}

	public function getVat()
	{
		$vatAttr = 'vat' . $this->priceBase;
		return $this->$vatAttr;
	}

}
