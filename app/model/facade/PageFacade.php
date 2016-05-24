<?php

namespace App\Model\Facade;

use App\Model\Entity\Page;
use App\Model\Repository\PageRepository;
use App\Router\RouterFactory;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Translation\Translator;
use Nette\Application\Request;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Object;

class PageFacade extends Object
{

	const KEY_ALL_SLUGS = 'page-slugs';
	const TAG_ALL_PAGES = 'all-pages';

	/** @var EntityManager @inject */
	public $em;

	/** @var Translator @inject */
	public $translator;

	/** @var IStorage @inject */
	public $cacheStorage;

	/** @var PageRepository */
	private $pageRepo;

	/** @var array */
	private $slugs = [];

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->pageRepo = $this->em->getRepository(Page::getClassName());
	}

	public function slugToId($uri, Request $request)
	{
		$locale = $request->getParameter(RouterFactory::LOCALE_PARAM_NAME);
		$slugs = $this->getSlugs($locale);
		$id = array_search($uri, $slugs);
		if ($id) {
			return $id;
		}
		return NULL;
	}

	public function idToSlug($id, Request $request)
	{
		$locale = $request->getParameter(RouterFactory::LOCALE_PARAM_NAME);
		$slugs = $this->getSlugs($locale);
		if (array_key_exists($id, $slugs)) {
			return $slugs[$id];
		}
		return NULL;
	}

	/** @return array */
	private function getSlugs($locale = NULL)
	{
		if ($locale === NULL) {
			$locale = $this->translator->getDefaultLocale();
		}
		if (array_key_exists($locale, $this->slugs)) {
			return $this->slugs[$locale];
		}

		$cache = $this->getCache();
		$cacheKey = self::KEY_ALL_SLUGS . '_' . $locale;

		$slugs[$locale] = $cache->load($cacheKey);
		if (!$slugs[$locale]) {
			$slugs[$locale] = $this->getLocaleSlugsArray($locale);
			$cache->save($cacheKey, $slugs[$locale], [Cache::TAGS => [self::TAG_ALL_PAGES]]);
		}
		$this->slugs[$locale] = $slugs[$locale];

		return $slugs[$locale];
	}

	/** @return array */
	private function getLocaleSlugsArray($locale)
	{
		$localeSlugs = [];
		$pages = $this->pageRepo->findAll();
		foreach ($pages as $page) {
			if (!$page->isInterLink()) {
				$page->setCurrentLocale($locale);
				$localeSlugs[$page->id] = $page->slug;
			}
		}
		return $localeSlugs;
	}

	/** @return Cache */
	public function getCache()
	{
		$cache = new Cache($this->cacheStorage, get_class($this));
		return $cache;
	}

}
