<?php

namespace Test\Examples;

use Kdyby\Doctrine\EntityRepository;
use Nette\DI\Container;
use Nette\Utils\DateTime;
use Test\Examples\Model\Entity\Loggable;
use Tester\Assert;

$container = require __DIR__ . '/../bootstrap.php';

/**
 * TEST: Loggable use
 * https://github.com/KnpLabs/DoctrineBehaviors/blob/master/tests/Knp/DoctrineBehaviors/ORM/LoggableTest.php
 *
 * @testCase
 * @phpVersion 5.4
 */
class LoggableUseTest extends BaseUse
{

	/** @var EntityRepository */
	private $loggableRepo;

	/** @var array */
	private $logs = [];

	public function __construct(Container $container)
	{
		parent::__construct($container);
		$this->loggableRepo = $this->em->getRepository(Loggable::getClassName());

		$loggedSubscriber = $this->getContainer()->getService('loggableSubscriber');
		$loggedSubscriber->setLoggerCallable([$this, 'log']);
	}

	protected function setUp()
	{
		parent::setUp();
		$this->updateSchema();
		$this->logs = [];
	}

	protected function tearDown()
	{
		parent::tearDown();
		$this->dropSchema();
	}

	/**
	 * @dataProvider dataProviderValues
	 */
	public function testShould_log_changeset_message_when_created($field, $value, $expected)
	{

		$entity = new Loggable();

		$set = "set" . ucfirst($field);
		$entity->$set($value);

		$this->em->persist($entity);
		$this->em->flush();

		Assert::count(2, $this->logs);
		Assert::same('Test\Examples\Model\Entity\Loggable #1 created', $this->logs[0]);
		Assert::same('Test\Examples\Model\Entity\Loggable #1 : property "' . $field . '" changed from "" to "' . $expected . '"', $this->logs[1]);
	}

	/**
	 * @dataProvider dataProviderValues
	 */
	public function testShould_log_changeset_message_when_updated($field, $value, $expected)
	{

		$entity = new Loggable();

		$this->em->persist($entity);
		$this->em->flush();

		$set = "set" . ucfirst($field);
		$entity->$set($value);

		$this->em->flush();

		Assert::count(3, $this->logs);
		Assert::same('Test\Examples\Model\Entity\Loggable #1 : property "' . $field . '" changed from "" to "' . $expected . '"', $this->logs[2]);
	}

	public function testShould_not_log_changeset_message_when_no_change()
	{
		$entity = new Loggable();

		$this->em->persist($entity);
		$this->em->flush();

		$entity->name = 'test2';
		$entity->name = null;
		$this->em->flush();

		Assert::count(2, $this->logs);
	}
	
    public function testShould_log_removal_message_when_deleted()
    {
		$entity = new Loggable();
		
        $this->em->persist($entity);
        $this->em->flush();
		
        $this->em->remove($entity);
        $this->em->flush();
		
        Assert::count(3, $this->logs);
		Assert::same('Test\Examples\Model\Entity\Loggable #1 removed', $this->logs[2]);
    }

	public function dataProviderValues()
	{
		return [
			[
				"name", "The name", "The name"
			],
			[
				"roles", array("x" => "y"), "an array"
			],
			[
				"date", new DateTime("2014-02-02 12:20:30"), "2014-02-02 12:20:30"
			],
		];
	}

	public function log($message)
	{
		$this->logs[] = $message;
	}

	protected function getClasses()
	{
		return [
			$this->em->getClassMetadata(Loggable::getClassName()),
		];
	}

}

$test = new LoggableUseTest($container);
$test->run();
