<?php

namespace App\Components;

use App\Extensions\Settings\Model\Service\LanguageService;
use App\Extensions\Settings\Model\Service\PasswordService;
use Exception;
use h4kuna\Exchange\Exchange;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\UI;
use Nette\Localization\ITranslator;

abstract class BaseControl extends UI\Control
{
	
	const DEFAULT_TEMPLATE = 'default';

	/** @var EntityManager @inject */
	public $em;

	/** @var LanguageService @inject */
	public $languageService;

	/** @var PasswordService @inject */
	public $passwordService;

	/** @var ITranslator @inject */
	public $translator;

	/** @var Exchange @inject */
	public $exchange;
	
	/** @var string */
	private $templateFile = self::DEFAULT_TEMPLATE;
	
	/** @var bool */
	protected $isAjax = FALSE;
	
	/** @var bool */
	protected $isSendOnChange = FALSE;

	/** @var string */
	protected $lang;
	
	public function __construct()
	{
		parent::__construct();
	}
	
	protected function setTemplateFile($name)
	{
		$this->templateFile = $name;
		return $this;
	}
	
	/**
	 * Set ajax for form
	 */
	public function setAjax($isAjax = TRUE, $sendOnChange = TRUE)
	{
		$this->isAjax = $isAjax;
		$this->isSendOnChange = $sendOnChange;
		return $this;
	}

	/**
	 * Set actual language for form
	 */
	public function setLang($lang)
	{
		$this->lang = $lang;
		return $this;
	}

	public function getTemplate()
	{
		$template = parent::getTemplate();
		$template->setTranslator($this->translator);
		return $template;
	}

	public function render()
	{
		$dir = dirname($this->getReflection()->getFileName());

		$template = $this->getTemplate();
		$template->setFile($dir . '/' . $this->templateFile . '.latte');
		$template->render();
	}

}

class BaseControlException extends Exception
{

}
