<?php

namespace App\Extensions\Products\Components;

/**
 * Paginating product list.
 *
 * @property-read int $countEnd
 * @property-read int $countBegin
 */
class Paginator extends \Nette\Utils\Paginator
{

	const DEFAULT_STEP_COUNT = 4;
	const DEFAULT_STEP_RANGE = 3;

	/** @var array */
	protected $steps = array();

	/** @var int */
	protected $countBegin;

	/** @var int */
	protected $countEnd;

	/** @var int */
	private $stepCount = self::DEFAULT_STEP_COUNT;

	/** @var int */
	private $stepRange = self::DEFAULT_STEP_RANGE;

	/**
	 * @param int $stepRange
	 * @return Paginator
	 */
	public function setStepRange($stepRange)
	{
		$this->stepRange = $stepRange;
		return $this;
	}

	/**
	 * @param int $stepCount
	 * @return Paginator
	 */
	public function setStepCount($stepCount)
	{
		$this->stepCount = (int) $stepCount;
		return $this;
	}

	/*	 * ******************************************************************************************* */

	/**
	 * @return array
	 */
	public function getSteps()
	{
		if (!$this->steps) {
			$arr = range(
					max($this->getFirstPage(), $this->getPage() - $this->stepRange)
					, min($this->getLastPage(), $this->getPage() + $this->stepRange)
			);

			$quotient = ($this->getPageCount() - 1) / $this->stepCount;
			$quotient = $quotient < 0 ? 0 : $quotient;

			for ($i = 0; $i <= $this->stepCount; $i++) {
				$arr[] = (int) (round($quotient * $i) + $this->getFirstPage());
			}

			sort($arr);
			$this->steps = array_values(array_unique($arr));
		}

		return $this->steps;
	}

	/**
	 * @return int
	 */
	public function getCountBegin()
	{
		if ($this->countBegin === NULL) {
			$this->countBegin = $this->itemCount > 0 ? $this->offset + 1 : 0;
		}

		return $this->countBegin;
	}

	/**
	 * @return int
	 */
	public function getCountEnd()
	{
		if ($this->countEnd === NULL) {
			$this->countEnd = $this->itemCount > 0 ? min($this->itemCount, $this->getPage() * $this->itemsPerPage) : 0;
		}

		return $this->countEnd;
	}

}
