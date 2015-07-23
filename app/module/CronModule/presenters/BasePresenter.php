<?php

namespace App\CronModule\Presenters;

use App\BaseModule\Presenters\BasePresenter as BaseBasePresenter;

abstract class BasePresenter extends BaseBasePresenter
{

	const STATUS_OK = 'OK';
	const STATUS_ERROR = 'ERROR';

	/** @var string */
	protected $status;

	/** @var string */
	protected $message;

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

	/**
	 * Formats view template file names.
	 * @return array
	 */
//	public function formatTemplateFiles()
//	{
//		parent::formatTemplateFiles();
//		$dir = dirname($this->getReflection()->getFileName());
//		$dir = is_dir("$dir/templates") ? $dir : dirname($dir);
//		return array(
//			"$dir/templates/$this->view.latte",
//		);
//	}
}
