<?php

namespace App\Listeners\Model\Entity;

use App\Model\Facade\PageFacade;
use Doctrine\ORM\Events;
use Kdyby\Events\Subscriber;
use Nette\Caching\Cache;
use Nette\Object;

class PageListener extends Object implements Subscriber
{

	/** @var PageFacade @inject */
	public $pageFacade;

	public function getSubscribedEvents()
	{
		return array(
			Events::prePersist,
			Events::preUpdate,
			Events::postRemove,
		);
	}

	// <editor-fold desc="listeners redirectors">

	public function prePersist($params)
	{
		$this->clearCache();
	}

	public function preUpdate($params)
	{
		$this->clearCache();
	}

	public function postRemove($params)
	{
		$this->clearCache();
	}

	// </editor-fold>

	private function clearCache()
	{
		$this->pageFacade->getCache()->clean([
			Cache::TAGS => [PageFacade::TAG_ALL_PAGES],
		]);
	}

}
