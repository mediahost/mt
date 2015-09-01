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
		$this->quantity = $quantity > 0 ? $quantity : 0;
		$this->actualizeInStore();
		
		return $this;
	}

	public function increaseQuantity($count = 1)
	{
		return $this->setQuantity($this->quantity + $count);
	}

	public function decreaseQuantity($count = 1)
	{
		return $this->setQuantity($this->quantity - $count);
	}

	public function setLock($lock)
	{
		$this->locked = $lock > 0 ? $lock : 0;
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
		$this->inStore = $inStore > 0 ? $inStore : 0;
	}

	public function getInStore()
	{
		return $this->inStore;
	}

}
