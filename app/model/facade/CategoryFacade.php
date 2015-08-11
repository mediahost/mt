<?php

namespace App\Model\Facade;

use App\Model\Entity\Category;
use App\Model\Repository\CategoryRepository;
use App\Router\RouterFactory;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Translation\Translator;
use Nette\Application\Request;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Object;

class CategoryFacade extends Object
{
	
	const KEY_ALL_URLS = 'category-urls';
	const TAG_ALL_CATEGORIES = 'all-categories';

	/** @var EntityManager @inject */
	public $em;

	/** @var Translator @inject */
	public $translator;

	/** @var IStorage @inject */
	public $cacheStorage;

	/** @var CategoryRepository */
	private $categoryRepo;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->categoryRepo = $this->em->getRepository(Category::getClassName());
	}

	public function getCategoriesList($locale = NULL)
	{
		if ($locale === NULL) {
			$locale = $this->translator->getDefaultLocale();
		}
		$categories = [];
		foreach ($this->categoryRepo->findAll() as $category) {
			/* @var $category Category */
			$category->setCurrentLocale($locale);
			$categories[$category->id] = $category->treeName;
		}
		@uasort($categories, 'strcoll');
		return $categories;
	}

	public function urlToId($uri, Request $request)
	{
		$locale = $request->getParameter(RouterFactory::LOCALE_PARAM_NAME);
		$slugs = $this->getUrls($locale);
		$slug = array_search($uri, $slugs);
		if ($slug) {
			return $slug;
		}
		return NULL;
	}

	public function idToUrl($id, Request $request)
	{
		$locale = $request->getParameter(RouterFactory::LOCALE_PARAM_NAME);
		$slugs = $this->getUrls($locale);
		if (array_key_exists($id, $slugs)) {
			return $slugs[$id];
		}
		return NULL;
	}

	/** @return array */
	private function getUrls($locale = NULL)
	{
		if ($locale === NULL) {
			$locale = $this->translator->getDefaultLocale();
		}
		
		$cache = $this->getCache();
		$cacheKey = self::KEY_ALL_URLS . '_' . $locale;

		$urls[$locale] = $cache->load($cacheKey);
		if (!$urls[$locale]) {
			$urls[$locale] = $this->getLocaleUrlsArray($locale);
			$cache->save($cacheKey, $urls[$locale], [Cache::TAGS => [self::TAG_ALL_CATEGORIES]]);
		}

		return $urls[$locale];
	}

	/** @return array */
	private function getLocaleUrlsArray($locale)
	{
		$localeUrls = [];
		$categories = $this->categoryRepo->findAll();
		foreach ($categories as $category) {
			$category->setCurrentLocale($locale);
			$localeUrls[$category->id] = $category->url;
		}
		return $localeUrls;
	}
	
	/** @return Cache */
	public function getCache()
	{
		$cache = new Cache($this->cacheStorage, get_class($this));
		return $cache;
	}

}
