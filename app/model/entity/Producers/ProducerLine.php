<?php

namespace App\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 *
 * @property string $name
 * @property Producer $producer
 * @property array $models
 * @property-read bool $hasModels
 */
class ProducerLine extends BaseEntity implements IProducer
{

	const ID = 'l';

	use Identifier;

	/** @ORM\Column(type="string", length=256) */
	protected $name;

	/** @ORM\ManyToOne(targetEntity="Producer", inversedBy="lines") */
	protected $producer;

	/** @ORM\OneToMany(targetEntity="ProducerModel", mappedBy="line", cascade={"persist"}) */
	protected $models;

	public function __construct($name)
	{
		$this->name = $name;
		$this->models = new ArrayCollection();
		parent::__construct();
	}

	public function addModel(ProducerModel $model)
	{
		$model->line = $this;
		$this->models->add($model);
		return $this;
	}

	public function getHasModels()
	{
		return (bool) count($this->models);
	}

	public function __toString()
	{
		return (string) $this->name;
	}

	public function isNew()
	{
		return $this->id === NULL;
	}

}
