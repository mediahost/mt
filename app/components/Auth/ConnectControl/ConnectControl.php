<?php

namespace App\Components\Auth;

use App\Components\BaseControl;
use App\Model\Entity\Facebook;
use App\Model\Entity\Twitter;
use App\Model\Entity\User;
use Nette\Utils\ArrayHash;

/**
 * ConnectManagerControl
 */
class ConnectManagerControl extends BaseControl
{

	const APP = 'App login';
	const FACEBOOK = 'Facebook';
	const TWITTER = 'Twitter';

	// <editor-fold desc="events">

	/** @var array */
	public $onConnect = [];

	/** @var array */
	public $onDisconnect = [];

	/** @var array */
	public $onLastConnection = [];

	/** @var array */
	public $onInvalidType = [];

	/** @var array */
	public $onUsingConnection = [];

	// </editor-fold>
	// <editor-fold desc="injects">

	/** @var IFacebookControlFactory @inject */
	public $iFacebookControlFactory;

	/** @var ITwitterControlFactory @inject */
	public $iTwitterControlFactory;

	// </editor-fold>

	/** @var User */
	private $user;

	/** @var string */
	private $redirectAppActivate;

	/**
	 * Set user to manage
	 * @param User $user
	 * @return self
	 */
	public function setUser(User $user)
	{
		$this->user = $user;
		return $this;
	}

	/**
	 * Set destination to redirect to activate app account
	 * @param string $link put result of $this->link()
	 * @param bool $relative if TRUE then link will be transformed to link
	 * @return self
	 */
	public function setAppActivateRedirect($link, $relative = FALSE)
	{
		if ($relative) {
			$link = $this->link($link);
		}
		$this->redirectAppActivate = $link;
		return $this;
	}

	/**
	 * Activation and deactivation.
	 */
	public function render()
	{
		$initConnection = ArrayHash::from([
					'name' => NULL,
					'active' => FALSE,
					'link' => '#',
		]);

		$appConnection = clone $initConnection;
		$appConnection->name = $this->translator->translate('SourceCode');
		$appConnection->active = $this->user->hasSocialConnection(User::SOCIAL_CONNECTION_APP);
		$appConnection->link = $appConnection->active ?
				$this->link('deactivate!', User::SOCIAL_CONNECTION_APP) :
				$this->redirectAppActivate ? $this->redirectAppActivate : '#';

		$fbConnection = clone $initConnection;
		$fbConnection->name = $this->translator->translate('Facebook');
		$fbConnection->active = $this->user->hasSocialConnection(User::SOCIAL_CONNECTION_FACEBOOK);
		$fbConnection->link = $fbConnection->active ?
				$this->link('deactivate!', User::SOCIAL_CONNECTION_FACEBOOK) :
				$this['facebook']->getLink();

		$twConnection = clone $initConnection;
		$twConnection->name = $this->translator->translate('Twitter');
		$twConnection->active = $this->user->hasSocialConnection(User::SOCIAL_CONNECTION_TWITTER);
		$twConnection->link = $twConnection->active ?
				$this->link('deactivate!', User::SOCIAL_CONNECTION_TWITTER) :
				$this['twitter']->getLink();

		$sources = [
			$appConnection,
			$fbConnection,
			$twConnection,
		];

		$template = $this->getTemplate();
		$template->sources = $sources;
		$template->canDisconnect = $this->user->connectionCount > 1;
		parent::render();
	}

	public function handleDeactivate($type)
	{
		if ($this->user->connectionCount <= 1) {
			$this->onLastConnection();
			$this->redrawControl();
		}

		$userRepo = $this->em->getRepository(User::getClassName());
		$user = $userRepo->find($this->user->id);

		$disconected = NULL;
		switch ($type) {
			case User::SOCIAL_CONNECTION_APP:
				$disconected = self::APP;
				$user->clearHash();
				break;
			case User::SOCIAL_CONNECTION_FACEBOOK:
				$disconected = self::FACEBOOK;
				$user->clearFacebook();
				break;
			case User::SOCIAL_CONNECTION_TWITTER:
				$disconected = self::TWITTER;
				$user->clearTwitter();
				break;
		}
		if ($disconected) {
			$savedUser = $userRepo->save($user);
			$this->onDisconnect($savedUser, $disconected);
		} else {
			$this->onInvalidType($type);
		}
		$this->redrawControl();
	}

	// <editor-fold desc="controls">

	/** @return FacebookControl */
	protected function createComponentFacebook()
	{
		$control = $this->iFacebookControlFactory->create();
		$control->setConnect();
		$control->onConnect[] = function (Facebook $fb) {
			$fbDao = $this->em->getDao(Facebook::getClassName());
			if ($fbDao->find($fb->id)) {
				$this->onUsingConnection(self::FACEBOOK);
				return;
			}
			$userDao = $this->em->getDao(User::getClassName());
			$user = $userDao->find($this->user->id);
			if (!$user->hasSocialConnection(User::SOCIAL_CONNECTION_FACEBOOK)) {
				$user->facebook = $fb;
				$userDao->save($user);
			}
			$this->onConnect(self::FACEBOOK);
		};
		return $control;
	}

	/** @return TwitterControl */
	protected function createComponentTwitter()
	{
		$control = $this->iTwitterControlFactory->create();
		$control->setConnect();
		$control->onConnect[] = function (Twitter $tw) {
			$twDao = $this->em->getDao(Twitter::getClassName());
			if ($twDao->find($tw->id)) {
				$this->onUsingConnection(self::TWITTER);
				return;
			}
			$userDao = $this->em->getDao(User::getClassName());
			$user = $userDao->find($this->user->id);
			if (!$user->hasSocialConnection(User::SOCIAL_CONNECTION_TWITTER)) {
				$user->twitter = $tw;
				$userDao->save($user);
			}
			$this->onConnect(self::TWITTER);
		};
		return $control;
	}

	// </editor-fold>
}

interface IConnectManagerControlFactory
{

	/** @return ConnectManagerControl */
	function create();
}
