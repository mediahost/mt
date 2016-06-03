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

	const TAG_ALL_CATEGORIES = 'all-categories';
	const TAG_CATEGORY = 'category_';

	/** @var EntityManager @inject */
	public $em;

	/** @var Translator @inject */
	public $translator;

	/** @var IStorage @inject */
	public $cacheStorage;

	/** @var CategoryRepository */
	private $categoryRepo;

	/** @var array */
	private $ids = [];

	/** @var array */
	private $urls = [];

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

	public function urlToId($uri, Request $request = NULL, $locale = NULL, Category $category = NULL)
	{
		$locale = $this->getLocale($request, $locale);
		$hash = $this->createCacheHash($uri, $locale);

		if (!$category && isset($this->ids[$hash])) {
			return $this->ids[$hash];
		}

		$cache = $this->getCache();
		$id = $cache->load($hash);
		if (!$id) {
			$localeArr = $locale == $this->translator->getDefaultLocale() ? $locale : [$locale, $this->translator->getDefaultLocale()];
			$category = $category ? $category : $this->categoryRepo->findOneByUrl($uri, $localeArr);
			if ($category) {
				$id = $category->id;
				$categoryTags = $this->getCategoryTags($category);
				$this->ids[$hash] = $id;
				$cache->save($hash, $id, [Cache::TAGS => [
					self::TAG_ALL_CATEGORIES,
				] + $categoryTags]);
			}
		}
		return $id;
	}

	public function idToUrl($id, Request $request = NULL, $locale = NULL, Category $category = NULL)
	{
		$locale = $this->getLocale($request, $locale);
		$hash = $this->createCacheHash($id, $locale);

		if (!$category && isset($this->urls[$hash])) {
			return $this->urls[$hash];
		}

		$cache = $this->getCache();
		$url = $cache->load($hash);
		if (!$url) {
			$category = $category ? $category : $this->categoryRepo->find($id);
			if ($category) {
				$category->setCurrentLocale($locale);
				$url = $category->getUrl();
				$categoryTags = $this->getCategoryTags($category);

				$this->urls[$hash] = $url;
				$cache->save($hash, $url, [Cache::TAGS => [
						self::TAG_ALL_CATEGORIES,
					] + $categoryTags]);
			}
		}
		return $url;
	}

	/** @return Cache */
	public function getCache()
	{
		$cache = new Cache($this->cacheStorage, get_class($this));
		return $cache;
	}

	private function createCacheHash($value, $locale)
	{
		return md5($locale . $value);
	}

	private function getCategoryTags(Category $category)
	{
		$tags = [];
		foreach ($category->getPathWithThis() as $item) {
			$tags[] = self::TAG_CATEGORY . $item->id;
		}
		return $tags;
	}

	private function getLocale(Request $request = NULL, $locale = NULL)
	{
		$locale = $request ? $request->getParameter(RouterFactory::LOCALE_PARAM_NAME) : $locale;
		if (!$locale) {
			$locale = $this->translator->getDefaultLocale();
		}
		return $locale;
	}

}
