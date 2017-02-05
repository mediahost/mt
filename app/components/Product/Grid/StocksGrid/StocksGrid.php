<?php

namespace App\Components\Product\Grid;

use App\Components\BaseControl;
use App\Components\Product\Form\StockPrice;
use App\Extensions\Grido\BaseGrid;
use App\Model\Entity\Discount;
use App\Model\Entity\Group;
use App\Model\Entity\Price;
use App\Model\Entity\Sign;
use App\Model\Entity\Stock;
use Grido\Components\Export;
use Grido\DataSources\Doctrine;
use Grido\Grid;
use Nette\Utils\DateTime;
use Nette\Utils\Html;

class StocksGrid extends BaseControl
{

	const ID = 'grid';

	/** @var array */
	private $ids = [];

	/** @return Grid */
	protected function createComponentGrid()
	{
		$grid = new BaseGrid();
		$grid->setTranslator($this->translator);
		$grid->setTheme(BaseGrid::THEME_METRONIC);

		$this->exchange->setVat(0, NULL, NULL);

		$stockRepo = $this->em->getRepository(Stock::getClassName());
		$qb = $stockRepo->createQueryBuilder('s')
			->select('s, p')
			->leftJoin('s.product', 'p')
			->leftJoin('p.translations', 't')
			->where('t.locale = :lang OR t.locale = :defaultLang')
			->andWhere('s.deletedAt IS NULL OR s.deletedAt > :now')
			->setParameter('lang', $this->translator->getLocale())
			->setParameter('defaultLang', $this->translator->getDefaultLocale())
			->setParameter('now', new DateTime());

		if (count($this->ids)) {
			$qb->andWhere('s.id IN (:ids)')
				->setParameter('ids', $this->ids);
		}

		$grid->model = new Doctrine($qb, [
			'product' => 'p',
			'product.name' => 't.name',
		]);

		$grid->setDefaultSort([
			'id' => 'DESC',
		]);


		$signRepo = $this->em->getRepository(Sign::getClassName());
		$signs = $signRepo->findAll();

		/***************************************************/
		$grid->addColumnNumber('id', 'ID')
			->setSortable()
			->setFilterNumber();
		$grid->getColumn('id')->headerPrototype->width = '5%';

		/***************************************************/
		$grid->addColumnNumber('pohodaCode', 'Code')
			->setSortable()
			->setFilterNumber();
		$grid->getColumn('pohodaCode')->headerPrototype->width = '5%';

		/***************************************************/
		$grid->addColumnImage('image', 'Image')
			->setColumn('product.image')
			->setSize(50, 32);

		/***************************************************/
		$grid->addColumnText('title', 'Product title')
			->setColumn('product.name')
			->setCustomRender(function ($row) use ($signs) {
				$name = $row->product->translate($this->translator->getLocale())->name;
				$signColors = [
					1 => 'danger',
					2 => 'success',
					3 => 'primary',
				];
				foreach ($signs as $sign) {
					if ($row->product->hasSign($sign)) {
						$sign->setCurrentLocale($this->translator->getLocale());
						$color = array_key_exists($sign->id, $signColors) ? $signColors[$sign->id] : 'label-info';
						$name .= ' ' . Html::el('span class="badge badge-roundless badge-' . $color . '"')->setText($sign);
					}
				}
				return $name;
			})
			->setSortable()
			->setFilterText()
			->setSuggestion();

		/***************************************************/
		$grid->addColumnNumber('purchasePrice', 'Purchase price')
			->setCustomRender(function ($row) {
				if ($row->purchasePrice) {
					return $this->exchange->format($row->purchasePrice->withoutVat);
				}
				return NULL;
			})
			->setCustomRenderExport(function ($row) {
				if ($row->purchasePrice) {
					return Price::floatToStr($row->purchasePrice->withoutVat);
				}
				return NULL;
			})
			->setSortable()
			->setFilterNumber();
		$grid->getColumn('purchasePrice')->headerPrototype->style = 'width:130px';
		$grid->getColumn('purchasePrice')->cellPrototype->style = 'text-align: right';

		/***************************************************/
		$grid->addColumnNumber(Stock::DEFAULT_PRICE_NAME, 'Price')
			->setCustomRender(function ($row) {
				$link = Html::el('a', [
					'data-toggle' => 'modal',
					'data-target' => '#editPrice',
				])
					->href($this->presenter->link('editPrices', $row->id))
					->setText($this->exchange->format($row->price->withoutVat));
				return $link;
			})
			->setCustomRenderExport(function ($row) {
				return Price::floatToStr($row->price->withoutVat);
			})
			->setSortable()
			->setFilterNumber();
		$grid->getColumn(Stock::DEFAULT_PRICE_NAME)->headerPrototype->style = 'width:110px';
		$grid->getColumn(Stock::DEFAULT_PRICE_NAME)->cellPrototype->style = 'text-align: right';
//		$grid->getColumn(Stock::DEFAULT_PRICE_NAME)->cellPrototype->class[] = 'changeOnClick'; // commented for link with ajax edit
		$grid->getColumn(Stock::DEFAULT_PRICE_NAME)
			->setEditableCallback(function ($id, $newValue, $oldValue, $column) {
				$stockRepo = $this->em->getRepository(Stock::getClassName());
				$stock = $stockRepo->find($id);
				if ($stock) {
					$stock->setDefaltPrice($newValue);
					$stockRepo->save($stock);
					return TRUE;
				} else {
					return FALSE;
				}
			})
			->setEditableRowCallback(function ($id, $column) {
				return $this->em->getRepository(Stock::getClassName())->find($id);
			})
			->setEditableValueCallback(function (Stock $item) {
				return $item->price->withoutVat;
			});

		/***************************************************/
		$groupRepo = $this->em->getRepository(Group::getClassName());
		$groups = $groupRepo->findAll();
		foreach ($groups as $group) {
			$priceLevel = 'price' . $group->level;
			$grid->addColumnNumber($priceLevel, (string)$group)
				->setOnlyForExport()
				->setCustomRenderExport(function ($row) use ($priceLevel, $group) {
					$discount = $row->getDiscountByGroup($group);
					if ($discount && $discount->type === Discount::PERCENTAGE) {
						return (StockPrice::PERCENT_IS_PRICE ? $discount->value : 100 - $discount->value) . '%';
					}
					return Price::floatToStr($row->$priceLevel->withoutVat);
				});
		}

		/***************************************************/
		$grid->addColumnNumber('quantity', 'Store')
			->setDisableExport()
			->setNumberFormat(0, NULL, ' ')
			->setSortable()
			->setFilterNumber();
		$grid->getColumn('quantity')->headerPrototype->style = 'width:100px';
		$grid->getColumn('quantity')->cellPrototype->style = 'text-align: right';

		/***************************************************/
		$grid->addColumnNumber('inStore', 'E-shop')
			->setDisableExport()
			->setNumberFormat(0, NULL, ' ')
			->setSortable()
			->setFilterNumber();
		$grid->getColumn('inStore')->headerPrototype->style = 'width:100px';
		$grid->getColumn('inStore')->cellPrototype->style = 'text-align: right';

		/***************************************************/
		$grid->addColumnNumber('lock', 'Locked')
			->setColumn('locked')
			->setDisableExport()
			->setNumberFormat(0, NULL, ' ')
			->setSortable()
			->setFilterNumber();
		$grid->getColumn('lock')->headerPrototype->style = 'width:90px';
		$grid->getColumn('lock')->cellPrototype->style = 'text-align: right';

		/***************************************************/
		$grid->addColumnBoolean('active', 'Public')
			->setDisableExport()
			->setSortable()
			->setFilterSelect([1 => 'YES', 0 => 'NO']);
		$grid->getColumn('active')->headerPrototype->style = 'width:95px';

		$grid->addActionHref('view', NULL)
			->setCustomRender(function ($item) {
				$icon = Html::el('i class="fa fa-eye"');
				return Html::el('a class="grid-action-view btn btn-xs btn-mini"')
					->href($this->presenter->link(':Front:Product:', ['id' => $item->getUrlId(), 'slug' => $item->getSlug()]))
					->setHtml($icon . ' ' . $this->translator->translate('View on web'));
			});

		$grid->addActionHref('edit', 'Edit')
			->setIcon('fa fa-edit');

		$grid->addActionHref('delete', 'Delete')
			->setIcon('fa fa-trash-o')
			->setConfirm(function ($item) {
				$message = $this->translator->translate('Are you sure you want to delete \'%name%\'?', NULL, ['name' => (string)$item]);
				return $message;
			})
			->getElementPrototype()->class[] = 'red';

		$operation = [
			'export' => $this->translator->translate('Grido.Export'),
		];
		$grid->setOperation($operation, $this->handleOperations);

		$grid->setActionWidth("20%");

		$grid->setExport('stocks')
			->setCsv(';');

		return $grid;
	}

	public function handleOperations($operation, $ids)
	{
		switch ($operation) {
			case 'export':
				$this->presenter->redirect('Products:export', ['ids' => $ids]);
				break;
		}
	}

	public function setIds()
	{
		if (is_array(func_get_arg(0))) {
			$this->ids = func_get_arg(0);
		} else {
			$this->ids = func_get_args();
		}
		return $this;
	}

	public function getExport()
	{
		return $this->getComponent(self::ID)->getComponent(Export::ID);
	}

}

interface IStocksGridFactory
{

	/** @return StocksGrid */
	function create();
}
