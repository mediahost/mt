<?php

namespace App\Forms\Controls\UploadBased;

use Nette\Forms\Controls\UploadControl;
use Nette\Utils\Html;

/**
 * Needs plugin - Bootstrap: fileinput.js v3.1.3
 * http://jasny.github.com/bootstrap/javascript/#fileinput
 */
class UploadFile extends UploadControl
{

	/** @var string */
	protected $selectText = 'Select file';

	/** @var string */
	protected $changeText = 'Change';

	/** @var string */
	protected $removeText = 'Remove';

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

	protected function getControlDiv()
	{
		return Html::el('div', [
					'class' => 'fileinput fileinput-new',
					'data-provides' => 'fileinput',
		]);
	}

	/** Generates control's HTML element. */
	public function getControl($caption = NULL)
	{
		$input = $this->getInput($caption);
		$group = Html::el('div', [
					'class' => 'input-group input-large',
		]);
		$group->add($this->getFilenameItem())
				->add($this->getButtons($input, NULL, 'input-group-addon'));

		return $this->getControlDiv()
						->add($group);
	}

	protected function getInput($caption)
	{
		return parent::getControl($caption);
	}

	protected function getButtons($input, $elName = NULL, $itemClass = NULL)
	{
		$selector = Html::el('span', ['class' => 'btn default btn-file ' . $itemClass])
				->add(Html::el('span', ['class' => 'fileinput-new'])->setText($this->translator->translate($this->selectText)))
				->add(Html::el('span', ['class' => 'fileinput-exists'])->setText($this->translator->translate($this->changeText)))
				->add($input);

		$removeLink = Html::el('a', [
					'class' => 'btn red fileinput-exists ' . $itemClass,
					'data-dismiss' => 'fileinput',
				])
				->href('#')
				->setText($this->translator->translate($this->removeText));

		return Html::el($elName)
						->add($selector)
						->add($removeLink);
	}

	protected function getFilenameItem()
	{
		return Html::el('div', [
							'class' => 'form-control uneditable-input',
							'data-trigger' => 'fileinput',
						])
						->add(Html::el('i class="fa fa-file fileinput-exists"'))
						->add('&nbsp;')
						->add(Html::el('span class="fileinput-filename"'));
	}

}
