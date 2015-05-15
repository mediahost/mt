<?php

namespace App\FotoModule\Presenters;

use App\Extensions\Foto;
use Nette\Application\UI\Presenter;

class FotoPresenter extends Presenter
{

	/** @var Foto @inject */
	public $service;

	public function actionDefault($size = NULL, $name = NULL)
	{
		$this->service->display($name, $size);
		$this->terminate(); // stop redirecting this URL (htaccess redirecting)
	}

}
