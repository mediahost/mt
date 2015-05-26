<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model;
use Nette\Utils\DateTime;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\ProductRepository")
 *
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property string $perex
 * @property ProductSeo $perex
 * @property mixed $createdBy
 * @property mixed $updatedBy
 * @property mixed $deletedBy
 * @property DateTime $createdAt
 * @property DateTime $updatedAt
 * @property DateTime $deletedAt
 * @property boolean $active
 * @property string $ean
 * @property Producer $producer
 * @property Category $mainCategory
 * @property array $categories
 */
class Product extends BaseTranslatable
{

	use Model\Translatable\Translatable;
	use Model\Blameable\Blameable;
	use Model\Loggable\Loggable;
	use Model\Timestampable\Timestampable;
	use Model\SoftDeletable\SoftDeletable;
	
	/** @ORM\Column(type="boolean") */
	protected $active = TRUE;
	
	/** @ORM\Column(type="string", length=100, nullable=true) */
	protected $ean;
	
    /** @ORM\ManyToOne(targetEntity="Producer", inversedBy="products") */
    protected $producer;
	
    /** @ORM\ManyToOne(targetEntity="Category") */
    protected $mainCategory;

	/** @ORM\ManyToMany(targetEntity="Category", inversedBy="products") */
	protected $categories;

	public function __construct($currentLocale = NULL)
	{
		parent::__construct($currentLocale);
	}

	public function __toString()
	{
		return (string) $this->name;
	}

}
