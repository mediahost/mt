<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 *
 * @property ProductTranslation $product
 * @property string $name
 * @property string $description
 */
class ProductSeo extends BaseEntity
{

	use Identifier;
	
    /** 
	 * @ORM\OneToOne(targetEntity="ProductTranslation", inversedBy="seo")
     * @ORM\JoinColumn(name="product_translation_id")
	 **/
    protected $product;

	/** @ORM\Column(type="string", length=55, nullable=true) */
	protected $name;

	/** @ORM\Column(type="text", nullable=true) */
	protected $description;
	
	public function __toString()
	{
		return (string) $this->name;
	}

}
