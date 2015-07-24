<?php

namespace App\CronModule\Presenters;

use App\BaseModule\Presenters\BasePresenter as BaseBasePresenter;
use Nette\Http\Request;
use Nette\Security\AuthenticationException;

abstract class BasePresenter extends BaseBasePresenter
{

	const STATUS_OK = 'OK';
	const STATUS_ERROR = 'ERROR';

	/** @var Request @inject */
	public $request;

	/** @var string */
	protected $status;

	/** @var string */
	protected $message;
	
	/** TODO: move to module settings */
	private $allowedIps = ['127.0.0.1'];
	
	protected function startup()
	{
		parent::startup();
		$ip = $this->request->getRemoteAddress();
		if (!in_array($this->request->getRemoteAddress(), $this->allowedIps)) {
			throw new AuthenticationException('Your IP (' . $ip . ') is not allowed.');
		}
	}

	protected function beforeRender()
	{
		parent::beforeRender();
		if (!$this->status) {
			$this->status = self::STATUS_ERROR;
			$this->message = 'No action';
		}
		$this->template->status = $this->status;
		$this->template->message = $this->message;
		$this->setView('../status');
	}

}
