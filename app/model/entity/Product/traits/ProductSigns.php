<?php

namespace App\Model\Entity\Traits;

use App\Model\Entity\ProductSign;
use App\Model\Entity\Sign;
use Doctrine\Common\Collections\ArrayCollection;

trait ProductSigns
{

	/** @ORM\OneToMany(targetEntity="ProductSign", mappedBy="product", cascade={"persist"}) */
	private $signs;

	public function getSigns()
	{
		$signs = new ArrayCollection();

		$addSign = function ($key, ProductSign $signConn) use ($signs) {
			return $signs->add($signConn->sign);
		};
		$this->signs->forAll($addSign);

		return $signs;
	}

	public function addSign(Sign $sign)
	{
		if (!$this->hasSign($sign)) {
			$signConn = new ProductSign();
			$signConn
				->setProduct($this)
				->setSign($sign);
			$this->signs->add($signConn);
		}
		return $this;
	}

	public function removeSign(ProductSign $signConn)
	{
		return $this->signs->removeElement($signConn);
	}

	public function hasSign(Sign $sign)
	{
		$finder = function ($key, ProductSign $signConn) use ($sign) {
			return $sign->id === $signConn->sign->id;
		};
		return $this->signs->exists($finder);
	}

}
