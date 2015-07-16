<?php

namespace App\AjaxModule\Presenters;

use App\BaseModule\Presenters\BasePresenter as BaseBasePresenter;
use Nette\Http\Request;
use Nette\Utils\Json;

abstract class BasePresenter extends BaseBasePresenter
{

	/** @var Request @inject */
	public $request;

	/** @var array */
	private $data = [];

	protected function startup()
	{
		header('content-type: application/json; charset=utf-8');
		parent::startup();
	}

	protected function beforeRender()
	{
		parent::beforeRender();
		$callback = $this->request->getQuery('callback');
		if ($callback) {
			$this->template->data = $callback . '(' . Json::encode($this->data) . ')';
		} else {
			$this->template->data = Json::encode($this->data);
		}
		$this->setView('data');
	}
	
	/**
	 * Formats view template file names.
	 * @return array
	 */
	public function formatTemplateFiles()
	{
		$dir = dirname($this->getReflection()->getFileName());
		$dir = is_dir("$dir/templates") ? $dir : dirname($dir);
		return array(
			"$dir/templates/$this->view.latte",
		);
	}

	protected function addData($key, $value, $remove = FALSE)
	{
		if (!array_key_exists('success', $this->data) || $remove) {
			$this->data['success'] = [];
		}
		
		if ($key === NULL) {
			$this->data['success'][] = $value;
		} else {
			$this->data['success'][$key] = $value;
		}
		
		return $this;
	}

	protected function addRawData($key, $value)
	{
		if ($key === NULL) {
			$this->data[] = $value;
		} else {
			$this->data[$key] = $value;
		}
		
		return $this;
	}

	protected function setError($message)
	{
		$this->data['error'] = $message;
		return $this;
	}

}
