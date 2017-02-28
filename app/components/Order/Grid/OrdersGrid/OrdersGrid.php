<?php

namespace App\Components\Order\Grid;

use App\Components\BaseControl;
use App\Extensions\Grido\BaseGrid;
use App\Extensions\Grido\DataSources\Doctrine;
use App\Forms\Controls\SelectBased\Select2;
use App\Model\Entity\Order;
use App\Model\Entity\OrderState;
use App\Model\Entity\Shop;
use App\Model\Entity\ShopVariant;
use App\Model\Facade\Exception\FacadeException;
use App\Model\Facade\OrderFacade;
use Grido\Grid;
use Nette\Utils\Html;

class OrdersGrid extends BaseControl
{

	/** @var OrderFacade @inject */
	public $orderFacade;

	/** @var Shop[] */
	private $shops = [];

	/** @var ShopVariant[] */
	private $shopVariants = [];

	/** @return Grid */
	protected function createComponentGrid()
	{
		$grid = new BaseGrid();
		$grid->setTranslator($this->translator);
		$grid->setTheme(BaseGrid::THEME_METRONIC);

		$repo = $this->em->getRepository(Order::getClassName());
		$qb = $repo->createQueryBuilder('o')
			->leftJoin('o.billingAddress', 'a');

		if (count($this->shopVariants)) {
			if (count($this->shopVariants) === 1) {
				$qb->andWhere('o.shopVariant = :shopVariant')
					->setParameter('shopVariant', current($this->shopVariants));
			} else {
				$qb->andWhere('o.shopVariant IN (:shopVariants)')
					->setParameter('shopVariants', $this->shopVariants);
			}
		} elseif (count($this->shops)) {
			if (count($this->shops) === 1) {
				$qb->andWhere('o.shop = :shop')
					->setParameter('shop', current($this->shops));
			} else {
				$qb->andWhere('o.shop IN (:shops)')
					->setParameter('shops', $this->shops);
			}
		}

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
		$grid->getColumn('id')->headerPrototype->width = '80px';

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
		$grid->getColumn('state')->headerPrototype->width = '200px';
		$grid->getColumn('state')->cellPrototype->class[] = 'changeOnClick';
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

		$grid->addColumnDate('paymentDate', 'Payment date')
			->setSortable()
			->setCustomRender(function (Order $item) {
				if ($item->paymentDate) {
					return date_format($item->paymentDate, 'd.m.Y') . ' (' . $item->paymentBlameName . ')';
				}
			});

		$grid->addColumnText('address', 'Address')
			->setColumn('billingAddress.name')
			->setCustomRender(function ($item) {
				$address = (string)$item->billingAddress;
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
			->setCustomRender(function (Order $item) {
				$totalPrice = $item->getTotalPriceToPay($this->exchange);
				return $this->exchange->format($totalPrice, $item->currency, $item->currency);
			})
			->setFilterNumber();
		$grid->getColumn('totalPrice')->headerPrototype->width = '100px';
		$grid->getColumn('totalPrice')->cellPrototype->style = 'text-align: right';

		$grid->addColumnDate('createdAt', 'Created At', 'd.m.Y H:i:s')
			->setSortable()
			->setFilterText()
			->setSuggestion();
		$grid->getColumn('createdAt')->headerPrototype->width = '150px';
		$grid->getColumn('createdAt')->cellPrototype->style = 'text-align: center';

		$grid->addColumnText('locale', 'Language')
			->setSortable()
			->setFilterText()
			->setSuggestion();
		$grid->getColumn('locale')->headerPrototype->width = '60px';
		$grid->getColumn('locale')->cellPrototype->style = 'text-align: center';

		$grid->addColumnText('currency', 'Currency')
			->setCustomRender(function ($item) {
				return $item->currency . ($item->rate ? ' (' . $item->rate . ')' : '');
			})
			->setSortable()
			->setFilterSelect([
				NULL => '---',
				'CZK' => 'CZK',
				'EUR' => 'EUR',
			]);
		$grid->getColumn('currency')->headerPrototype->width = '60px';

		$grid->addColumnText('shopVariant', 'Shop')
			->setSortable()
			->setFilterSelect([NULL => '---'] + $this->shopFacade->getVariantPairs());

		$grid->addActionHref('edit', 'Edit')
			->setIcon('fa fa-edit');

		$grid->addActionHref('delete', 'Delete')
			->setIcon('fa fa-trash-o')
			->setConfirm(function ($item) {
				$message = $this->translator->translate('Are you sure you want to delete \'%name%\'?', NULL, ['name' => (string)$item]);
				return $message;
			})
			->setDisable(function ($item) {
				return !$this->presenter->canDelete($item);
			})
			->getElementPrototype()->class[] = 'red';

		$grid->setActionWidth("10%");

		return $grid;
	}

	public function setShop(array $shopIds = [], array $variantIds = [])
	{
		if (count($variantIds)) {
			foreach ($variantIds as $variantId) {
				$shopVariantRepo = $this->em->getRepository(ShopVariant::getClassName());
				$variant = $shopVariantRepo->find($variantId);
				if ($variant) {
					$this->shopVariants[] = $variant;
					$this->shops[] = $variant->shop;
				}
			}
		} else if (count($shopIds)) {
			foreach ($shopIds as $shopId) {
				$shopRepo = $this->em->getRepository(Shop::getClassName());
				$shop = $shopRepo->find($shopId);
				if ($shop) {
					$this->shops[] = $shop;
				}
			}
		}
		return $this;
	}

}

interface IOrdersGridFactory
{

	/** @return OrdersGrid */
	function create();
}
