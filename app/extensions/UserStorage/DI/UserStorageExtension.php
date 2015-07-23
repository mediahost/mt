<?php

namespace App\Extensions\UserStorage\DI;

use Nette\DI\CompilerExtension;

class UserStorageExtension extends CompilerExtension
{

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('user'))
				->setClass('Majkl578\NetteAddons\Doctrine2Identity\Http\UserStorage')
				->setAutowired(FALSE);

		$builder->addDefinition($this->prefix('guest'))
				->setClass('App\Extensions\UserStorage\GuestStorage')
				->setAutowired(FALSE);
	}

	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();

		$userStorageDefinitionName = $builder->getByType('Nette\Security\IUserStorage') ? : 'nette.userStorage';
		$builder->getDefinition($userStorageDefinitionName)
				->setFactory('App\Extensions\UserStorage\UserStorageStrategy')
				->addSetup('setUser', [$builder->getDefinition($this->prefix('user'))])
				->addSetup('setGuest', [$builder->getDefinition($this->prefix('guest'))]);
	}

}
