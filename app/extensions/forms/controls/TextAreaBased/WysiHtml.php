<?php

namespace App\Forms\Controls\TextAreaBased;

use Nette\Forms\Controls\TextArea;

class WysiHtml extends TextArea
{

	public function __construct($label = NULL, $rows = NULL)
	{
		parent::__construct($label);
		$this->control->class = ['wysihtml5'];
		if ($rows) {
			$this->control->rows = $rows;
		}
	}

}
