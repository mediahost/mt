<?php

namespace App\ApiModule\Presenters;

use App\Extensions\Settings\SettingsStorage;
use App\Model\Facade\ShopFacade;
use Drahak\Restful\Application\UI\ResourcePresenter;
use Drahak\Restful\IResource;
use h4kuna\Exchange\Exchange;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Translation\Translator;
use Nette\Application\Responses\TextResponse;

abstract class BasePresenter extends ResourcePresenter
{

	protected $typeMap = [
		'json' => IResource::JSON,
		'xml' => IResource::XML,
	];

	/** @var EntityManager @inject */
	public $em;

	/** @var Exchange @inject */
	public $exchange;

	/** @var Translator @inject */
	public $translator;

	/** @var SettingsStorage @inject */
	public $settings;

	/** @var ShopFacade @inject */
	public $shopFacade;

	public function setView($view = NULL)
	{
		foreach ($this->resource as $key => $value) {
			$this->template->$key = $value;
		}

		$name = $this->getName();
		$presenter = substr($name, strrpos(':' . $name, ':'));
		$action = $view ? $view : $this->action;
		$templatePath = __DIR__ . "/../templates/{$presenter}/{$action}.latte";
		$this->template->setFile(realpath($templatePath));

		$this->sendResponse(new TextResponse($this->template));
	}

}
