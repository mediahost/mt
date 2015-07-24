<?php

namespace App\BaseModule\Presenters;

use App\Components\Auth\ISignOutFactory;
use App\Components\Auth\SignOut;
use App\Extensions\Settings\Model\Service\DesignService;
use App\Extensions\Settings\Model\Service\ModuleService;
use App\Extensions\Settings\Model\Service\PageConfigService;
use App\Extensions\Settings\Model\Service\PageInfoService;
use App\Extensions\Settings\Model\Storage\DefaultSettingsStorage;
use App\Model\Entity;
use App\Model\Facade\GroupFacade;
use App\Model\Facade\ParameterFacade;
use App\Model\Facade\ProducerFacade;
use App\Model\Facade\StockFacade;
use App\Model\Facade\UserFacade;
use h4kuna\Exchange\Exchange;
use h4kuna\Exchange\ExchangeException;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Translation\Translator;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Presenter;
use WebLoader\Nette\CssLoader;
use WebLoader\Nette\LoaderFactory;

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Presenter
{

	/** @persistent */
	public $locale;

	/** @persistent */
	public $currency = '';

	/** @persistent */
	public $backlink = '';

	// <editor-fold desc="injects">

	/** @var LoaderFactory @inject */
	public $webLoader;

	/** @var Exchange @inject */
	public $exchange;

	/** @var ISignOutFactory @inject */
	public $iSignOutFactory;

	/** @var Translator @inject */
	public $translator;

	/** @var DefaultSettingsStorage @inject */
	public $settingStorage;

	/** @var DesignService @inject */
	public $designService;

	/** @var ModuleService @inject */
	public $moduleService;

	/** @var PageConfigService @inject */
	public $pageConfigService;

	/** @var PageInfoService @inject */
	public $pageInfoService;

	/** @var EntityManager @inject */
	public $em;

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

	/** @var int */
	protected $priceLevel = NULL;

	// </editor-fold>

	protected function startup()
	{
		parent::startup();
		$this->loadUserSettings();
		$this->setLocale();
		$this->setCurrency();
		$this->loadPriceLevel();
	}

	protected function beforeRender()
	{
		$this->template->lang = $this->translator->locale;
		$this->template->setTranslator($this->translator);
		$this->template->allowedLanguages = $this->translator->getAvailableLocales();
		$this->template->designSettings = $this->designService->settings;
		$this->template->designColors = $this->designService->colors;
		$this->template->pageInfo = $this->pageInfoService;
		$this->template->exchange = $this->exchange;
		if ($this->currency) {
			$currency = $this->exchange[$this->currency];
			$this->template->currency = $currency;
			$this->template->currencySymbol = $currency->getFormat()->getSymbol();
		}
	}

	protected function isInstallPresenter()
	{
		$presenterExceptions = [
			'Front:Install',
		];
		return in_array($this->presenter->name, $presenterExceptions);
	}

	// <editor-fold desc="requirments">

	public function checkRequirements($element)
	{
		$secured = $element->getAnnotation('secured');
		$resource = $element->getAnnotation('resource');
		$privilege = $element->getAnnotation('privilege');

		if ($secured) {
			$this->checkSecured($resource, $privilege);
		}
	}

	private function checkSecured($resource, $privilege)
	{
		if (!$this->user->loggedIn) {
			$message = $this->translator->translate('You should be logged in!');
			$this->flashMessage($message);
			$this->redirect(':Front:Sign:in', ['backlink' => $this->storeRequest()]);
		} elseif (!$this->user->isAllowed($resource, $privilege)) {
			throw new ForbiddenRequestException;
		}
	}

	// </editor-fold>
	// <editor-fold desc="settings">

	protected function loadUserSettings()
	{
		$this->settingStorage->loggedIn = $this->user->loggedIn;
		if ($this->user->identity instanceof Entity\User) {
			$this->settingStorage->user = $this->user->identity;
		}
	}

	// </editor-fold>
	// <editor-fold desc="locale">

	public function setLocale()
	{
//		if ($this->locale !== $this->user->identity->pageConfigSettings->language) {
//			$this->user->identity->pageConfigSettings->language = $this->locale;
//			$this->em->persist($this->user->identity)
//					->flush();
//		}
	}

	// </editor-fold>
	// <editor-fold desc="currency">

	private function setCurrency()
	{
		if ($this->isInstallPresenter()) {
			$this->currency = NULL;
			return;
		}

		if (!$this->currency) {
//			$this->currency = 'czk'|NULL; // TODO: load from user setting
		}

		if (!array_key_exists(strtoupper($this->currency), $this->exchange)) {
			$this->currency = strtolower($this->exchange->getDefault()->getCode());
		}

		$this->loadCurrencyRates();
	}

	private function loadCurrencyRates()
	{
		$rateRepo = $this->em->getRepository(Entity\Rate::getClassName());
		$rates = $rateRepo->findPairs('value');

		$defaultCode = $this->exchange->getDefault()->getCode();
		foreach ($this->exchange as $code => $currency) {
			$isDefault = strtolower($code) === strtolower($defaultCode);
			$isInDb = array_key_exists($code, $rates);
			if (!$isDefault && $isInDb) {
				$dbRate = (float) $rates[$code];
				$originRate = (float) $currency->getForeing();
				$rateRelated = $originRate / $dbRate;
				$this->exchange->addRate($code, $rateRelated);
			}
		}
	}

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
	// <editor-fold desc="handlers">

	public function handleChangeLanguage($newLang)
	{
		$this->redirect('this', ['locale' => $newLang]);
	}

	public function handleChangeCurrency($newCurrency)
	{
		try {
			$currency = $this->exchange[$newCurrency];
			$newCode = strtolower($currency->getCode());
			// TODO: save to user settings
			$this->redirect('this', ['currency' => $newCode]);
		} catch (ExchangeException $ex) {
			$message = $this->translator->translate('Requested currency isn\'t supported.');
			$this->flashMessage($message, 'warning');
			$this->redirect('this');
		}
	}

	// </editor-fold>
	// <editor-fold desc="components">

	/** @return SignOut */
	public function createComponentSignOut()
	{
		return $this->iSignOutFactory->create();
	}

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
