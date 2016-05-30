<?php

namespace App\Listeners\Model\Entity;

use App\Model\Entity\Product;
use App\Model\Entity\ProductTranslation;
use App\Model\Facade\StockFacade;
use Doctrine\ORM\Events;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Events\Subscriber;
use Kdyby\Translation\Translator;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Object;

class ProductListener extends Object implements Subscriber
{

	/** @var EntityManager @inject */
	public $em;

	/** @var IStorage @inject */
	public $cacheStorage;

	/** @var Translator @inject */
	public $translator;

	/** @var StockFacade @inject */
	public $stockFacade;

	public function getSubscribedEvents()
	{
		return array(
			Events::postUpdate,
		);
	}

	// <editor-fold desc="listeners redirectors">

	public function postUpdate($params)
	{
		$product = $this->getProductFromParams($params);
		if ($product) {
			if ($this->hasChangeName($params) || $this->hasChangeMainCategory($params)) {
				$this->clearCache($product);
				$this->generateUrls($product);
			}
			if ($this->hasDeleted($params)) {
				$this->clearCache($product);
			}
		}
	}

	// </editor-fold>

	private function hasChangeName($entity)
	{
		$uow = $this->em->getUnitOfWork();
		$changes = $uow->getEntityChangeSet($entity);
		if (is_array($changes) && array_key_exists('name', $changes)) {
			return TRUE;
		}
		return FALSE;
	}

	private function hasChangeMainCategory($entity)
	{
		$uow = $this->em->getUnitOfWork();
		$changes = $uow->getEntityChangeSet($entity);
		if (is_array($changes) && array_key_exists('mainCategory', $changes)) {
			return TRUE;
		}
		return FALSE;
	}

	private function hasDeleted($entity)
	{
		$uow = $this->em->getUnitOfWork();
		$changes = $uow->getEntityChangeSet($entity);
		if (is_array($changes) && array_key_exists('deletedAt', $changes)) {
			return TRUE;
		}
		return FALSE;
	}

	private function clearCache(Product $product)
	{
		$cache = new Cache($this->cacheStorage);
		$cache->clean([
			Cache::TAGS => [StockFacade::TAG_PRODUCT . $product->id],
		]);
	}

	private function generateUrls(Product $product)
	{
		$this->stockFacade->idToUrl($product->id, NULL, NULL, $product);
		$this->stockFacade->urlToId($product->getUrl(), NULL, NULL, $product);
	}

	/** @return Product|NULL */
	private function getProductFromParams($params)
	{
		if ($params instanceof ProductTranslation) {
			return $params->getTranslatable();
		} elseif ($params instanceof Product) {
			return $params;
		}
		return NULL;
	}

}
