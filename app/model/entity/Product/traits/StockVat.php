<?php

namespace App\Model\Entity\Traits;

use App\Model\Entity\Vat;

trait StockVat
{

	/** @ORM\ManyToOne(targetEntity="Vat") */
	private $vat; // TODO: delete

	/** @ORM\ManyToOne(targetEntity="Vat") */
	private $vatA;

	/** @ORM\ManyToOne(targetEntity="Vat") */
	private $vatB;

	public function setVat(Vat $vat, $priceBase = NULL)
	{
		$priceBase = $priceBase ? $priceBase : $this->priceBase;
		$vatAttr = 'vat' . $priceBase;
		$this->$vatAttr = $vat;
		return $this;
	}

	public function getVat($priceBase = NULL)
	{
		$priceBase = $priceBase ? $priceBase : $this->priceBase;
		$vatAttr = 'vat' . $priceBase;
		return $this->$vatAttr;
	}

}
