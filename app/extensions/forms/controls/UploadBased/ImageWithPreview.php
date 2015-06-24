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

	/** @var string */
	private $selectText = 'Select image';

	/** @var string */
	private $changeText = 'Change';

	/** @var string */
	private $removeText = 'Remove';

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

	public function setTexting($select = NULL, $change = NULL, $remove = NULL)
	{
		if ($select) {
			$this->selectText = $select;
		}
		if ($change) {
			$this->changeText = $change;
		}
		if ($remove) {
			$this->removeText = $remove;
		}
		return $this;
	}

	/** Generates control's HTML element. */
	public function getControl($caption = NULL)
	{
		$control = Html::el('div', [
					'class' => 'fileinput fileinput-new',
					'data-provides' => 'fileinput',
		]);
		if ($this->src) {
			$control->add($this->getExisted());
		}
		return $control
						->add($this->getPreview())
						->add($this->getButtons($caption));
	}

	private function getExisted()
	{
		return Html::el('div', ['class' => 'fileinput-new thumbnail'])
						->add(Html::el('img', [
									'src' => $this->src,
									'alt' => $this->alt,
		]));
	}

	private function getPreview()
	{
		return Html::el('div', [
					'class' => 'fileinput-preview fileinput-exists thumbnail',
					'style' => 'max-width: ' . $this->width . 'px; max-height: ' . $this->height . 'px;',
		]);
	}

	private function getButtons($caption)
	{
		$input = parent::getControl($caption);

		$selector = Html::el('span', ['class' => 'btn default btn-file'])
				->add(Html::el('span', ['class' => 'fileinput-new'])->setText($this->translator->translate($this->selectText)))
				->add(Html::el('span', ['class' => 'fileinput-exists'])->setText($this->translator->translate($this->changeText)))
				->add($input);

		$removeLink = Html::el('a', [
					'class' => 'btn red fileinput-exists',
					'data-dismiss' => 'fileinput',
				])
				->href('#')
				->setText($this->translator->translate($this->removeText));

		return Html::el('div')
						->add($selector)
						->add(Html::el('span')->setHtml('&nbsp;'))
						->add($removeLink);
	}

	public function isFilled()
	{
		$isImage = $this->value instanceof FileUpload ? $this->value->isImage() : (bool) $this->value;
		return parent::isFilled() && $isImage;
	}

}
