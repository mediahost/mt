<?php

namespace App\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity
 *
 * @property Producer $parent
 * @property array $children
 * @property-read bool $hasChildren
 * @property string $name
 * @property array $products
 * @property array $path
 * @property string $url
 * @property array $lines
 * @property-read bool $hasLines
 */
class Producer extends BaseEntity implements IProducer
{

	const ID = 'p';
	const SEPARATOR = '-';
	
	use Identifier;
	use Model\Sluggable\Sluggable;

	/** @ORM\Column(type="string", length=256) */
	protected $name;
	
	/** @ORM\OneToMany(targetEntity="Product", mappedBy="producer") */
	protected $products;

	/** @ORM\OneToMany(targetEntity="ProducerLine", mappedBy="producer", cascade={"persist"}) */
	protected $lines;

	public function __construct($name = NULL)
	{
		if ($name) {
			$this->name = $name;
		}
		$this->lines = new ArrayCollection();
		parent::__construct();
	}
	
	public function addLine(ProducerLine $line)
	{
		$line->producer = $this;
		$this->lines->add($line);
		return $this;
	}

	public function getHasLines()
	{
		return (bool) count($this->lines);
	}

	protected function getSluggableFields()
	{
		return ['name'];
	}

	public function __toString()
	{
		return (string) $this->name;
	}

	public function isNew()
	{
		return $this->id === NULL;
	}

	public static function getItemId($id, &$type = NULL)
	{
		$allowedTypes = \App\Helpers::concatStrings('|', self::ID, ProducerLine::ID, ProducerModel::ID);
		$separator = preg_quote(self::SEPARATOR);
		if (preg_match('/^(' . $allowedTypes . ')' . $separator . '(\d+)$/i', $id, $matches)) {
			$type = $matches[1];
			return $matches[2];
		}
		return NULL;
	}

}
