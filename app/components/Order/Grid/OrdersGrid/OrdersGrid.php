<?php

namespace App\Components\Order\Grid;

use App\Components\BaseControl;
use App\Extensions\Grido\BaseGrid;
use App\Extensions\Grido\DataSources\Doctrine;
use App\Forms\Controls\SelectBased\Select2;
use App\Model\Entity\Order;
use App\Model\Entity\OrderState;
use App\Model\Facade\Exception\FacadeException;
use App\Model\Facade\OrderFacade;
use Grido\Grid;
use Nette\Utils\Html;

class OrdersGrid extends BaseControl
{

	/** @var OrderFacade @inject */
	public $orderFacade;

	/** @return Grid */
	protected function createComponentGrid()
	{
		$grid = new BaseGrid();
		$grid->setTranslator($this->translator);
		$grid->setTheme(BaseGrid::THEME_METRONIC);

		$repo = $this->em->getRepository(Order::getClassName());
		$qb = $repo->createQueryBuilder('o')
				->leftJoin('o.billingAddress', 'a');
		$grid->setModel(new Doctrine($qb, [
			'billingAddress' => 'a',
			'billingAddress.name' => 'a.name',
				]), TRUE);

		$grid->setDefaultSort([
			'createdAt' => 'DESC',
		]);

		$grid->addColumnText('id', 'Number')
				->setCustomRender(function ($item) {
					$link = $this->presenter->link('edit', ['id' => $item->id]);
					return Html::el('a')->href($link)->setText($item->id);
				})
				->setSortable()
				->setFilterText()
				->setSuggestion();
		$grid->getColumn('id')->headerPrototype->width = '7%';

		$stateRepo = $this->em->getRepository(OrderState::getClassName());
		$stateList = $stateRepo->findPairs('name');
		$stateSelector = new Select2(NULL, $stateList);
		$stateSelector->getControlPrototype()->class[] = 'input-medium';
		$stateRenderer = function ($item) {
			return $this->templateColumnRenderer(__DIR__ . '/state.latte', $item);
		};
		$grid->addColumnText('state', 'State')
				->setSortable()
				->setCustomRender($stateRenderer)
				->setFilterSelect([NULL => '--- anyone ---'] + $stateList);
		$grid->getColumn('state')
				->setEditableControl($stateSelector)
				->setEditableCallback(function ($id, $newValue, $oldValue, $column) {
					try {
						$this->orderFacade->changeStateByOrderId($id, $newValue);
						return TRUE;
					} catch (FacadeException $e) {
						return FALSE;
					}
				});
		$grid->getColumn('state')->cellPrototype->class[] = 'changeOnClick';

		$grid->addColumnText('address', 'Address')
				->setColumn('billingAddress.name')
				->setCustomRender(function ($item) {
					$address = (string) $item->billingAddress;
					return $item->billingAddress && $item->billingAddress->isFilled() ? $address : $item->mail;
				})
				->setSortable()
				->setFilterText()
				->setSuggestion();

		$grid->addColumnText('note', 'Note')
				->setEditableCallback(function ($id, $newValue, $oldValue, $column) {
					$orderRepo = $this->em->getRepository(Order::getClassName());
					$order = $orderRepo->find($id);
					if ($order) {
						$order->note = $newValue;
						$orderRepo->save($order);
						return TRUE;
					}
					return FALSE;
				})
				->setFilterText()
				->setSuggestion();
		$grid->getColumn('note')->cellPrototype->class[] = 'changeOnDblClick';

		$grid->addColumnText('totalPrice', 'Total price')
				->setCustomRender(function ($item) {
					$toCurrency = $item->currency;
					$totalPrice = $item->getTotalPrice($this->exchange);
					return $this->exchange->formatTo($totalPrice, $toCurrency);
				})
				->setFilterNumber();
		$grid->getColumn('totalPrice')->headerPrototype->width = '10%';
		$grid->getColumn('totalPrice')->cellPrototype->style = 'text-align: right';

		$grid->addColumnDate('createdAt', 'Created At', 'd.m.Y H:i:s')
				->setSortable()
				->setFilterText()
				->setSuggestion();
		$grid->getColumn('createdAt')->headerPrototype->width = '10%';
		$grid->getColumn('createdAt')->cellPrototype->style = 'text-align: center';

		$grid->addColumnText('locale', 'Language')
				->setSortable()
				->setFilterText()
				->setSuggestion();
		$grid->getColumn('locale')->headerPrototype->width = '4%';
		$grid->getColumn('locale')->cellPrototype->style = 'text-align: center';

		$grid->addColumnText('currency', 'Currency')
				->setCustomRender(function ($item) {
					return $item->currency . ($item->rate ? ' (' . $item->rate . ')' : '');
				})
				->setSortable()
				->setFilterSelect([
					NULL => '--- anyone ---',
					'CZK' => 'CZK',
					'EUR' => 'EUR',
		]);
		$grid->getColumn('currency')->headerPrototype->width = '7%';
		$grid->getColumn('locale')->cellPrototype->style = 'text-align: center';

		$grid->addActionHref('edit', 'Edit')
				->setIcon('fa fa-edit');

		$grid->addActionHref('delete', 'Delete')
						->setIcon('fa fa-trash-o')
						->setConfirm(function($item) {
							$message = $this->translator->translate('Are you sure you want to delete \'%name%\'?', NULL, ['name' => (string) $item]);
							return $message;
						})
						->setDisable(function($item) {
							return !$this->presenter->canDelete($item);
						})
				->elementPrototype->class[] = 'red';

		$grid->setActionWidth("10%");

		return $grid;
	}

}

interface IOrdersGridFactory
{

	/** @return OrdersGrid */
	function create();
}
