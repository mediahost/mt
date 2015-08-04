<?php

namespace App;

use Kdyby\Translation\Translator;

/**
 * String with tags to replace
 */
class TaggedString
{

	/** @var string */
	private $taggedString;

	/** @var array */
	private $replacements = [];

	/** @var int */
	private $form;

	/** @var Translator */
	private $translator;

	public function __construct($taggedString, $replacement = NULL, $_ = null)
	{
		$this->setTaggedString($taggedString);
		$replacements = func_get_args();
		if (func_num_args() > 2) {
			array_shift($replacements);
			$this->setReplacements($replacements);
		} else {
			$this->setReplacements([$replacement]);
		}
	}
	
	public function setTaggedString($string)
	{
		$this->taggedString = $string;
		return $this;
	}
	
	public function setReplacements(array $replacements)
	{
		$this->replacements = $replacements;
		return $this;
	}
	
	public function setForm($form)
	{
		$this->form = $form;
		return $this;
	}
	
	public function setTranslator(Translator $translator)
	{
		$this->translator = $translator;
		return $this;
	}

	public function __toString()
	{
		$string = $this->translator ? $this->translator->translate($this->taggedString, $this->form) : $this->taggedString;
		return vsprintf($string, $this->replacements);
	}

}
