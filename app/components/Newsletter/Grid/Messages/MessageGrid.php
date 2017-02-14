<?php

namespace App\Components\Newsletter\Grid;

use App\Components\BaseControl;
use App\Extensions\Grido\BaseGrid;
use App\Model\Entity\Newsletter\Message;
use App\Model\Entity\Newsletter\Status;
use App\Model\Facade\LocaleFacade;
use App\Model\Facade\NewsletterFacade;
use Grido\DataSources\Doctrine;

class MessageGrid extends BaseControl
{

	const LOCALE_DOMAIN = 'newsletter.admin.newsletter.grid';

	/** @var LocaleFacade @inject */
	public $localeFacade;

	/** @var NewsletterFacade @inject */
	public $newsletterFacade;

	/** @return BaseGrid */
	protected function createComponentGrid()
	{
		$grid = new BaseGrid();
		$grid->setTranslator($this->translator);
		$grid->setTheme(BaseGrid::THEME_METRONIC);

		$repo = $this->em->getRepository(Message::getClassName());
		$qb = $repo->createQueryBuilder('m')
			->leftJoin('m.group', 'g');
		$grid->model = new Doctrine($qb, []);

		$grid->setDefaultSort([
			'created' => 'DESC',
		]);

		////////// ID //////////
		$grid->addColumnNumber('id', 'Grido.id')
			->setSortable()
			->setFilterNumber();
		$grid->getColumn('id')->headerPrototype->width = '5%';

		////////// Subject //////////
		$grid->addColumnText('subject', self::LOCALE_DOMAIN . '.header.subject')
			->setSortable()
			->setFilterText()
			->setSuggestion();

		////////// Created //////////
		$grid->addColumnDate('created', self::LOCALE_DOMAIN . '.header.created', 'j.n.Y G:i')
			->setSortable()
			->setFilterDate();
		$grid->getColumn('created')->headerPrototype->width = '15%';

		////////// Type //////////
		$grid->addColumnNumber('type', self::LOCALE_DOMAIN . '.header.type')
			->setCustomRender(__DIR__ . DIRECTORY_SEPARATOR . 'type.latte', ['localeDomain' => self::LOCALE_DOMAIN])
			->setFilterSelect([
				NULL => self::LOCALE_DOMAIN . '.all',
				Message::TYPE_USER => self::LOCALE_DOMAIN . '.types.users',
				Message::TYPE_DEALER => self::LOCALE_DOMAIN . '.types.dealers',
				Message::TYPE_SHOP => self::LOCALE_DOMAIN . '.types.shop',
			]);
		$grid->getColumn('type')->headerPrototype->width = '7%';

		////////// Locale //////////
		$grid->addColumnText('locale', self::LOCALE_DOMAIN . '.header.locale')
			->setCustomRender(function ($message) {
				if ($message->locale === NULL) {
					return $this->translator->translate('default.locales.all');
				}

				return $message->locale;
			})
			->setFilterSelect($this->localeFacade->getLocalesToSelect());
		$grid->getColumn('locale')->headerPrototype->width = '7%';

		////////// Status //////////
		$grid->addColumnNumber('status', self::LOCALE_DOMAIN . '.header.status')
			->setCustomRender(__DIR__ . DIRECTORY_SEPARATOR . 'status.latte', ['localeDomain' => self::LOCALE_DOMAIN])
			->setFilterSelect([
				NULL => self::LOCALE_DOMAIN . '.all',
				Message::STATUS_PAUSED => self::LOCALE_DOMAIN . '.status.paused',
				Message::STATUS_RUNNING => self::LOCALE_DOMAIN . '.status.running',
				Message::STATUS_SENT => self::LOCALE_DOMAIN . '.status.sent',
			]);
		$grid->getColumn('status')->headerPrototype->width = '8%';

		////////// Statistics //////////
		$grid->addColumnText('stats', self::LOCALE_DOMAIN . '.header.stats')
			->setCustomRender(function ($message) {
				$qbSent = $this->em->getRepository(Status::getClassName())
					->createQueryBuilder('s')
					->select('count(s.id)')
					->join('s.message', 'm')
					->setParameter('message', $message);

				$qbAll = clone $qbSent;

				$sent = $qbSent->where('m = :message AND s.status = :status')
					->setParameter('status', Message::STATUS_SENT)
					->getQuery()
					->getSingleScalarResult();

				$all = $qbAll->where('m = :message')
					->getQuery()
					->getSingleScalarResult();

				return $sent . '/' . $all;
			});
		$grid->getColumn('stats')->headerPrototype->width = '10%';

		////////// Actions //////////
		$grid->setActionWidth("15%");

		$grid->addActionEvent('run', self::LOCALE_DOMAIN . '.action.run', function ($messageId) {
			$message = $this->em->getRepository(Message::getClassName())->find($messageId);
			$this->newsletterFacade->run($message);

			if ($this->presenter->isAjax()) {
				$this->redrawControl();
			} else {
				$this->presenter->redirect('this');
			}
		})
			->setDisable(function ($message) {
				return $message->status === Message::STATUS_PAUSED ? FALSE : TRUE;
			})
			->setIcon('fa fa-play')
			->getElementPrototype()->class[] = 'ajax';

		$grid->addActionEvent('pause', self::LOCALE_DOMAIN . '.action.pause', function ($messageId) {
			$message = $this->em->getRepository(Message::getClassName())->find($messageId);
			$this->newsletterFacade->pause($message);

			if ($this->presenter->isAjax()) {
				$this->redrawControl();
			} else {
				$this->presenter->redirect('this');
			}
		})
			->setDisable(function ($message) {
				return $message->status === Message::STATUS_RUNNING ? FALSE : TRUE;
			})
			->setIcon('fa fa-pause')
			->getElementPrototype()->class[] = 'ajax';

		return $grid;
	}

}

interface IMessageGridFactory
{

	/** @return MessageGrid */
	function create();
}
