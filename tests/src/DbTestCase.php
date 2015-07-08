<?php

namespace Test;

use Doctrine\ORM\Tools\SchemaTool;
use Kdyby\Doctrine\Connection;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\Helpers;
use Kdyby\TesterExtras\Bootstrap;

abstract class DbTestCase extends BaseTestCase
{

	/** @var EntityManager @inject */
	public $em;

	/** @var Connection @inject */
	public $connection;

	/** @var SchemaTool */
	protected $schemaTool;

	protected function updateSchema()
	{
		if (!$this->schemaTool instanceof SchemaTool) {
			$this->schemaTool = new SchemaTool($this->em);
		}
//		$this->setOwnDb();
		$this->schemaTool->updateSchema($this->getClasses());
	}

	protected function setOwnDb()
	{
		Bootstrap::setupDoctrineDatabase($this->getContainer(), [], 'mt');
	}

	protected function importDbDataFromFile($file)
	{
		Helpers::loadFromFile($this->connection, $file);
	}

	protected function dropSchema()
	{
		if (!$this->schemaTool instanceof SchemaTool) {
			$this->schemaTool = new SchemaTool($this->em);
		}
		$this->schemaTool->dropSchema($this->getClasses());
		$this->em->clear();
	}

	protected function getClasses()
	{
		return $this->em->getMetadataFactory()->getAllMetadata();
	}

}
