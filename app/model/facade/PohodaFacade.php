<?php

namespace App\Model\Facade;

use App\Helpers;
use Exception;
use Kdyby\Doctrine\EntityManager;
use Nette\DI\Container;
use Nette\Object;
use Nette\Utils\FileSystem;
use Nette\Utils\Strings;

class PohodaFacade extends Object
{

	/** @var array */
	public $onRecieveXml = [];

	/** @var EntityManager @inject */
	public $em;

	/** @var Container @inject */
	public $container;

	public function recieveXml($xml, $filename)
	{
		if ($xml) {
			$this->saveXml($xml, $filename);
			$this->onRecieveXml($xml);
		} else {
			throw new Exception('XML file is empty');
		}
	}

	protected function saveXml($xml, $filename)
	{
		$time = time();
		$filename = Strings::webalize($filename) . "_{$time}.xml";
		$dir = Helpers::getPath($this->container->parameters['appDir'], '../files/pohoda-xml-import/uploaded');
		FileSystem::createDir($dir);
		file_put_contents($dir . '/' . $filename, $xml);
	}

}
