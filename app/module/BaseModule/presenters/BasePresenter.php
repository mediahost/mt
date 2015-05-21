<?php

namespace App\BaseModule\Presenters;

use App\Components\Auth\ISignOutControlFactory;
use App\Components\Auth\SignOutControl;
use App\Extensions\Settings\Model\Service\DesignService;
use App\Extensions\Settings\Model\Service\LanguageService;
use App\Extensions\Settings\Model\Storage\DefaultSettingsStorage;
use App\Model\Entity;
use App\Model\Facade\UserFacade;
use App\TaggedString;
use GettextTranslator\Gettext;
use Kdyby\Doctrine\EntityManager;
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
	public $lang = '';

	/** @persistent */
	public $backlink = '';

	// <editor-fold desc="injects">

	/** @var LoaderFactory @inject */
	public $webLoader;

	/** @var ISignOutControlFactory @inject */
	public $iSignOutControlFactory;

	/** @var Gettext @inject */
	public $translator;

	/** @var DefaultSettingsStorage @inject */
	public $settingStorage;

	/** @var DesignService @inject */
	public $designService;

	/** @var LanguageService @inject */
	public $languageService;

	/** @var EntityManager @inject */
	public $em;

	/** @var UserFacade @inject */
	public $userFacade;

	// </editor-fold>

	protected function startup()
	{
		parent::startup();
		$this->loadUserSettings();
		$this->setLang();
	}

	protected function beforeRender()
	{
		$this->template->lang = $this->lang;
		$this->template->setTranslator($this->translator);
		$this->template->allowedLanguages = $this->languageService->allowedLanguages;
		$this->template->designSettings = $this->designService->settings;
		$this->template->designColors = $this->designService->colors;
		$this->template->pageInfo = $this->settingStorage->pageInfo;
	}

	// <editor-fold desc="flash messages">

	/** Translate flash messages if not HTML */
	public function flashMessage($message, $type = 'info')
	{
		if (is_string($message)) {
			$message = $this->translator->translate($message);
		} else if ($message instanceof TaggedString) {
			$message->setTranslator($this->translator);
			$message = (string) $message;
		}
		parent::flashMessage($message, $type);
	}

	// </editor-fold>
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
			$this->flashMessage('You should be logged in!');
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
	// <editor-fold desc="language">

	private function setLang()
	{
		$presenterExceptions = [
			'Front:Install',
		];
		if (in_array($this->presenter->name, $presenterExceptions)) { // defaultLanguage for some presenters
			$this->lang = NULL;
			return;
		}

		/**
		 * Nejvyšší prioritu má jazyk nastavený u uživatele
		 * Druhou prioritu má jazyk zadaný v URL
		 * V případě, že není jazyk nastaven ani v uživatelském nastavení, ani URL, pak se detekuje automaticky
		 */
		// for identity in session load from settings
		if ($this->languageService->userLanguage || !$this->lang) {
			$this->lang = $this->languageService->userLanguage;
		}
		// for no identity in session or not setted in identity (detect from browser or default)
		if (!$this->lang) {
			$this->lang = $this->languageService->detectedLanguage;
		}
		$this->translator->setLang($this->lang);
	}

	// </editor-fold>
	// <editor-fold desc="handlers">

	public function handleChangeLanguage($newLang)
	{
		if ($this->languageService->isAllowed($newLang)) {
			$this->languageService->userLanguage = $newLang;
			$this->redirect('this', ['lang' => $newLang]);
		} else {
			$this->flashMessage('Requested language isn\'t supported.', 'warning');
			$this->redirect('this');
		}
	}

	// </editor-fold>
	// <editor-fold desc="components">

	/** @return SignOutControl */
	public function createComponentSignOut()
	{
		return $this->iSignOutControlFactory->create();
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
