<?php

namespace App\AppModule\Presenters;

use App\Components\Currency\Form\IRateFactory;
use App\Components\Currency\Form\Rate;
use App\Components\Shop\Form\IShopEditFactory;
use App\Components\Shop\Form\IVariantEditFactory;
use App\Components\Shop\Form\ShopEdit;
use App\Components\Unit\Form\IUnitsEditFactory;
use App\Components\Unit\Form\UnitsEdit;
use App\Model\Entity\Shop;
use App\Model\Entity\ShopVariant;
use App\Model\Entity\Unit;
use App\Model\Entity\Variant;
use App\Model\Repository\ShopRepository;
use Kdyby\Doctrine\EntityRepository;

class ShopsPresenter extends BasePresenter
{

	/** @var Shop */
	private $shopEntity;

	/** @var ShopVariant */
	private $variantEntity;

	/** @var ShopRepository */
	private $shopRepo;

	/** @var EntityRepository */
	private $shopVariantRepo;

	// <editor-fold desc="injects">

	/** @var IShopEditFactory @inject */
	public $iShopEditFactory;

	/** @var IVariantEditFactory @inject */
	public $iVariantEditFactory;

	// </editor-fold>

	protected function startup()
	{
		parent::startup();
		$this->shopRepo = $this->em->getRepository(Shop::getClassName());
		$this->shopVariantRepo = $this->em->getRepository(ShopVariant::getClassName());
	}

	/**
	 * @secured
	 * @resource('shops')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		$this->template->shops = $this->shopRepo->findAll();
		$this->template->locales = $this->translator->getAvailableLocales();
	}

	/**
	 * @secured
	 * @resource('shops')
	 * @privilege('add')
	 */
	public function actionAdd()
	{
		$this->shopEntity = new Shop();
		$this->setView('edit');
	}

	/**
	 * @secured
	 * @resource('shops')
	 * @privilege('editShop')
	 */
	public function actionEditShop($id)
	{
		$this->shopEntity = $this->shopRepo->find($id);
		if (!$this->shopEntity) {
			$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('Shop')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('default');
		}
	}

	public function renderEditShop()
	{
		$this->template->shop = $this->shopEntity;
	}

	/**
	 * @secured
	 * @resource('shops')
	 * @privilege('editVariant')
	 */
	public function actionEditVariant($id)
	{
		$this->variantEntity = $this->shopVariantRepo->find($id);
		if (!$this->variantEntity) {
			$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('Variant')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('default');
		}
	}

	public function renderEditVariant()
	{
		$this->template->variant = $this->variantEntity;
	}

	// <editor-fold desc="forms">

	/** @return ShopEdit */
	public function createComponentShopForm()
	{
		$control = $this->iShopEditFactory->create();
		$control->setShop($this->shopEntity);
		$control->onAfterSave = function () {
			$message = $this->translator->translate('Shop was successfully saved.');
			$this->flashMessage($message, 'success');
			$this->redirect('default');
		};
		return $control;
	}

	/** @return ShopEdit */
	public function createComponentVariantForm()
	{
		$control = $this->iVariantEditFactory->create();
		$control->setVariant($this->variantEntity);
		$control->onAfterSave = function () {
			$message = $this->translator->translate('Shop variant was successfully saved.');
			$this->flashMessage($message, 'success');
			$this->redirect('default');
		};
		return $control;
	}

	// </editor-fold>
}
