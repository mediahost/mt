<?php

namespace App\Extensions\Grido;

use App\Extensions\Grido\Columns\Boolean;
use App\Extensions\Grido\Columns\Image;
use Grido\Grid;

/**
 * Base of grid.
 */
class BaseGrid extends Grid
{

	const THEME_METRONIC = 'metronic';

	/** @var string */
	private $templateFile;

	/** @var string */
	private $actionWidth;

	/**
	 * Custom condition callback for filter birthday.
	 * @param string $value
	 * @return array|NULL
	 */
	public function birthdayFilterCondition($value)
	{
		$date = explode('.', $value);
		foreach ($date as &$val) {
			$val = (int) $val;
		}

		return count($date) == 3 ? ['birthday', '= ?', "{$date[2]}-{$date[1]}-{$date[0]}"] : NULL;
	}

	public function addColumnNumber($name, $label, $decimals = 0, $decPoint = ',', $thousandsSep = '')
	{
		return parent::addColumnNumber($name, $label, $decimals, $decPoint, $thousandsSep);
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return Boolean
	 */
	public function addColumnBoolean($name, $label, $width = '2%')
	{
		$column = new Boolean($this, $name, $label);

		$header = $column->headerPrototype;
		$header->style['width'] = $width;
		$header->class[] = 'center';

		return $column;
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @return Boolean
	 */
	public function addColumnImage($name, $label, $sizeX = NULL, $sizeY = NULL)
	{
		$column = new Image($this, $name, $label);

		$column->setDisableExport();
		if ($sizeX && $sizeY) {
			$column->setSize($sizeX, $sizeY);
		}

		$header = $column->headerPrototype;
		$header->style['width'] = '2%';

		return $column;
	}

	public function setTheme($theme = self::THEME_METRONIC)
	{
		switch ($theme) {
			case self::THEME_METRONIC:
			default:
				$this->templateFile = self::THEME_METRONIC;
				$this->getTablePrototype()->class[] = 'table-bordered no-footer';
				break;
		}
	}

	public function setActionWidth($width)
	{
		$this->actionWidth = $width;
		return $this;
	}

	public function render()
	{
		if ($this->templateFile) {
			$this->setTemplateFile(__DIR__ . '/Themes/' . $this->templateFile . '.latte');
		}
		$this->template->actionWidth = $this->actionWidth;
		parent::render();
	}

}
