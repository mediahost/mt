<?php

namespace App\FrontModule\Presenters;

use App\Extensions\Installer;

class InstallPresenter extends BasePresenter
{

	/** @var array */
	private $messages = [];

	/** @var Installer @inject */
	public $installer;

	protected function startup()
	{
		if ($this->user->loggedIn) {
			$this->user->logout();
		}
		parent::startup();
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
