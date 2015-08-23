<?php

namespace App\Mail\Messages;

use App\Extensions\Settings\SettingsStorage;
use Kdyby\Translation\Translator;
use Latte\Engine;
use Nette\Http\Request;
use Nette\Mail\IMailer;
use Nette\Mail\Message;

abstract class BaseMessage extends Message
{

	/** @var IMailer @inject */
	public $mailer;

	/** @var SettingsStorage @inject */
	public $settings;

	/** @var Request @inject */
	public $httpRequest;

	/** @var Translator @inject */
	public $translator;

	/** @var array */
	protected $params = [];

	/** @var bool */
	protected $isNewsletter = FALSE;

	/** @var string */
	protected $unsubscribeLink;

	/**
	 * @return string
	 */
	protected function getPath()
	{
		$dir = dirname($this->getReflection()->getFileName());
		$name = $this->reflection->getShortName();
		return $dir . DIRECTORY_SEPARATOR . $name . '.latte';
	}

	protected function build()
	{
		$this->params['hostUrl'] = $this->httpRequest->url->hostUrl;
		$this->params['basePath'] = $this->httpRequest->url->basePath;
		$this->params['pageInfo'] = $this->settings->pageInfo;
		$this->params['isNewsletter'] = $this->isNewsletter;
		$this->params['unsubscribeLink'] = $this->unsubscribeLink ? $this->unsubscribeLink : $this->params['hostUrl'];
		
		$engine = new Engine;
		$engine->addFilter('translate', $this->translator->translate);
		$this->setHtmlBody($engine->renderToString($this->getPath(), $this->params));
		
		return parent::build();
	}
	
	public function setNewsletter($unsubscribeLink = NULL)
	{
		$this->isNewsletter = TRUE;
		$this->unsubscribeLink = $unsubscribeLink;
	}
	
	public function addParameter($paramName, $value)
	{
		$this->params[$paramName] = $value;
		return $this;
	}
	
	public function send()
	{
		$this->mailer->send($this);
	}

}
