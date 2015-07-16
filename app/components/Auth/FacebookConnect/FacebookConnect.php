<?php

namespace App\Components\Auth;

use App\Components\BaseControl;
use App\Model\Entity;
use App\Model\Facade\RoleFacade;
use App\Model\Facade\UserFacade;
use App\Model\Storage\SignUpStorage;
use Kdyby\Facebook\Dialog\LoginDialog;
use Kdyby\Facebook\Facebook;
use Kdyby\Facebook\FacebookApiException;
use Nette\Utils\ArrayHash;
use Tracy\Debugger;

class FacebookConnect extends BaseControl
{

	/** @var array */
	public $onSuccess = [];

	/** @var array */
	public $onConnect = [];

	/** @var bool */
	private $onlyConnect = FALSE;

	/** @var Facebook @inject */
	public $facebook;

	/** @var SignUpStorage @inject */
	public $session;

	/** @var UserFacade @inject */
	public $userFacade;

	/** @var RoleFacade @inject */
	public $roleFacade;

	/**
	 * @var bool
	 * @persistent
	 */
	public $remember = FALSE;

	protected function createComponentDialog()
	{
		$dialog = $this->facebook->createDialog('login');

		/** @var LoginDialog $dialog */
		$dialog->onResponse[] = function (LoginDialog $dialog) {
			$fb = $dialog->getFacebook();

			if (!$fb->getUser()) {
				$message = $this->translator->translate('We are sorry, facebook authentication failed hard.');
				$this->presenter->flashMessage($message);
				return;
			}

			try {
				$me = $fb->api('/me');

				if ($this->onlyConnect) {
					$fb = new Entity\Facebook($me->id);
					$this->loadFacebookEntity($fb, $me);
					$this->onConnect($fb);
				} else {
					$user = $this->userFacade->findByFacebookId($fb->getUser());
					if ($user) {
						$this->loadFacebookEntity($user->facebook, $me);
						$this->em->getDao(Entity\Facebook::getClassName())->save($user->facebook);
					} else {
						$user = $this->createUser($me);
					}
					$this->onSuccess($this, $user, $this->remember);
				}
			} catch (FacebookApiException $e) {
				Debugger::log($e->getMessage(), 'facebook');
				$message = $this->translator->translate('We are sorry, facebook authentication failed hard.');
				$this->presenter->flashMessage($message);
			}
		};

		return $dialog;
	}

	public function render()
	{
		$template = $this->getTemplate();
		$template->link = $this->getLink();
		parent::render();
	}

	// <editor-fold desc="load & create">

	/**
	 * @param ArrayHash $me
	 * @return Entity\User
	 */
	protected function createUser(ArrayHash $me)
	{
		$user = new Entity\User();
		$user->requiredRole = $this->roleFacade->findByName($this->session->getRole(TRUE));

		if (isset($me->email)) {
			$user->mail = $me->email;
			$this->session->verification = TRUE;
		} else {
			$this->session->verification = FALSE;
		}

		$fb = new Entity\Facebook($me->id);
		$this->loadFacebookEntity($fb, $me);

		$user->facebook = $fb;
		return $user;
	}

	/**
	 * Load data to FB entity
	 * @param Entity\Facebook $fb
	 * @param ArrayHash $me
	 */
	protected function loadFacebookEntity(Entity\Facebook &$fb, ArrayHash $me)
	{
		if (isset($me->email)) {
			$fb->mail = $me->email;
		}
		if (isset($me->name)) {
			$fb->name = $me->name;
		}
		if (isset($me->birthday)) {
			$fb->birthday = $me->birthday;
		}
		if (isset($me->gender)) {
			$fb->gender = $me->gender;
		}
		if (isset($me->hometown)) {
			if (isset($me->hometown->name)) {
				$fb->hometown = $me->hometown->name;
			}
		}
		if (isset($me->link)) {
			$fb->link = $me->link;
		}
		if (isset($me->location)) {
			if (isset($me->location->name)) {
				$fb->location = $me->location->name;
			}
		}
		if (isset($me->locale)) {
			$fb->locale = $me->locale;
		}
		if (isset($me->username)) {
			$fb->username = $me->username;
		}

		$fb->accessToken = $fb->getAccessToken();
	}

	// </editor-fold>
	// <editor-fold desc="setters">

	/**
	 * Fire onConnect event besides onSuccess
	 * @param bool $onlyConnect
	 * @return self
	 */
	public function setConnect($onlyConnect = TRUE)
	{
		$this->onlyConnect = $onlyConnect;
		return $this;
	}

	// </editor-fold>
	// <editor-fold desc="getters">

	/**
	 * return link to open dialog
	 * @return type
	 */
	public function getLink()
	{
		return $this->link('//dialog-open!');
	}

	// </editor-fold>
}

interface IFacebookConnectFactory
{

	/** @return FacebookConnect */
	function create();
}
