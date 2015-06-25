<?php

namespace App\Forms\Controls\UploadBased;

use Nette\Http\FileUpload;
use Nette\Utils\Html;

/**
 * Needs plugin - Bootstrap: fileinput.js v3.1.3
 * http://jasny.github.com/bootstrap/javascript/#fileinput
 */
class UploadImageWithPreview extends UploadFile
{

	/** @var string */
	protected $src;

	/** @var string */
	protected $alt = '';

	/** @var int */
	protected $width = 200;

	/** @var int */
	protected $height = 150;

	public function __construct($label = NULL, $multiple = FALSE)
	{
		parent::__construct($label, $multiple);
		$this->selectText = 'Select image';
	}

	public function setPreview($src, $alt)
	{
		$this->src = $src;
		$this->alt = $alt;
		return $this;
	}

	public function setSize($width, $height)
	{
		$this->width = $width;
		$this->height = $height;
		return $this;
	}

	public function isFilled()
	{
		$isImage = $this->value instanceof FileUpload ? $this->value->isImage() : (bool) $this->value;
		return parent::isFilled() && $isImage;
	}

	/** Generates control's HTML element. */
	public function getControl($caption = NULL)
	{
		$control = $this->getControlDiv();
		$input = $this->getInput($caption);

		if ($this->src) {
			$control->add($this->getExisted());
		}
		$control->add($this->getPreview())
				->add($this->getButtons($input, 'div'));

		return $control;
	}

	protected function getExisted()
	{
		return Html::el('div', ['class' => 'fileinput-new thumbnail'])
						->add(Html::el('img', [
									'src' => $this->src,
									'alt' => $this->alt,
		]));
	}

	protected function getPreview()
	{
		return Html::el('div', [
					'class' => 'fileinput-preview fileinput-exists thumbnail',
					'style' => 'max-width: ' . $this->width . 'px; max-height: ' . $this->height . 'px;',
		]);
	}

}
