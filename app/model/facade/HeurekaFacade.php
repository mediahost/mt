<?php

namespace App\Model\Facade;

use App\Model\Entity\Heureka\Category;
use Doctrine\ORM\Query;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use Tracy\Debugger;
use XMLReader;

class HeurekaFacade extends Object
{

	/** @var EntityManager @inject */
	public $em;

	public function downloadCategories($url, $locale)
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
						$this->readCategory($reader, $locale);
					}
				} // category end
			}
		} // READER END
	}

	private function readCategory(XMLReader &$reader, $locale)
	{
		while ($reader->read()) {
			if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->name === 'CATEGORY') {
				break;
			}
			if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'CATEGORY') {
				$this->readCategory($reader, $locale);
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

		if (isset($id) && isset($name) && isset($fullname)) {
			$categoryRepo = $this->em->getRepository(Category::getClassName());
			$category = $categoryRepo->find($id);
			if (!$category) {
				$category = new Category($locale, $id);
			}
			$category->setCurrentLocale($locale);
			$category->translateAdd($locale);
			$category->name = $name;
			$category->fullname = $fullname;
			$category->mergeNewTranslations();
			$categoryRepo->save($category);
		}
	}


}
