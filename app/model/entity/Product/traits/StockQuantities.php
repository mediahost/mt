<?php

namespace App\Model\Entity\Traits;


/**
 * @property int $quantity
 * @property int $lock
 * @property-read int $inStore
 */
trait StockQuantities
{
	
	/** @ORM\Column(type="integer") */
	protected $quantity = 0;
	
	/** @ORM\Column(type="integer") */
	private $locked = 0;
	
	/** @ORM\Column(type="integer") */
	private $inStore;

	public function setQuantity($quantity)
	{
		$this->quantity = $quantity > 1 ? $quantity : 0;
		$this->actualizeInStore();
		
		return $this;
	}

	public function setLock($lock)
	{
		$this->locked = $lock > 1 ? $lock : 0;
		$this->actualizeInStore();
		
		return $this;
	}

	public function getLock()
	{
		return $this->locked;
	}

	public function addLock($lock = 1)
	{
		return $this->setLock($this->locked + $lock);
	}

	public function removeLock($lock = 1)
	{
		return $this->setLock($this->locked - $lock);
	}
	
	protected function actualizeInStore()
	{
		$inStore = $this->quantity - $this->locked;
		$this->inStore = $inStore > 1 ? $inStore : 0;
	}

	public function getInStore()
	{
		return $this->inStore;
	}

}
