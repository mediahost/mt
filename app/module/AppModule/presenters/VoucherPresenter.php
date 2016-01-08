<?php

namespace App\AppModule\Presenters;

use App\Components\Discount\Form\IVoucherEditFactory;
use App\Components\Discount\Form\VoucherEdit;
use App\Components\Discount\Grid\IVouchersGridFactory;
use App\Components\Discount\Grid\VouchersGrid;
use App\Model\Entity\Voucher;
use Exception;
use Kdyby\Doctrine\EntityRepository;

class VoucherPresenter extends BasePresenter
{

	/** @var Voucher */
	private $voucherEntity;

	/** @var EntityRepository */
	private $voucherRepo;

	// <editor-fold desc="injects">

	/** @var IVoucherEditFactory @inject */
	public $iVoucherEditFactory;

	/** @var IVouchersGridFactory @inject */
	public $iVoucherGridFactory;

	// </editor-fold>

	protected function startup()
	{
		parent::startup();
		$this->voucherRepo = $this->em->getRepository(Voucher::getClassName());
	}

	/**
	 * @secured
	 * @resource('vouchers')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		
	}

	/**
	 * @secured
	 * @resource('vouchers')
	 * @privilege('add')
	 */
	public function actionAdd($type = Voucher::DEFAULT_TYPE)
	{
		$this->voucherEntity = new Voucher(0, $type);
		$this['voucherForm']->setVoucher($this->voucherEntity);
		$this->setView('edit');
	}

	/**
	 * @secured
	 * @resource('vouchers')
	 * @privilege('edit')
	 */
	public function actionEdit($id)
	{
		$this->voucherEntity = $this->voucherRepo->find($id);
		if (!$this->voucherEntity) {
			$message = $this->translator->translate('wasntFoundShe', NULL, ['name' => $this->translator->translate('Discount')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('default');
		} else {
			$this['voucherForm']->setVoucher($this->voucherEntity);
		}
	}

	public function renderEdit()
	{
		$this->template->isAdd = $this->voucherEntity->isNew();
	}

	/**
	 * @secured
	 * @resource('vouchers')
	 * @privilege('delete')
	 */
	public function actionDelete($id)
	{
		$voucher = $this->voucherRepo->find($id);
		if (!$voucher) {
			$message = $this->translator->translate('wasntFoundShe', NULL, ['name' => $this->translator->translate('Discount')]);
			$this->flashMessage($message, 'danger');
		} else {
			try {
				$this->voucherRepo->delete($voucher);
				$message = $this->translator->translate('successfullyDeletedShe', NULL, ['name' => $this->translator->translate('Discount')]);
				$this->flashMessage($message, 'success');
			} catch (Exception $e) {
				$message = $this->translator->translate('cannotDeleteShe', NULL, ['name' => $this->translator->translate('Discount')]);
				$this->flashMessage($message, 'danger');
			}
		}
		$this->redirect('default');
	}

	// <editor-fold desc="forms">

	/** @return VoucherEdit */
	public function createComponentVoucherForm()
	{
		$control = $this->iVoucherEditFactory->create();
		$control->onAfterSave = function (Voucher $savedVoucher) {
			$message = $this->translator->translate('successfullySavedShe', NULL, [
				'type' => $this->translator->translate('Discount'), 'name' => (string) $savedVoucher
			]);
			$this->flashMessage($message, 'success');
			$this->redirect('default');
		};
		return $control;
	}

	// </editor-fold>
	// <editor-fold desc="grids">

	/** @return VouchersGrid */
	public function createComponentVouchersGrid()
	{
		$control = $this->iVoucherGridFactory->create();
		return $control;
	}

	// </editor-fold>
}
