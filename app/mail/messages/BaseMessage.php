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
use Nette\Utils\ArrayHash;

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

	/** @var string */
	protected $oldLocale;

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
		$currency = $this->exchange[$this->exchange->getWeb()];
		$this->params += [
			'companyInfo' => $this->settings->companyInfo,
			'pageInfo' => $this->settings->pageInfo,
			'mail' => $this,
			'colon' => '',
			'locale' => $this->translator->getLocale(),
			'exchange' => $this->exchange,
			'currencySymbol' => $currency->getFormat()->getSymbol(),
			'basePath' => $this->httpRequest->getUrl()->getBaseUrl(),
		];

		$template = $this->templateFactory->createTemplate();
		$template->setTranslator($this->translator)
						->setFile($this->getPath())
						->setParameters($this->params)
				->_control = $this->linkGenerator;

		$this->setHtmlBody($template);

		return parent::build();
	}

	protected function changeLocale($locale)
	{
		$this->oldLocale = $this->translator->getLocale();
		$this->translator->setLocale($locale);
	}

	protected function changeCurrency($currency, $rate = NULL)
	{
		$this->exchange->setWeb($currency);
		if ($rate) {
			$rateRelated = ExchangeHelper::getRelatedRate($rate, $this->exchange[$currency]);
			$this->exchange->addRate($currency, $rateRelated);
		}
	}

	protected function beforeSend()
	{
		
	}

	protected function afterSend()
	{
		if ($this->oldLocale) {
			$this->translator->setLocale($this->oldLocale);
		}
	}

	public function addTo($email, $name = NULL)
	{
		if (is_array($email) || $email instanceof ArrayHash) {
			foreach ($email as $mail) {
				parent::addTo($mail);
			}
		} else {
			parent::addTo($email, $name);
		}
		return $this;
	}

	public function send()
	{
		$this->beforeSend();
		$this->mailer->send($this);
		$this->afterSend();
	}

	// <editor-fold defaultstate="collapsed" desc="setters">

	public function addParameter($paramName, $value)
	{
		$this->params[$paramName] = $value;
		return $this;
	}

	public function setNewsletter($unsubscribeLink = NULL)
	{
		$this->isNewsletter = TRUE;
		$this->unsubscribeLink = $unsubscribeLink;

		return $this;
	}

	public function setOrder(Order $order)
	{
		$this->order = $order;
		$this->addParameter('order', $order);
		$this->changeLocale($order->locale);
		$this->changeCurrency($order->currency, $order->rate);

		return $this;
	}

	// </editor-fold>
}
