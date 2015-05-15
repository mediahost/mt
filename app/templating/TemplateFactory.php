<?php

namespace App\Templating;

use Latte\Engine;
use Latte\Macros\MacroSet;
use Nette\Application\UI\Control;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Bridges\ApplicationLatte\TemplateFactory as ParentTemplateFactory;

class TemplateFactory extends ParentTemplateFactory
{

	/**
	 * @param Control $control
	 * @return Template
	 */
	public function createTemplate(Control $control = NULL)
	{
		$template = parent::createTemplate($control);
		$latte = $template->getLatte();
		$latte->onCompile[] = $this->addMacros;
		return $template;
	}

	public function addMacros(Engine $latte)
	{
		$set = new MacroSet($latte->getCompiler());
		$set->addMacro('ifCurrentIn', $this->ifCurrentInBegin, 'endif; unset($_c);');
		$set->addMacro('scache', '?>?<?php echo strtotime(date(\'Y-m-d hh \')); ?>"<?php');
	}

	public function ifCurrentInBegin($node, $writer)
	{
		return $writer->write('foreach (%node.array as $l) { if ($_presenter->isLinkCurrent($l)) { $_c = true; break; }} if (isset($_c)): ');
	}

}
