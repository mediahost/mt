<?php

namespace App\AppModule\Presenters;

use App\Components\Order\Form\IOrderProductsEditFactory;
use App\Components\Order\Form\OrderProductsEdit;
use App\Components\Order\Grid\IOrdersGridFactory;
use App\Components\Order\Grid\OrdersGrid;
use App\Model\Entity\Order;
use App\Model\Repository\OrderRepository;
use Exception;

class OrdersPresenter extends BasePresenter
{

	/** @var OrderRepository */
	private $orderRepo;

	/** @var Order */
	private $orderEntity;

	// <editor-fold desc="injects">

	/** @var IOrderProductsEditFactory @inject */
	public $iOrderProductsEditFactory;

	/** @var IOrdersGridFactory @inject */
	public $iOrdersGridFactory;

	// </editor-fold>

	protected function startup()
	{
		parent::startup();
		$this->orderRepo = $this->em->getRepository(Order::getClassName());
	}

	/**
	 * @secured
	 * @resource('orders')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		
	}

	/**
	 * @secured
	 * @resource('orders')
	 * @privilege('edit')
	 */
	public function actionEdit($id)
	{
		$this->orderEntity = $this->orderRepo->find($id);
		if (!$this->orderEntity) {
			$message = $this->translator->translate('wasntFoundShe', NULL, ['name' => $this->translator->translate('Order')]);
			$this->flashMessage($message, 'warning');
			$this->redirect('default');
		} else {
			$this['orderProductsForm']->setOrder($this->orderEntity);
		}
		$this->template->order = $this->orderEntity;
	}

	/**
	 * @secured
	 * @resource('orders')
	 * @privilege('delete')
	 */
	public function actionDelete($id)
	{
		$order = $this->orderRepo->find($id);
		if (!$order) {
			$message = $this->translator->translate('wasntFoundShe', NULL, ['name' => $this->translator->translate('Order')]);
			$this->flashMessage($message, 'danger');
		} else {
			try {
				$this->orderRepo->delete($order);
				$message = $this->translator->translate('successfullyDeletedShe', NULL, ['name' => $this->translator->translate('Order')]);
				$this->flashMessage($message, 'success');
			} catch (Exception $e) {
				$message = $this->translator->translate('cannotDeleteShe', NULL, ['name' => $this->translator->translate('Order')]);
				$this->flashMessage($message, 'danger');
			}
		}
		$this->redirect('default');
	}

	public function canDelete(Order $order)
	{
		// TODO: musí zohlednit stav objednávky
		return $this->user->isAllowed('orders', 'delete');
	}

	// <editor-fold desc="forms">

	/** @return OrderProductsEdit */
	public function createComponentOrderProductsForm()
	{
		$control = $this->iOrderProductsEditFactory->create();
		$control->onAfterSave = function (Order $savedOrder) {
			$message = $this->translator->translate('successfullySavedShe', NULL, [
				'type' => $this->translator->translate('Order'), 'name' => (string) $savedOrder
			]);
			$this->flashMessage($message, 'success');
			$this->redirect('this');
		};
		return $control;
	}

	// </editor-fold>
	// <editor-fold desc="grids">

	/** @return OrdersGrid */
	public function createComponentOrdersGrid()
	{
		$control = $this->iOrdersGridFactory->create();
		return $control;
	}

	// </editor-fold>
}
