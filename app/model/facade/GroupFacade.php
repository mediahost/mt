<?php

namespace App\Model\Facade;

use App\Model\Entity\Group;
use App\Model\Entity\Stock;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Nette\Object;

class GroupFacade extends Object
{

	/** @var EntityManager @inject */
	public $em;

	/** @var EntityRepository */
	private $groupRepo;

	/** @var array */
	private $allowedGroupLevels = [];

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->groupRepo = $this->em->getRepository(Group::getClassName());
		$this->allowedGroupLevels = Stock::getPriceProperties();
	}

	public function getLastUnusedLevel()
	{
		foreach ($this->allowedGroupLevels as $level => $property) {
			if ($this->isUnused($level)) {
				return $level;
			}
		}
		return NULL;
	}

	private function isUnused($level)
	{
		return (bool) ($this->groupRepo->findOneBy(['level' => $level]) === NULL);
	}

}
