<?php

namespace App\Components\Newsletter;

use App\Components\BaseControl;
use App\Extensions\Grido\BaseGrid;
use App\Model\Entity\Newsletter\Subscriber;
use Grido\DataSources\Doctrine;

class SubscriberGridControl extends BaseControl
{

	const LOCALE_DOMAIN = 'newsletter.admin.subscriber.grid';

	/** @return BaseGrid */
	protected function createComponentGrid()
	{
		$grid = new BaseGrid();
		$grid->setTranslator($this->translator);
		$grid->setTheme(BaseGrid::THEME_METRONIC);

		$repo = $this->em->getRepository(Subscriber::getClassName());
		$qb = $repo->createQueryBuilder('s');
		$grid->model = new Doctrine($qb, []);

		$grid->setDefaultSort([
			'subscribed' => 'DESC',
		]);

		////////// ID //////////
		$grid->addColumnNumber('id', self::LOCALE_DOMAIN . '.header.id')
				->setSortable()
				->setFilterNumber();
		$grid->getColumn('id')->headerPrototype->width = '5%';

		////////// E-mail //////////
		$grid->addColumnText('mail', self::LOCALE_DOMAIN . '.header.email')
				->setSortable()
				->setFilterText()
				->setSuggestion();

		////////// Subscribed //////////
		$grid->addColumnDate('subscribed', self::LOCALE_DOMAIN . '.header.subscribed', 'j.n.Y G:i')
				->setSortable()
				->setFilterDate();
		$grid->getColumn('subscribed')->headerPrototype->width = '15%';

		////////// IP //////////
		$grid->addColumnText('ip', self::LOCALE_DOMAIN . '.header.ip')
				->setFilterText();
		$grid->getColumn('ip')->headerPrototype->width = '15%';

		////////// Type //////////
		$grid->addColumnNumber('type', self::LOCALE_DOMAIN . '.header.type')
				->setCustomRender(__DIR__ . DIRECTORY_SEPARATOR . 'type.latte', ['localeDomain' => self::LOCALE_DOMAIN])
				->setFilterSelect([
					NULL => self::LOCALE_DOMAIN . '.all',
					Subscriber::TYPE_USER => self::LOCALE_DOMAIN . '.types.user',
					Subscriber::TYPE_DEALER => self::LOCALE_DOMAIN . '.types.dealer',
		]);
		$grid->getColumn('type')->headerPrototype->width = '10%';

		////////// Locale //////////
		$locales = [NULL => self::LOCALE_DOMAIN . '.all'];
		foreach ($this->translator->getAvailableLocales() as $locale) {
			$locales[substr($locale, 0, 2)] = 'default.locales.' . $locale;
		}

		$grid->addColumnText('locale', self::LOCALE_DOMAIN . '.header.locale')
				->setFilterSelect($locales);
		$grid->getColumn('locale')->headerPrototype->width = '10%';

		////////// Actions //////////
		$grid->addActionHref('delete', 'Delete', 'delete!')
				->setIcon('fa fa-trash-o')
				->setConfirm(function($item) {
					return $this->translator->translate('Are you sure you want to delete \'%name%\'?', NULL, ['name' => (string) $item]);
				})
				->setDisable(function($item) {
					return !$this->presenter->user->isAllowed('question', 'delete');
				});

		$grid->setActionWidth("15%");

		return $grid;
	}

}

interface ISubscriberGridControlFactory
{

	/** @return SubscriberGridControl */
	function create();
}
