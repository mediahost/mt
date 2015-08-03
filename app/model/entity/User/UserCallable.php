<?php

namespace App\Model\Entity;

use Nette\DI\Container;

/**
 * UserCallable can be invoked to return a blameable user from Identity
 */
class UserCallable
{

	/** @var Container */
	private $container;

	/**
	 * @param callable
	 * @param string $userEntity
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/** @return User */
	public function __invoke()
	{
		$identity = $this->container->getService('security.user')->identity;
		if ($identity->id) {
			return $identity;
		}
	}

}
