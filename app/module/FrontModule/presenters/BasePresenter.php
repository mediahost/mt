<?php

namespace App\FrontModule\Presenters;

abstract class BasePresenter extends \App\BaseModule\Presenters\BasePresenter
{
	
	protected function setDemoLayout()
	{
		$this->setLayout('demo');
		$this->template->pacePluginOff = TRUE;
	}

	/** @return \WebLoader\Nette\CssLoader */
	protected function createComponentCssDemo()
	{
		$css = $this->webLoader->createCssLoader('demo')
				->setMedia('screen,projection,tv');
		return $css;
	}

}
