<?php

namespace App\Model\Facade;

use App\Model\Entity\Parameter;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Nette\Object;

class ParameterFacade extends Object
{

	/** @var EntityManager @inject */
	public $em;

	/** @var EntityRepository */
	private $paramRepo;

	/** @var array */
	private $allowedParameterTypes = [];

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->paramRepo = $this->em->getRepository(Parameter::getClassName());
		$this->allowedParameterTypes = Parameter::getProductTypesWithCodes();
	}

	public function getLastUnusedCode($type)
	{
		if (array_key_exists($type, $this->allowedParameterTypes)) {
			foreach ($this->allowedParameterTypes[$type] as $number => $code) {
				if ($this->isUnused($code)) {
					return $code;
				}
			}
		}
		return NULL;
	}
	
	private function isUnused($code)
	{
		return (bool) ($this->paramRepo->findOneBy(['code' => $code]) === NULL);
	}

}
