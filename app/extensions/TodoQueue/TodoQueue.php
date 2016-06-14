<?php

namespace App\Extensions;

use App\Model\Entity\Category;
use App\Model\Entity\TodoTask;
use App\Model\Facade\CategoryFacade;
use App\Model\Facade\StockFacade;
use App\Model\Repository\CategoryRepository;
use Exception;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Translation\Translator;
use Nette\Object;
use Nette\Utils\DateTime;

class TodoQueue extends Object
{

	const REFRESH_CATEGORY_CACHE = 'refresh-category-cache';

	const DO_IT_NOW = 'now';
	const DO_IT_IN_WHILE = '+5 minutes';
	const DO_IT_LATER = '+10 minutes';
	const DO_IT_MORE_LATER = '+25 minutes';

	/** @var EntityManager @inject */
	public $em;

	/** @var Translator @inject */
	public $translator;

	/** @var CategoryFacade @inject */
	public $categoryFacade;

	/** @var StockFacade @inject */
	public $stockFacade;

	public function todo($type, $late = self::DO_IT_LATER)
	{
		switch ($type) {
			case self::REFRESH_CATEGORY_CACHE:
				$taskRepo = $this->em->getRepository(TodoTask::getClassName());
				$task = $taskRepo->findOneByName($type);
				if (!$task) {
					$task = new TodoTask($type);
				}
				$task->runTime = new DateTime('+' . $late);
				$taskRepo->save($task);
				break;
			default:
				throw new TodoQueueException('Unknown todo type.');
		}
	}

	public function run()
	{
		$taskRepo = $this->em->getRepository(TodoTask::getClassName());
		foreach ($taskRepo->findAll() as $task) {
			if ($task->isRunnable()) {
				$this->doItNow($task);
				$taskRepo->delete($task);
				break;
			}
		}
	}

	private function doItNow(TodoTask $task)
	{
		switch ($task->name) {
			case self::REFRESH_CATEGORY_CACHE:
				$this->doRefreshCategoryCache();
				break;

			default:
				throw new TodoQueueException('Unknown todo type.');
		}
	}

	private function doRefreshCategoryCache()
	{
		$categoryRepo = $this->em->getRepository(Category::getClassName());
		$categoryRepo->clearResultCache(CategoryRepository::ALL_CATEGORIES_CACHE_ID);

		$categoryRepo->findAll(); // reload cache
	}

}

class TodoQueueException extends Exception
{

}
