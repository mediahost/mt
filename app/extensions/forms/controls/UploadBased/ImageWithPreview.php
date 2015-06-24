<?php

namespace App\Forms\Controls\UploadBased;

use Nette\Forms\Controls\UploadControl;
use Nette\Http\FileUpload;
use Nette\Utils\Html;

/**
 * Needs plugin - Bootstrap: fileinput.js v3.1.3
 * http://jasny.github.com/bootstrap/javascript/#fileinput
 */
class UploadImageWithPreview extends UploadControl
{

	/** @var string */
	private $src;

	/** @var string */
	private $alt = '';

	/** @var int */
	private $width = 200;

	/** @var int */
	private $height = 150;

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

	/** Generates control's HTML element. */
	public function getControl($caption = NULL)
	{
		$input = parent::getControl($caption);

		$selector = Html::el('span', ['class' => 'btn default btn-file'])
				->add(Html::el('span', ['class' => 'fileinput-new'])->setText($this->translator->translate('Select image')))
				->add(Html::el('span', ['class' => 'fileinput-exists'])->setText($this->translator->translate('Change')))
				->add($input);
		$removeLink = Html::el('a', [
					'class' => 'btn red fileinput-exists',
					'data-dismiss' => 'fileinput',
				])
				->href('#')
				->setText($this->translator->translate('Remove'));

		$existed = Html::el('div', ['class' => 'fileinput-new thumbnail'])
				->add(Html::el('img', [
					'src' => $this->src,
					'alt' => $this->alt,
		]));
		$preview = Html::el('div', [
					'class' => 'fileinput-preview fileinput-exists thumbnail',
					'style' => 'max-width: ' . $this->width . 'px; max-height: ' . $this->height . 'px;',
		]);
		$buttons = Html::el('div')
				->add($selector)
				->add(Html::el('span')->setHtml('&nbsp;'))
				->add($removeLink);

		$control = Html::el('div', [
					'class' => 'fileinput fileinput-new',
					'data-provides' => 'fileinput',
		]);
		if ($this->src) {
			$control->add($existed);
		}
		return $control
						->add($preview)
						->add($buttons);
	}

	public function isFilled()
	{
		$isImage = $this->value instanceof FileUpload ? $this->value->isImage() : (bool) $this->value;
		return parent::isFilled() && $isImage;
	}

}
