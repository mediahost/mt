<?php

namespace App\Mail\Messages;

use App\Extensions\Settings\SettingsStorage;
use Kdyby\Translation\Translator;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\ITemplate;
use Nette\Application\UI\ITemplateFactory;
use Nette\Http\Request;
use Nette\Mail\IMailer;
use Nette\Mail\Message;

abstract class BaseMessage extends Message
{

	/** @var IMailer @inject */
	public $mailer;

	/** @var ITemplateFactory @inject */
	public $templateFactory;

	/** @var LinkGenerator @inject */
	public $linkGenerator;

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

	/** @var ITemplate */
	protected $template;

	/** @return string */
	protected function getPath()
	{
		$dir = dirname($this->getReflection()->getFileName());
		$name = $this->reflection->getShortName();
		return $dir . DIRECTORY_SEPARATOR . $name . '.latte';
	}

	protected function build()
	{
		$this->params += [
			'settings' => $this->settings->modules->newsletter,
			'pageInfo' => $this->settings->pageInfo,
			'mail' => $this,
			'colon' => '',
			'locale' => $this->translator->locale,
		];

		$template = $this->templateFactory->createTemplate();
		$template->setTranslator($this->translator)
						->setFile($this->getPath())
						->setParameters($this->params)
				->_control = $this->linkGenerator;

		$this->setHtmlBody($template);

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
