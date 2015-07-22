<?php

namespace App\Model\Facade;

use App\Helpers;
use App\Model\Entity\PohodaItem;
use App\Model\Entity\PohodaStorage;
use App\Model\Entity\Special\XmlItem;
use Exception;
use Kdyby\Doctrine\EntityManager;
use Nette\DI\Container;
use Nette\Object;
use Nette\Utils\DateTime;
use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use XMLReader;

class PohodaFacade extends Object
{

	const TYPE_STORE = 'store';
	const TYPE_SHORT_STOCK = 'short_stock';
	const DIR_FOR_IMPORT = 'files/pohoda-xml-import';
	const FOLDER_UPLOADED = 'uploaded';
	const FOLDER_PARSED = 'parsed';
	const DATA_STRING = 'string';
	const DATA_INT = 'int';
	const DATA_BOOL = 'bool';
	const DATA_FLOAT = 'float';

	/** @var array */
	public $onRecieveXml = [];

	/** @var array */
	public $onParseXml = [];

	/** @var EntityManager @inject */
	public $em;

	/** @var Container @inject */
	public $container;

	public function recieveStore($xml)
	{
		$type = self::TYPE_STORE;
		$filename = $this->recieveXml($xml, $type);
		return $this->parseXml($filename, $type);
	}

	public function recieveShortStock($xml)
	{
		$type = self::TYPE_SHORT_STOCK;
		$filename = $this->recieveXml($xml, $type);
		return $this->parseXml($filename, $type);
	}

	public function getLastUpdate($type)
	{
		$time = NULL;
		switch ($type) {
			case self::TYPE_STORE:
			case self::TYPE_SHORT_STOCK:
				$time = $this->getLastUpdateDate(Strings::webalize($type));
				break;
		}
		return $time;
	}

	protected function recieveXml($xml, $type)
	{
		if ($xml) {
			$savedFilename = $this->createXml($xml, $type);
			$this->onRecieveXml();
			return $savedFilename;
		} else {
			throw new Exception('XML file is empty');
		}
	}

