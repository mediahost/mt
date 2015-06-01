<?php

namespace App\Model\Entity\Traits;

use App\Model\Entity\Product;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @property ArrayCollection $similars
 * @property ArrayCollection $similarsWithMe
 */
trait ProductSimilars
{

	/**
	 * @ORM\ManyToMany(targetEntity="Product", inversedBy="similarsWithMe")
	 * @ORM\JoinTable(name="product_similars",
	 *      joinColumns={@ORM\JoinColumn(name="product_id", referencedColumnName="id")},
	 *      inverseJoinColumns={@ORM\JoinColumn(name="similar_product_id", referencedColumnName="id")}
	 *      )
	 */
	protected $similars;

	/** @ORM\ManyToMany(targetEntity="Product", mappedBy="similars") */
	protected $similarsWithMe;

	public function setSimilars(array $similars)
	{
		$removeIdles = function ($key, Product $product) use ($similars) {
			if (!in_array($product, $similars, TRUE)) {
				$this->removeSimilar($product);
			}
			return TRUE;
		};
		$this->similars->forAll($removeIdles);
		foreach ($similars as $category) {
			$this->addSimilar($category);
		}
		return $this;
	}

	public function addSimilar(Product $product)
	{
		if (!$this->similars->contains($product)) {
			$this->similars->add($product);
		}
		return $this;
	}

	public function removeSimilar(Product $product)
	{
		return $this->similars->removeElement($product);
	}

}
