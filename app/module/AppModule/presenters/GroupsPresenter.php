<?php

namespace App\AppModule\Presenters;

use App\Model\Entity\Group;

class GroupsPresenter extends BasePresenter
{

	/**
	 * @secured
	 * @resource('groups')
	 * @privilege('default')
	 */
	public function actionDefault()
	{
		$groupRepo = $this->em->getRepository(Group::getClassName());
		
		$this->template->groups = $groupRepo->findAll();
	}

}
