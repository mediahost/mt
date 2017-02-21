<?php

namespace App\FrontModule\Presenters;

use Kdyby\Translation\Translator;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Presenter;
use Tracy\Debugger;

class ErrorPresenter extends Presenter
{

	/** @var Translator @inject */
	public $translator;

	protected function beforeRender()
	{
		$this->template->locale = $this->translator->getLocale();
	}

	public function renderDefault($exception)
	{
		$this->setLayout('layout.error');
		if ($exception instanceof BadRequestException) {
			$code = $exception->getCode();
			// load template 403.latte or 404.latte or ... 4xx.latte
			$this->setView(in_array($code, array(403, 404, 405, 410, 500)) ? $code : '4xx');
			// log to access.log
			Debugger::log("HTTP code $code: {$exception->getMessage()} in {$exception->getFile()}:{$exception->getLine()}", 'access');
		} else {
			$this->setView('500'); // load template 500.latte
			Debugger::log($exception, Debugger::ERROR); // and log exception
		}

		if ($this->isAjax()) { // AJAX request? Note this error in payload.
			$this->payload->error = TRUE;
			$this->terminate();
		} else {
			$this->template->isErrorPresenter = TRUE;
		}
	}

}
