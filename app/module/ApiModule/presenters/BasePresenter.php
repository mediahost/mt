<?php

namespace App\ApiModule\Presenters;

use Drahak\Restful\Application\UI\ResourcePresenter;
use Drahak\Restful\IResource;
use Nette\Application\Responses\TextResponse;

abstract class BasePresenter extends ResourcePresenter
{

	protected $typeMap = [
		'json' => IResource::JSON,
		'xml' => IResource::XML,
	];
	
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