	protected function parseXml($filename, $type, $startLine = NULL, $finishLine = NULL)
	{
		ini_set('max_execution_time', 60);
		ini_set('memory_limit', '300M');

		$productRepo = $this->em->getRepository(PohodaItem::getClassName());
		$storageRepo = $this->em->getRepository(PohodaStorage::getClassName());

		$reader = new XMLReader();
		$reader->open($filename);

		$line = 0;
		$counter = 0;
		while ($reader->read()) {
			if ($finishLine && $line >= $finishLine) {
				break;
			}

			if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'lStk:stock') { // products start
				$line++;
				if ($startLine && $line < $startLine) {
					continue;
				}
				$item = new XmlItem();
				while ($reader->read()) { // product start
					if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->name === 'lStk:stock') {
						break;
					}

					if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'stk:stockHeader') {
						while ($reader->read()) {
							if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->name === 'stk:stockHeader') {
								break;
							}
							if ($reader->nodeType === XMLReader::ELEMENT && (
									$reader->name === 'stk:id' ||
									$reader->name === 'stk:code' ||
									$reader->name === 'stk:name' ||
									$reader->name === 'stk:EAN' ||
									$reader->name === 'stk:count' ||
									$reader->name === 'stk:countReceivedOrders' ||
									$reader->name === 'stk:purchasingPrice' ||
									$reader->name === 'stk:sellingPrice' ||
									$reader->name === 'stk:sellingPriceWithVAT' ||
									$reader->name === 'stk:purchasingRateVAT' ||
									$reader->name === 'stk:sellingRateVAT' ||
									$reader->name === 'stk:isSales'
									)) {
								$item->{$reader->name} = $reader->readString();
							}
							if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'stk:storage') {
								$item->storage = new XmlItem();
								while ($reader->read()) {
									if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->name === 'stk:storage') {
										break;
									}
									if ($reader->nodeType === XMLReader::ELEMENT && (
											$reader->name === 'typ:id' ||
											$reader->name === 'typ:ids'
											)) {
										$item->storage->{$reader->name} = $reader->readString();
									}
								}
							}
						}
					} // stk:stockHeader

					if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'stk:stockPriceItem') {
						$item->stockPrice = [];
						while ($reader->read()) {
							if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->name === 'stk:stockPriceItem') {
								break;
							}
							// stk:stockPrice
							if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'stk:stockPrice') {
								$item->stockPrice[] = $stockPriceItem = new XmlItem();
								while ($reader->read()) {
									if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->name === 'stk:stockPrice') {
										break;
									}
									if ($reader->nodeType === XMLReader::ELEMENT && (
											$reader->name === 'typ:id' ||
											$reader->name === 'typ:ids' ||
											$reader->name === 'typ:price'
											)) {
										$stockPriceItem->{$reader->name} = $reader->readString();
									}
								}
							}
						}
					} // stk:stockPriceItem
				} // product end
				// update/add storage
				if (isset($item->storage) && isset($item->storage->typ_id) && isset($item->storage->typ_ids)) {
					$storage = $storageRepo->find($item->storage->typ_id);
					if (!$storage) {
						$storage = new PohodaStorage($item->storage->typ_id);
					}
					$storage->name = $item->storage->typ_id;
					$this->em->persist($storage);
				}

				// update/add product
				if (isset($item->stk_id) && isset($item->stk_code) && isset($storage)) {
					$product = $productRepo->find($item->stk_id);
					if (!$product) {
						$product = new PohodaItem($item->stk_id);
					}
					$product->code = $item->stk_code;
					$product->storage = $storage;

					$optional = [
						'stk_name' => 'name',
						'stk_isSales' => 'isSales',
						'stk_purchasingRateVAT' => 'purchasingRateVAT',
						'stk_sellingRateVAT' => 'sellingRateVAT',
						'stk_purchasingPrice' => 'purchasingPrice',
						'stk_sellingPrice' => 'sellingPrice',
						'stk_count' => 'count',
						'stk_countReceivedOrders' => 'countReceivedOrders',
					];
					foreach ($optional as $itemKey => $productKey) {
						if (isset($item->$itemKey) && isset($product->$productKey)) {
							$product->$productKey = $item->$itemKey;
						}
					}
					if (isset($item->stockPrice) && is_array($item->stockPrice)) {
						foreach ($item->stockPrice as $priceItem) {
							if (isset($priceItem->typ_id) && $priceItem->typ_id === '1') {
								$product->priceItem1 = $priceItem->typ_price;
							}
						}
					}

					$this->em->persist($product);
					$counter++;
				}

				if ($counter % 1000 === 0) {
					$this->em->flush();
					$this->em->clear();
				}
			} // products end
		} // READER END
		$this->em->flush();
		$reader->close();

		$this->onParseXml();

		$this->setLastUpdate($type);
		$this->moveXml($filename, self::FOLDER_PARSED, $type);
	}

	protected function createXml($xml, $type)
	{
		$filename = $this->getFilenameForXml(self::FOLDER_UPLOADED, Strings::webalize($type));
		file_put_contents($filename, $xml);
		return $filename;
	}

	protected function moveXml($file, $folder, $type)
	{
		$dest = $this->getFilenameForXml($folder, Strings::webalize($type));
		FileSystem::copy($file, $dest);
		FileSystem::delete($file);
		return $dest;
	}

	protected function setLastUpdate($type)
	{
		$filename = $this->getFilenameLastUpdate($type);
		file_put_contents($filename, time());
	}

	protected function getRootDir()
	{
		$root = Helpers::getPath($this->container->parameters['appDir'], '..');
		$rootPohoda = Helpers::getPath($root, self::DIR_FOR_IMPORT);
		FileSystem::createDir($rootPohoda);

		return $rootPohoda;
	}

	protected function getDirForXml($dir, $folder)
	{
		$root = $this->getRootDir();
		$path = Helpers::getPath($root, $dir, $folder);
		FileSystem::createDir($path);

		return $path;
	}

	protected function getFilenameForXml($dir, $folder)
	{
		$filename = time() . '.xml';
		return $this->getDirForXml($dir, $folder) . '/' . $filename;
	}

	protected function getFilenameLastUpdate($type)
	{
		$filename = Strings::webalize($type) . '.time';
		return $this->getRootDir() . '/' . $filename;
	}

	protected function getLastUpdateDate($type)
	{
		$filename = $this->getFilenameLastUpdate($type);
		if (is_file($filename)) {
			$content = file_get_contents($filename);
			if ($content) {
				return new DateTime($content);
			}
		}
		return NULL;
	}

}
