<?php

namespace App\CronModule\Presenters;

use App\BaseModule\Presenters\BasePresenter as BaseBasePresenter;
use Nette\Application\ForbiddenRequestException;
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
	
	protected function startup()
	{
		parent::startup();
		$ip = $this->request->getRemoteAddress();
		if (!$this->settings->modules->cron->enabled) {
			throw new ForbiddenRequestException('Cron module is not allowed.');
		}
		if (!in_array($this->request->getRemoteAddress(), (array) $this->settings->modules->cron->allowedIps)) {
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
