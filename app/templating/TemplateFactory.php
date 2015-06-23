<?php

namespace App\Templating;

use App\ExchangeHelper;
use Latte\Engine;
use Latte\Macros\MacroSet;
use Nette\Application\UI\Control;
use Nette\Bridges\ApplicationLatte\ILatteFactory;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Bridges\ApplicationLatte\TemplateFactory as ParentTemplateFactory;
use Nette\Caching\IStorage;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Security\User;

class TemplateFactory extends ParentTemplateFactory
{

	/** @var ExchangeHelper */
	private $exchange;

	public function __construct(ExchangeHelper $exchange, ILatteFactory $latteFactory, IRequest $httpRequest = NULL, IResponse $httpResponse = NULL, User $user = NULL, IStorage $cacheStorage = NULL)
	{
		$this->exchange = $exchange;
		parent::__construct($latteFactory, $httpRequest, $httpResponse, $user, $cacheStorage);
	}

	/**
	 * @param Control $control
	 * @return Template
	 */
	public function createTemplate(Control $control = NULL)
	{
		$template = parent::createTemplate($control);
		$latte = $template->getLatte();
		$latte->onCompile[] = $this->addMacros;
		$latte->addFilter('concat', ['App\Helpers', 'concatArray']);
		$latte->addFilter('size', ['App\Model\Entity\Image', 'returnSizedFilename']);
		$latte->addFilter('currency', [$this->exchange, 'format']);
		$latte->addFilter('currencyVat', [$this->exchange, 'formatVat']);
		$latte->addFilter('change', [$this->exchange, 'change']);
		$latte->addFilter('changeVat', [$this->exchange, 'changeVat']);
		return $template;
	}

	public function addMacros(Engine $latte)
	{
		$set = new MacroSet($latte->getCompiler());
		$set->addMacro('ifCurrentIn', $this->ifCurrentInBegin, 'endif; unset($_c);');
		$set->addMacro('scache', '?>?<?php echo strtotime(date(\'Y-m-d hh \'));');
	}

	public function ifCurrentInBegin($node, $writer)
	{
		return $writer->write('foreach (%node.array as $l) { if ($_presenter->isLinkCurrent($l)) { $_c = true; break; }} if (isset($_c)): ');
	}

}
