<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 *
 * @property ProducerModel $model
 * @property ModelParameter $parameter
 * @property Vat $vat
 * @property float $price
 */
class ParameterPrice extends BaseEntity
{

	use Identifier;
	
    /** @ORM\ManyToOne(targetEntity="ProducerModel", inversedBy="parameterPrices") */
    protected $model;

    /** @ORM\ManyToOne(targetEntity="ModelParameter") */
	protected $parameter;

	/** @ORM\Column(type="float") */
	protected $price;

	/** @ORM\ManyToOne(targetEntity="Vat") */
	protected $vat;
	
	public function __construct(ModelParameter $parameter)
	{
		$this->parameter = $parameter;
		parent::__construct();
	}

	public function setPrice($value, $withVat = FALSE)
	{
		if ($value === NULL) {
			$this->price = NULL;
		} else {
			$price = new Price($this->vat, $value, !$withVat);
			$this->price = $price->withoutVat;
		}
		return $this;
	}

	/** @return Price|NULL */
	public function getPrice()
	{
		if ($this->price === NULL) {
			return NULL;
		}
		return new Price($this->vat, $this->price);
	}
	
	public function isNew()
	{
		return $this->id === NULL;
	}

}
