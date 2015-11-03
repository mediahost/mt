<?php

namespace App\AppModule\Presenters;

use App\Extensions\ImportFromMT1;
use App\Extensions\ImportFromMT1Exception;
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

	/** @var ImportFromMT1 @inject */
	public $importFromOld;

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
	 * @privilege('imports')
	 */
	public function actionImports()
	{
		
	}

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('importOldUsers')
	 */
	public function handleImportOldUsers()
	{
		try {
			$this->importFromOld->downloadUsers();
			$message = $this->translator->translate('Users was imported from old DB');
			$this->flashMessage($message, 'success');
		} catch (ImportFromMT1Exception $e) {
			$message = $this->translator->translate('Please check settings of this module');
			$this->flashMessage($message, 'warning');
		} catch (LimitExceededException $e) {
			$message = $this->translator->translate('Import wasn\'t finished. Please start it again');
			$this->flashMessage($message, 'warning');
		}
		$this->redirect('this');
	}

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('importOldProducts')
	 */
	public function handleImportOldProducts()
	{
		try {
			$this->importFromOld->downloadProducts();
			$message = $this->translator->translate('Products was imported from old DB');
			$this->flashMessage($message, 'success');
		} catch (ImportFromMT1Exception $e) {
			$message = $this->translator->translate('Please check settings of this module');
			$this->flashMessage($message, 'warning');
		} catch (WrongSituationException $e) {
			$this->flashMessage($e->getMessage(), 'warning');
		}
		$this->redirect('this');
	}

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('importOldOrders')
	 */
	public function handleImportOldOrders()
	{
		try {
			$this->importFromOld->downloadOrders();
			$message = $this->translator->translate('Orders was imported from old DB');
			$this->flashMessage($message, 'success');
		} catch (ImportFromMT1Exception $e) {
			$message = $this->translator->translate('Please check settings of this module');
			$this->flashMessage($message, 'warning');
		}
		$this->redirect('this');
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
