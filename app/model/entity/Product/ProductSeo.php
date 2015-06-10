<?php

namespace App\Model\Entity;

use App\Helpers;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 *
 * @property ProductTranslation $product
 * @property string $name
 * @property string $keywords
 * @property string $description
 * @property-read boolean $isEmpty
 */
class ProductSeo extends BaseEntity
{

	use Identifier;

	/**
	 * @ORM\OneToOne(targetEntity="ProductTranslation", inversedBy="seo")
	 * @ORM\JoinColumn(name="product_translation_id")
	 * */
	protected $product;

	/** @ORM\Column(type="string", length=55, nullable=true) */
	protected $name;

	/** @ORM\Column(type="text", nullable=true) */
	protected $keywords;

	/** @ORM\Column(type="text", nullable=true) */
	protected $description;

	public function __construct($name = NULL, $keywords = NULL, $description = NULL)
	{
		parent::__construct();
		$this->name = $name;
		$this->keywords = $keywords;
		$this->description = $description;
	}

	public function setName($name)
	{
		if (!empty($name)) {
			$this->name = $name;
		}
		return $this;
	}

	public function setKeywords($keywords, $glue = ', ')
	{
		if (is_array($keywords)) {
			$keywords = Helpers::concatArray($keywords, $glue);
		}
		if (!empty($keywords)) {
			$this->keywords = $keywords;
		}
		return $this;
	}

	public function setDescription($description)
	{
		if (!empty($description)) {
			$this->description = $description;
		}
		return $this;
	}

	public function getIsEmpty()
	{
		$nameIsEmpty = !$this->name;
		$keywordsIsEmpty = !$this->keywords;
		$descriptionIsEmpty = !$this->description;

		return $nameIsEmpty && $keywordsIsEmpty && $descriptionIsEmpty;
	}

	public function __toString()
	{
		return (string) $this->name;
	}

}
