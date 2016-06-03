<?php

namespace App\Model\Entity;

use App\Helpers;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\ProducerLineRepository")
 *
 * @property string $name
 * @property int $priority
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

	/** @ORM\Column(type="smallint") */
	protected $priority = 0;

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

	public function getModels()
	{
		$array = [];
		foreach ($this->models as $model) {
			$array[$model->priority] = $model;
		}
		ksort($array);
		return $array;
	}

	public function hasModels()
	{
		return (bool)count($this->models);
	}

	public function getFullName($glue = ' / ')
	{
		return Helpers::concatStrings($glue, (string)$this->producer, (string)$this);
	}

	public function getFullPath($glue = '/')
	{
		return Helpers::concatStrings($glue, $this->producer->getFullPath(), $this->slug);
	}

	public function __toString()
	{
		return (string)$this->name;
	}

	public function isNew()
	{
		return $this->id === NULL;
	}

	public function getSluggableFields()
	{
		return ['name'];
	}

}
