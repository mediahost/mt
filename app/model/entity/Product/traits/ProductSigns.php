<?php

namespace App\Model\Entity\Traits;

use App\Model\Entity\Sign;

trait ProductSigns
{

	/** @ORM\ManyToMany(targetEntity="Sign", inversedBy="products") */
	protected $signs;
	
	protected function setSigns(array $signs)
	{
		$removeIdles = function ($key, Sign $sign) use ($signs) {
			if (!in_array($sign, $signs, TRUE)) {
				$this->removeSign($sign);
			}
			return TRUE;
		};
		$this->signs->forAll($removeIdles);
		foreach ($signs as $sign) {
			$this->addSign($sign);
		}
		return $this;
	}

	public function addSign(Sign $sign)
	{
		if (!$this->signs->contains($sign)) {
			$this->signs->add($sign);
		}
		return $this;
	}

	public function removeSign(Sign $sign)
	{
		return $this->signs->removeElement($sign);
	}

	public function hasSign(Sign $sign)
	{
		return $this->signs->contains($sign);
	}

}
