<?php

namespace App\AppModule\Presenters;

use App\BaseModule\Presenters\BasePresenter as BaseBasePresenter;

abstract class BasePresenter extends BaseBasePresenter
{
	public function handleToggleSidebar($value)
	{
		$this->user->identity->sidebarClosed = (bool) $value;
		
		if ($this->user->isLoggedIn()) {
			$this->em->flush($this->user->identity);
		}
		
		$this->redrawControl();
	}
}
