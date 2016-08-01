<?php

namespace App\AppModule\Presenters;

use App\Extensions\Installer;
use App\Extensions\LimitExceededException;
use App\Extensions\WrongSituationException;
use App\Model\Entity\Discount;
use App\Model\Entity\Group;
use App\Model\Entity\Product;
use App\Model\Entity\Stock;
use App\Model\Facade\RoleFacade;
use App\Model\Facade\UserFacade;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\ResultSetMapping;
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

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('reSaveAccessoriesFor')
	 */
	public function handleReSaveAccessoriesFor()
	{
		$count = $this->resaveProductsAccessoriesFor();
		$message = $this->translator->translate('%count% products was updated', $count);
		$this->flashMessage($message, 'success');
		$this->redirect('this');
	}

	/**
	 * @secured
	 * @resource('service')
	 * @privilege('resetBonusPrices')
	 */
	public function handleResetBonusPrices()
	{
		$this->resetBonusPrices();
		$message = $this->translator->translate('All bonus prices was restored to default values');
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

	private function resaveProductsAccessoriesFor()
	{
		$rsm = new ResultSetMapping();
		$rsm->addEntityResult(Product::getClassName(), 'p');
		$rsm->addFieldResult('p', 'product_id', 'id');
		$sql = "SELECT DISTINCT `product_id`
				FROM `product_producer_model`
				LEFT JOIN `product` ON `product_producer_model`.`product_id` = `product`.`id`
				WHERE `product`.`accessories_producer_ids` = ''
				LIMIT :limit";
		$query = $this->em->createNativeQuery($sql, $rsm)
			->setParameter('limit', 100);

		$counter = 0;
		$productsRepo = $this->em->getRepository(Product::getClassName());
		foreach ($query->getResult(AbstractQuery::HYDRATE_ARRAY) as $item) {
			$product = $productsRepo->find($item['id']);
			if ($product) {
				$product->updateAccessoriesForOptimized();
				$this->em->persist($product);
				$counter++;
			}
		}
		$this->em->flush();
		return $counter;
	}

	private function resetBonusPrices()
	{
		$groupRepo = $this->em->getRepository(Group::getClassName());
		$bonusGroups = $groupRepo->findByType(Group::TYPE_BONUS);

		$stockRepo = $this->em->getRepository(Stock::getClassName());
		$stocks = $stockRepo->findByPrice11(NULL, [], 100);
		foreach ($stocks as $stock) {
			foreach ($bonusGroups as $group) {
				$stock->addDiscount($group->getDiscount(), $group);
			}
			$stock->recalculateOtherPrices();
			$stockRepo->save($stock);
		}
		return $this;
	}

}
