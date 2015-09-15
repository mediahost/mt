<?php

namespace App\Mail\Messages;

use App\ExchangeHelper;
use App\Extensions\Settings\SettingsStorage;
use App\Model\Entity\Order;
use h4kuna\Exchange\Exchange;
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

	/** @var Exchange @inject */
	public $exchange;

	/** @var array */
	protected $params = [];

	/** @var Order */
	protected $order;

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
			'exchange' => $this->exchange,
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

	protected function beforeSend()
	{
		
	}

	public function send()
	{
		$this->beforeSend();
		$this->mailer->send($this);
	}

	// <editor-fold defaultstate="collapsed" desc="setters">

	public function setOrder(Order $order)
	{
		$this->order = $order;
		$this->addParameter('order', $order);
		$this->translator->setLocale($order->locale);
		$this->exchange->setWeb($order->currency);
		if ($order->rate) {
			$rateRelated = ExchangeHelper::getRelatedRate($order->rate, $this->exchange[$order->currency]);
			$this->exchange->addRate($order->currency, $rateRelated);
		}
	}

	// </editor-fold>
}
