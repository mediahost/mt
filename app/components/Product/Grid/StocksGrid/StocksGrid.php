<?php

namespace App\Components\Product\Grid;

use App\Components\BaseControl;
use App\Extensions\Grido\BaseGrid;
use App\Model\Entity\Discount;
use App\Model\Entity\Group;
use App\Model\Entity\Price;
use App\Model\Entity\Stock;
use Grido\DataSources\Doctrine;
use Grido\Grid;
use Nette\Utils\DateTime;

class StocksGrid extends BaseControl
{

	/** @return Grid */
	protected function createComponentGrid()
	{
		$grid = new BaseGrid();
		$grid->setTranslator($this->translator);
		$grid->setTheme(BaseGrid::THEME_METRONIC);

		$stockRepo = $this->em->getRepository(Stock::getClassName());
		$qb = $stockRepo->createQueryBuilder('s')
				->select('s, p')
				->leftJoin('s.product', 'p')
				->leftJoin('p.translations', 't')
				->where('t.locale = :lang OR t.locale = :defaultLang')
				->andWhere('s.deletedAt IS NULL OR s.deletedAt > :now')
				->setParameter('lang', $this->lang)
				->setParameter('defaultLang', $this->languageService->defaultLanguage)
				->setParameter('now', new DateTime());
		$grid->model = new Doctrine($qb, [
			'product' => 'p',
			'product.name' => 't.name',
		]);

		$grid->setDefaultSort([
			'id' => 'DESC',
		]);

		/*		 * ************************************************ */
		$grid->addColumnNumber('id', 'ID')
				->setSortable()
				->setFilterNumber();
		$grid->getColumn('id')->headerPrototype->width = '5%';

		/*		 * ************************************************ */
		$grid->addColumnText('title', 'Product title')
				->setColumn('product.name')
				->setCustomRender(function ($row) {
					return $row->product->translate($this->lang)->name;
				})
				->setSortable()
				->setFilterText()
				->setSuggestion();

		/*		 * ************************************************ */
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

		/*		 * ************************************************ */
		$grid->addColumnNumber('defaultPrice', 'Price')
				->setCustomRender(function ($row) {
					return $this->exchange->format($row->price->withoutVat);
				})
				->setCustomRenderExport(function ($row) {
					return Price::floatToStr($row->price->withoutVat);
				})
				->setSortable()
				->setFilterNumber();
		$grid->getColumn('defaultPrice')->headerPrototype->style = 'width:110px';
		$grid->getColumn('defaultPrice')->cellPrototype->style = 'text-align: right';

		/*		 * ************************************************ */
		$groupRepo = $this->em->getRepository(Group::getClassName());
		$groups = $groupRepo->findAll();
		foreach ($groups as $group) {
			$priceLevel = 'price' . $group->level;
			$grid->addColumnNumber($priceLevel, (string) $group)
					->setOnlyForExport()
					->setCustomRenderExport(function ($row) use ($priceLevel, $group) {
						$discount = $row->getDiscountByGroup($group);
						if ($discount && $discount->type === Discount::PERCENTAGE) {
							return $discount->value . '%';
						}
						return Price::floatToStr($row->$priceLevel->withoutVat);
					});
		}

		/*		 * ************************************************ */
		$grid->addColumnNumber('quantity', 'Store')
				->setDisableExport()
				->setNumberFormat(0, NULL, ' ')
				->setSortable()
				->setFilterNumber();
		$grid->getColumn('quantity')->headerPrototype->style = 'width:100px';
		$grid->getColumn('quantity')->cellPrototype->style = 'text-align: right';

		/*		 * ************************************************ */
		$grid->addColumnNumber('inStore', 'E-shop')
				->setDisableExport()
				->setNumberFormat(0, NULL, ' ')
				->setSortable()
				->setFilterNumber();
		$grid->getColumn('inStore')->headerPrototype->style = 'width:100px';
		$grid->getColumn('inStore')->cellPrototype->style = 'text-align: right';

		/*		 * ************************************************ */
		$grid->addColumnNumber('lock', 'Locked')
				->setDisableExport()
				->setNumberFormat(0, NULL, ' ')
				->setSortable()
				->setFilterNumber();
		$grid->getColumn('lock')->headerPrototype->style = 'width:90px';
		$grid->getColumn('lock')->cellPrototype->style = 'text-align: right';

		/*		 * ************************************************ */
		$grid->addColumnBoolean('active', 'Public')
				->setDisableExport()
				->setSortable()
				->setFilterSelect([1 => 'YES', 0 => 'NO']);
		$grid->getColumn('active')->headerPrototype->style = 'width:95px';

		$grid->addActionHref('view', 'View on web', ':Front:Product:viewById')
				->setIcon('fa fa-eye');

		$grid->addActionHref('edit', 'Edit')
				->setIcon('fa fa-edit');

		$grid->addActionHref('delete', 'Delete')
						->setIcon('fa fa-trash-o')
						->setConfirm(function($item) {
							$message = $this->translator->translate('Are you sure you want to delete \'%name%\'?', NULL, ['name' => (string) $item]);
							return $message;
						})
				->elementPrototype->class[] = 'red';

		$grid->setActionWidth("20%");

		$grid->setExport('stocks')
				->setCsv(';');

		return $grid;
	}

}

interface IStocksGridFactory
{

	/** @return StocksGrid */
	function create();
}
