<?php

namespace App\Model\Entity;

use App\Model\Entity\Traits\ProductCategories;
use App\Model\Entity\Traits\ProductParameters;
use Doctrine\Common\Collections\ArrayCollection;
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
 */
class Product extends BaseTranslatable
{

	use Model\Translatable\Translatable;
	use Model\Blameable\Blameable;
	use Model\Loggable\Loggable;
	use Model\Timestampable\Timestampable;
	use Model\SoftDeletable\SoftDeletable;
	use ProductCategories;
	use ProductParameters;
	use ProductPrices;

	/** @ORM\Column(type="boolean") */
	protected $active = TRUE;

	/** @ORM\Column(type="string", length=100, nullable=true) */
	protected $ean;

	public function __construct($currentLocale = NULL)
	{
		$this->categories = new ArrayCollection();
		$this->parameters = new ArrayCollection();
		$this->tags = new ArrayCollection();
		parent::__construct($currentLocale);
	}

	public function __toString()
	{
		return (string) $this->name;
	}

}
