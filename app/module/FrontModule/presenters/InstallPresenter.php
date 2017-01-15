<?php

namespace App\FrontModule\Presenters;

use App\Extensions\Installer;
use Nette\Application\UI\Presenter;

class InstallPresenter extends BasePresenter
{

	/** @var array */
	private $messages = [];

	/** @var Installer @inject */
	public $installer;

	protected function startup()
	{
		ini_set('max_execution_time', 120);
		if ($this->user->loggedIn) {
			$this->user->logout();
		}
		Presenter::startup();
	}
	
	protected function beforeRender()
	{
		Presenter::beforeRender();
	}

	public function actionDefault()
	{
		$this->messages = $this->installer->install();
	}

	public function renderDefault($printHtml = TRUE)
	{
		$this->template->html = (bool) $printHtml;
		$this->template->messages = $this->messages;
	}

}
