<?php

namespace App\Extensions\Grido\Columns;

use App\Model\Entity\Image as ImageEntity;
use Grido\Components\Columns\Text;
use Nette\Utils\Html;

/**
 * Image column
 */
class Image extends Text
{

	private $sizeX = 30;
	private $sizeY = 30;

	public function getCellPrototype($row = NULL)
	{
		$cell = parent::getCellPrototype($row = NULL);
		$cell->class[] = 'text-center';
		$cell->style['padding'] = '2px;';

		return $cell;
	}

	public function setSize($x, $y)
	{
		$this->sizeX = $x;
		$this->sizeY = $y;
		return $this;
	}

	/**
	 * @param $value
	 * @return Html
	 */
	protected function formatValue($value)
	{
		$filename = ImageEntity::returnSizedFilename($value, $this->sizeX, $this->sizeY);
		return Html::el('img', [
							'src' => '/foto/' . $filename,
						]);
	}

}
