<?php

namespace App\Components\Grids\User;

use App\Components\BaseControl;
use App\Extensions\Grido\BaseGrid;
use App\Helpers;
use App\Model\Entity\Role;
use App\Model\Entity\User;
use Grido\DataSources\Doctrine;
use Grido\Grid;
use Nette\Security\User as Identity;

class UsersGrid extends BaseControl
{

	/** @var Identity */
	private $identity;

	/** @return Grid */
	protected function createComponentGrid()
	{
		$grid = new BaseGrid();
		$grid->setTranslator($this->translator);
		$grid->setTheme(BaseGrid::THEME_METRONIC);

		$repo = $this->em->getRepository(User::getClassName());
		$qb = $repo->createQueryBuilder('u')
				->select('u, r')
				->leftJoin('u.roles', 'r');
		$grid->model = new Doctrine($qb, [
			'roles' => 'r.id'
		]);

		$grid->setDefaultSort([
			'id' => 'ASC',
			'mail' => 'ASC'
		]);

		$grid->addColumnNumber('id', 'ID #')
				->setSortable()
				->setFilterNumber();
		$grid->getColumn('id')->headerPrototype->width = '5%';

		$grid->addColumnEmail('mail', 'Mail')
				->setSortable()
				->setFilterText()
				->setSuggestion();

		$roleRepo = $this->em->getRepository(Role::getClassName());
		$grid->addColumnText('roles', 'Roles')
				->setSortable()
				->setFilterSelect($roleRepo->findPairs('name'));
		$grid->getColumn('roles')
				->setCustomRender(__DIR__ . '/tag.latte')
				->setCustomRenderExport(function ($item) {
					return Helpers::concatStrings(', ', $item->roles);
				});

		$grid->addActionHref('access', 'Access')
						->setIcon('fa fa-key')
						->setDisable(function($item) {
							return !$this->presenter->canAccess($this->identity, $item);
						});

		$grid->addActionHref('edit', 'Edit')
						->setIcon('fa fa-edit')
						->setDisable(function($item) {
							return !$this->presenter->canEdit($this->identity, $item);
						});

		$grid->addActionHref('delete', 'Delete')
						->setIcon('fa fa-trash-o')
						->setConfirm(function($item) {
							$message = $this->translator->translate('Are you sure you want to delete \'%s\'?');
							return sprintf($message, (string) $item);
						})
						->setDisable(function($item) {
							return !$this->presenter->canDelete($this->identity, $item);
						})
				->elementPrototype->class[] = 'red';
						
		$grid->setActionWidth("20%");

		$grid->setExport('users');

		return $grid;
	}

	public function setIdentity(Identity $identity)
	{
		$this->identity = $identity;
		return $this;
	}

}

interface IUsersGridFactory
{

	/** @return UsersGrid */
	function create();
}
