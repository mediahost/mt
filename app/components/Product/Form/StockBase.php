<?php

namespace App\Components\Product\Form;

use App\Components\BaseControl;
use App\Components\BaseControlException;
use App\Model\Entity\Product;
use App\Model\Entity\Stock;

abstract class StockBase extends BaseControl
{

	/** @var Stock */
	protected $stock;

	// <editor-fold desc="events">

	/** @var array */
	public $onAfterSave = [];

	// </editor-fold>

	protected function checkEntityExistsBeforeRender()
	{
		if (!$this->stock) {
			throw new BaseControlException('Use setStock(\App\Model\Entity\Stock) before render');
		}
	}

	public function setStock(Stock $stock)
	{
		$this->stock = $stock;
		if ($this->stock->isNew()) {
			$this->stock->product = new Product();
		}
		return $this;
	}

}
