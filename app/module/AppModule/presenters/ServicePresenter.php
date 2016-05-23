<?php

namespace App\AppModule\Presenters;

use App\Extensions\Installer;
use App\Extensions\LimitExceededException;
use App\Extensions\WrongSituationException;
use App\Model\Facade\RoleFacade;
use App\Model\Facade\UserFacade;
use Doctrine\ORM\Tools\SchemaTool;
use Kdyby\Doctrine\Connection;
use Nette\Utils\FileSystem;

class ServicePresenter extends BasePresenter
{

	/** @var Connection @inject */
	public $connection;

	/** @var Installer @inject */
	public $installer;

	/** @var RoleFacade @inject */
	public $roleFacade;

	/** @var UserFacade @inject */
	public $userFacade;

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		$this->redirect('tools');
	}

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('tools')
	 */
	public function actionTools()
	{
		
	}

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('creators')
	 */
	public function actionCreators()
	{
		
	}

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('importInitData')
	 */
	public function handleImportInitData()
	{
		$this->reinstall();
		$this->importDbAll();
		$message = $this->translator->translate('Data was imported from SQL files');
		$this->flashMessage($message, 'success');
		$this->redirect('this');
	}

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('install')
	 */
	public function handleInstall()
	{
		$this->install();
		$message = $this->translator->translate('DB was instaled');
		$this->flashMessage($message, 'success');
		$this->redirect('this');
	}

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('reinstall')
	 */
	public function handleReinstall()
	{
		$this->reinstall();
		$message = $this->translator->translate('DB was reinstaled');
		$this->flashMessage($message, 'success');
		$this->redirect('this');
	}

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('removeCache')
	 */
	public function handleRemoveCache()
	{
		$this->removeCache();
		$message = $this->translator->translate('Cache was cleared');
		$this->flashMessage($message, 'success');
		$this->redirect('this');
	}

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('afterDeploy')
	 */
	public function handleAfterDeploy()
	{
		$this->removeCache();
		$message = $this->translator->translate('Cache was cleared with install');
		$this->flashMessage($message, 'success');
		$this->redirect('this');
	}

	private function removeCache()
	{
		$cacheFolder = './../temp/cache/';
		
		foreach (scandir(realpath($cacheFolder)) as $key => $folder) {
			$folderName = realpath($cacheFolder . $folder);
			if ($folder !== '.' && $folder !== '..' && !preg_match('/^\.delete/', $folder)) {
				$newName = $cacheFolder . '/.delete' . uniqid() . $key;
				FileSystem::rename($folderName, $newName);
			}
		}
		
		foreach (scandir(realpath($cacheFolder)) as $folder) {
			$folderName = realpath($cacheFolder . $folder);
			if (preg_match('/^\.delete/', $folder)) {
				FileSystem::delete($folderName);
			}
		}
		
		return $this;
	}

	private function reinstall()
	{
		$this->uninstall();
		$this->install();
		return $this;
	}

	private function uninstall()
	{
		$schemaTool = new SchemaTool($this->em);
		$schemaTool->dropSchema($this->em->getMetadataFactory()->getAllMetadata());
		$this->em->clear();
		return $this;
	}

	private function install()
	{
		FileSystem::delete(realpath('./../temp/install/'));
		$this->installer
				->setInstallDoctrine(TRUE)
				->setInstallAdminer(FALSE)
				->setInstallComposer(FALSE);
		$this->installer->install();
		return $this;
	}

	private function importDbAll()
	{
		return $this;
	}

}
