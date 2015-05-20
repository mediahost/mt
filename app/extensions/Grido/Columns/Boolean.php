<?php

namespace App\Extensions\Grido\Columns;

use Grido\Extensions\Columns\Text;
use Nette\Utils\Html;

/**
 * Boolean column
 */
class Boolean extends Text
{
    public function getCellPrototype($row = NULL)
    {
        $cell = parent::getCellPrototype($row = NULL);
        $cell->class[] = 'center';

        return $cell;
    }

    /**
     * @param $value
     * @return Html
     */
    protected function formatValue($value)
    {
        $icon = $value ? 'ok' : 'remove';
        return Html::el('i')->class("glyphicon glyphicon-$icon icon-$icon");
    }
}
