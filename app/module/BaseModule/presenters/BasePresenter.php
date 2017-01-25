<?php

namespace App\BaseModule\Presenters;

use App\ExchangeHelper;
use App\Extensions\Settings\SettingsStorage;
use App\Model\Entity\Rate;
use App\Model\Entity\ShopVariant;
use App\Model\Facade\BasketFacade;
use App\Model\Facade\CategoryFacade;
use App\Model\Facade\GroupFacade;
use App\Model\Facade\OrderFacade;
use App\Model\Facade\ParameterFacade;
use App\Model\Facade\ProducerFacade;
use App\Model\Facade\ShopFacade;
use App\Model\Facade\StockFacade;
use App\Model\Facade\UserFacade;
use h4kuna\Exchange\Exchange;
use h4kuna\Exchange\ExchangeException;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Translation\Translator;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Presenter;
use Nette\Caching\IStorage;
use Tracy\Debugger;
use WebLoader\Nette\CssLoader;
use WebLoader\Nette\LoaderFactory;

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Presenter
{

	/** @var ShopVariant */
	protected $shopVariant;

	/** @persistent */
	public $locale;

	/** @persistent */
	public $backlink = '';

	// <editor-fold desc="injects">

	/** @var LoaderFactory @inject */
	public $webLoader;

	/** @var Exchange @inject */
	public $exchange;

	/** @var Translator @inject */
	public $translator;

	/** @var SettingsStorage @inject */
	public $settings;

	/** @var EntityManager @inject */
	public $em;

	/** @var IStorage @inject */
	public $cacheStorage;

	/** @var UserFacade @inject */
	public $userFacade;

	/** @var StockFacade @inject */
	public $stockFacade;

	/** @var ParameterFacade @inject */
	public $parameterFacade;

	/** @var GroupFacade @inject */
	public $groupFacade;

	/** @var ProducerFacade @inject */
	public $producerFacade;

	/** @var CategoryFacade @inject */
	public $categoryFacade;

	/** @var BasketFacade @inject */
	public $basketFacade;

	/** @var OrderFacade @inject */
	public $orderFacade;

	/** @var ShopFacade @inject */
	public $shopFacade;

	/** @var int */
	protected $priceLevel = NULL;

	// </editor-fold>

	protected function startup()
	{
		parent::startup();
		$this->setLocale();
		$this->loadPriceLevel();
		$this->loadShop();
	}

	protected function beforeRender()
	{
		$this->template->setTranslator($this->translator);
		$this->template->lang = $this->translator->getLocale(); // TODO: remove lang from latte
		$this->template->locale = $this->translator->getLocale();
		$this->template->defaultLocale = $this->translator->getDefaultLocale();
		$this->template->allowedLanguages = $this->translator->getAvailableLocales();

		$this->template->designSettings = $this->settings->design; // TODO: remove design settings
		$this->template->pageInfo = $this->settings->pageInfo;
		$this->template->shop = $this->shopVariant->shop;
		$this->template->shopVariant = $this->shopVariant;

		$currency = $this->exchange[$this->exchange->getWeb()];
		$this->template->exchange = $this->exchange;
		$this->template->currency = $currency;
		$this->template->currencySymbol = $currency->getFormat()->getSymbol();

		$this->template->isDevelopment = Debugger::isEnabled();
	}

	// <editor-fold desc="requirments">

	public function checkRequirements($element)
	{
		$secured = $element->getAnnotation('secured');
		$resource = $element->getAnnotation('resource');
		$privilege = $element->getAnnotation('privilege');

		if ($secured) {
			if (!$this->user->loggedIn) {
				$this->flashMessage($this->translator->translate('flash.signedInRequired'), 'warning');
				$this->redirect(':Front:Sign:in', ['backlink' => $this->storeRequest()]);
			} elseif (!$this->user->isAllowed($resource, $privilege)) {
				throw new ForbiddenRequestException;
			}
		}
	}

	// </editor-fold>
	// <editor-fold desc="price level">

	private function loadPriceLevel()
	{
		if ($this->user->loggedIn) {
			$identity = $this->user->identity;
			if ($identity->group) {
				$this->priceLevel = $identity->group->level;
			}
		}
	}

	// </editor-fold>
	// <editor-fold desc="shop">

	private function loadShop()
	{
		$this->shopVariant = $this->shopFacade->getShopVariant();
	}

	// </editor-fold>
	// <editor-fold desc="locale">

	private function setLocale()
	{
		if ($this->user->isLoggedIn()) {
			if ($this->user->identity->locale !== $this->locale && $this->name !== 'Front:Error') { // Locale has changed
				$overwrite = $this->getParameter('overwrite', 'no');

				if ($overwrite == 'yes' || $this->user->identity->locale === NULL) {
					$this->user->storage->setLocale($this->locale);
				}

				$this->redirect('this', ['locale' => $this->user->identity->locale]);
			}
		} else {
			$this->user->storage->setLocale($this->locale);
		}
	}

	// </editor-fold>
	// <editor-fold desc="handlers">

	public function handleSetCurrency($currency)
	{
		try {
			$this->exchange->setWeb($this->exchange[$currency], TRUE);
			$this->user->storage->setCurrency($this->exchange[$currency]);
		} catch (ExchangeException $e) {
			$this->flashMessage($this->translator->translate('Requested currency isn\'t supported.'), 'warning');
		}

		$this->redirect('this');
	}

	// </editor-fold>
	// <editor-fold desc="components">
	// </editor-fold>
	// <editor-fold desc="css webloader">

	/** @return CssLoader */
	protected function createComponentCssFront()
	{
		$css = $this->webLoader->createCssLoader('front')
			->setMedia('screen,projection,tv');
		return $css;
	}

	/** @return CssLoader */
	protected function createComponentCssFront2()
	{
		$css = $this->webLoader->createCssLoader('front2')
			->setMedia('screen,projection,tv');
		return $css;
	}

	/** @return CssLoader */
	protected function createComponentCssApp()
	{
		$css = $this->webLoader->createCssLoader('app')
			->setMedia('screen,projection,tv');
		return $css;
	}

	/** @return CssLoader */
	protected function createComponentCssPrint()
	{
		$css = $this->webLoader->createCssLoader('print')
			->setMedia('print');
		return $css;
	}

	// </editor-fold>
}
