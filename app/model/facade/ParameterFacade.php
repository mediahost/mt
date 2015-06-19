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
		$this->allowedParameterTypes = $this->parametersToArray(Parameter::getProductTypes());
	}

	public function getLastUnusedNumber($type)
	{
		if (array_key_exists($type, $this->allowedParameterTypes)) {
			foreach ($this->allowedParameterTypes[$type] as $number) {
				if ($this->isUnused($type . $number)) {
					return $number;
				}
			}
		}
		return NULL;
	}
	
	private function isUnused($param)
	{
		return (bool) ($this->paramRepo->findOneBy(['type' => $param]) === NULL);
	}
	
	private function parametersToArray($parameters)
	{
		$array = [];
		foreach ($parameters as $parameter) {
			if (preg_match('/^(\w)(\d+)$/', $parameter, $matches)) {
				$array[$matches[1]][] = $matches[2];
			}
		}
		return $array;
	}

}
