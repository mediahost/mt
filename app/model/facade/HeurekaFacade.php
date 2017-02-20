<?php

namespace App\Model\Facade;

use App\Helpers;
use App\Model\Entity\Heureka\Category;
use App\Model\Repository\HeurekaCategoryRepository;
use Doctrine\ORM\Query;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use XMLReader;

class HeurekaFacade extends Object
{

	/** @var EntityManager @inject */
	public $em;

	/** @var HeurekaCategoryRepository */
	private $categoryRepo;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->categoryRepo = $this->em->getRepository(Category::getClassName());
	}

	public function getFullnames($locale, $order = 'ASC')
	{
		$pairs = $this->categoryRepo->findPairs($locale, 't.fullname', ['t.fullname' => $order]);
		return $pairs;
	}

	public function downloadCategories($url, $locale, array $allowedCategories = [])
	{
		$reader = new XMLReader();
		$reader->open($url);

		while ($reader->read()) {
			if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'HEUREKA') {
				while ($reader->read()) {
					if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->name === 'HEUREKA') {
						break;
					}
					if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'CATEGORY') {
						switch ($locale) {
							case 'cs':
								$names = [0 => 'Heureka.cz'];
								break;
							case 'sk':
								$names = [0 => 'Heureka.sk'];
								break;
							default:
								$names = [];
								break;
						}
						while ($reader->read()) {
							if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'CATEGORY_ID') {
								$id = $reader->readString();
								$save = in_array($id, $allowedCategories);
								$this->readCategory($reader, $locale, $save, $names);
							}
						}
					}
				} // category end
			}
		} // READER END

		$this->categoryRepo->clearResultCache(HeurekaCategoryRepository::ALL_CATEGORIES_CACHE_ID . $locale);
	}

	private function readCategory(XMLReader &$reader, $locale, $save = TRUE, array &$names = [], $deep = 1)
	{
		while ($reader->read()) {
			if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->name === 'CATEGORY') {
				break;
			}
			if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'CATEGORY') {
				$this->readCategory($reader, $locale, $save, $names, $deep + 1);
			}

			if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'CATEGORY_ID') {
				$id = $reader->readString();
			}
			if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'CATEGORY_NAME') {
				$name = $reader->readString();
				if ($save) {
					$names[$deep] = $name;
				}
			}
			if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'CATEGORY_FULLNAME') {
				$fullname = $reader->readString();
			}
		}

		if ($save && isset($id) && !empty($name)) {
			if (empty($fullname)) {
				$fullname = Helpers::concatStrings(' | ', $names);
			}
			unset($names[$deep]);

			$category = $this->categoryRepo->find($id);
			if (!$category) {
				$category = new Category($locale, $id);
			}
			$category->setCurrentLocale($locale);
			$translation = $category->translate($locale);
			if (!$translation->id) {
				$translation = $category->translateAdd($locale);
			}
			$translation->name = $name;
			$translation->fullname = $fullname;
			$category->mergeNewTranslations();
			$this->categoryRepo->save($category);
		}
	}


}
