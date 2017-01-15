<?php

namespace App\AppModule\Presenters;

use App\Components\Currency\Form\IRateFactory;
use App\Components\Currency\Form\Rate;
use App\Components\Shop\Form\IShopEditFactory;
use App\Components\Shop\Form\ShopEdit;
use App\Components\Unit\Form\IUnitsEditFactory;
use App\Components\Unit\Form\UnitsEdit;
use App\Model\Entity\Shop;
use App\Model\Entity\ShopVariant;
use App\Model\Entity\Unit;
use App\Model\Entity\Variant;
use Kdyby\Doctrine\EntityRepository;

class ShopsPresenter extends BasePresenter
{

	/** @var Shop */
	private $shop;

	/** @var EntityRepository */
	private $shopRepo;

	// <editor-fold desc="injects">

	/** @var IShopEditFactory @inject */
	public $iShopEditFactory;

	// </editor-fold>

	protected function startup()
	{
		parent::startup();
		$this->shopRepo = $this->em->getRepository(Shop::getClassName());
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
		$this->shop = new Shop();
		$this->setView('edit');
	}

	/**
	 * @secured
	 * @resource('shops')
	 * @privilege('edit')
	 */
	public function actionEdit($id)
	{
		$this->shop = $this->shopRepo->find($id);
		if (!$this->shop) {
			$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('Shop')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('default');
		}
	}

	public function renderEdit()
	{
		$this->template->shop = $this->shop;
	}

	/**
	 * @secured
	 * @resource('shops')
	 * @privilege('addVariant')
	 */
	public function handleAddVariant($shopId, $code)
	{
		$this->shop = $this->shopRepo->find($shopId);
		if (!$this->shop) {
			$message = $this->translator->translate('wasntFound', NULL, ['name' => $this->translator->translate('Shop')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('default');
		}

		$variant = new ShopVariant($code);
		$this->shop->addVariant($variant);
		$this->shopRepo->save($this->shop);

		$this->redirect('default');
	}

	// <editor-fold desc="forms">

	/** @return ShopEdit */
	public function createComponentShopForm()
	{
		$control = $this->iShopEditFactory->create();
		$control->setShop($this->shop);
		$control->onAfterSave = function () {
			$message = $this->translator->translate('Shop was successfully saved.');
			$this->flashMessage($message, 'success');
			$this->redirect('default');
		};
		return $control;
	}

	// </editor-fold>
}
