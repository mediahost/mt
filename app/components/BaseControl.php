<?php

namespace App\Components;

use App\Extensions\Settings\SettingsStorage;
use App\Model\Entity\Price;
use App\Model\Entity\Stock;
use App\Model\Facade\ShopFacade;
use Exception;
use h4kuna\Exchange\Exchange;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Translation\Translator;
use Nette\Application\UI;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Latte\Engine;
use Nette\Utils\Strings;

abstract class BaseControl extends UI\Control
{

	const DEFAULT_TEMPLATE = 'default';

	/** @var EntityManager @inject */
	public $em;

	/** @var SettingsStorage @inject */
	public $settings;

	/** @var Translator @inject */
	public $translator;

	/** @var Exchange @inject */
	public $exchange;

	/** @var ShopFacade @inject */
	public $shopFacade;

	/** @var string */
	protected $templateFile = self::DEFAULT_TEMPLATE;

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

	protected function change($value, $currency)
	{
		$value = Price::strToFloat($value);
		switch (Strings::upper($currency)) {
			case 'EUR':
			default:
				return $value;
			case 'CZK':
				return $value * Stock::RECALCULATE_RATE_CZK;
			case 'PLN':
				return $value * Stock::RECALCULATE_RATE_PLN;
		}
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

	public function getTemplate()
	{
		$template = parent::getTemplate();
		$template->setTranslator($this->translator);
		return $template;
	}

	public function templateColumnRenderer($file, $item)
	{
		$engine = new Engine();
		$template = new Template($engine);
		$template->setTranslator($this->translator)
			->setFile($file)
			->setParameters([
				'item' => $item,
			]);
		return $template;
	}

	public function render()
	{
		$dir = dirname($this->getReflection()->getFileName());

		$template = $this->getTemplate();
		$template->setFile($dir . DIRECTORY_SEPARATOR . $this->templateFile . '.latte');
		$template->render();
	}

}

class BaseControlException extends Exception
{

}
