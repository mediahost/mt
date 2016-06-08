<?php

namespace App\Model\Facade;

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

	public function getFullnames($locale)
	{
		$pairs = $this->categoryRepo->findPairs($locale, 't.fullname');
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
						while ($reader->read()) {
							if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'CATEGORY_ID') {
								$id = $reader->readString();
								$save = in_array($id, $allowedCategories);
								$this->readCategory($reader, $locale, $save);
							}
						}
					}
				} // category end
			}
		} // READER END

		$this->categoryRepo->clearResultCache(HeurekaCategoryRepository::ALL_CATEGORIES_CACHE_ID . $locale);
	}

	private function readCategory(XMLReader &$reader, $locale, $save = TRUE)
	{
		while ($reader->read()) {
			if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->name === 'CATEGORY') {
				break;
			}
			if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'CATEGORY') {
				$this->readCategory($reader, $locale, $save);
			}

			if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'CATEGORY_ID') {
				$id = $reader->readString();
			}
			if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'CATEGORY_NAME') {
				$name = $reader->readString();
			}
			if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'CATEGORY_FULLNAME') {
				$fullname = $reader->readString();
			}
		}

		if ($save && isset($id) && isset($name) && isset($fullname)) {
			$category = $this->categoryRepo->find($id);
			if (!$category) {
				$category = new Category($locale, $id);
			}
			$category->setCurrentLocale($locale);
			$category->translateAdd($locale);
			$category->name = $name;
			$category->fullname = $fullname;
			$category->mergeNewTranslations();
			$this->categoryRepo->save($category);
		}
	}


}
