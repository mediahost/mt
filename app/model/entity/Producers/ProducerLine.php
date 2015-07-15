<?php

namespace App\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\ProducerLineRepository")
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
	use Model\Sluggable\Sluggable;

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
	
	public function getModelsArray()
	{
		$array = [];
		foreach ($this->models as $model) {
			$array[$model->id] = (string) $model;
		}
		return $array;
	}

	public function getHasModels()
	{
		return (bool) count($this->models);
	}

	public function getFullName($glue = ' / ')
	{
		return Helpers::concatStrings($glue, (string) $this->line->producer, (string) $this);
	}

	public function __toString()
	{
		return (string) $this->name;
	}

	public function isNew()
	{
		return $this->id === NULL;
	}

	protected function getSluggableFields()
	{
		return ['name'];
	}

}
