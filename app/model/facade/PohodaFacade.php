<?php

namespace App\Model\Facade;

use App\Extensions\FilesManager;
use App\Extensions\Settings\SettingsStorage;
use App\Helpers;
use App\Model\Entity\PohodaItem;
use App\Model\Entity\PohodaStorage;
use App\Model\Entity\Special\XmlItem;
use App\Model\Entity\Stock;
use App\Model\Entity\Vat;
use App\Model\Repository\PohodaItemRepository;
use Exception;
use Kdyby\Doctrine\EntityManager;
use MissingSettingsException;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Object;
use Nette\Utils\DateTime;
use Nette\Utils\FileSystem;
use Nette\Utils\Finder;
use Nette\Utils\Random;
use Nette\Utils\Strings;
use Tracy\Debugger;
use XMLReader;

class PohodaFacade extends Object
{

	const STORE = 'store'; // skladová karta
	const SHORT_STOCK = 'short-stock'; // skladová zásoba
	const ANY_IMPORT = 'any-import'; // jakýkoli import skladů
	const ORDERS = 'orders'; // objednávky
	const DIR_FOR_IMPORT = 'files/pohoda-xml-import';
	const FOLDER_UPLOADED = 'uploaded';
	const FOLDER_PARSED = 'parsed';
	const LAST_DOWNLOAD = 'last-download'; // poslední stažení XML do PohodaConnectoru
	const LAST_UPDATE = 'last-update'; // poslední nahrání XML do přechodné Pohoda DB
	const LAST_CONVERT = 'last-convert'; // poslední převod produktů z Pohoda DB do vnitřní DB

	/** @var array */

	public $onDoneRecieveXml = [];

	/** @var array */
	public $onStartParseXml = [];

	/** @var array */
	public $onDoneParseXml = [];

	/** @var EntityManager @inject */
	public $em;

	/** @var FilesManager @inject */
	public $filesManager;

	/** @var SettingsStorage @inject */
	public $settings;

	public function updateFullProducts($limit = NULL, $offset = NULL)
	{
		/* @var $pohodaRepo PohodaItemRepository */
		$pohodaRepo = $this->em->getRepository(PohodaItem::getClassName());
		$stockRepo = $this->em->getRepository(Stock::getClassName());
		$language = $this->settings->modules->pohoda->language;
		if (!$language) {
			throw new MissingSettingsException('Pohoda language must be set.');
		}
		$conditions = [
			'isInternet' => 'true',
			'synchronized' => FALSE,
			'skipped' => FALSE,
		];
		$order = ['updatedAt' => 'DESC'];

		$limit = $limit === NULL ? 100 : $limit;
		$pohodaItems = $pohodaRepo->findBy($conditions, $order, $limit, $offset);
		$listedCount = 0;
		$changedCount = 0;
		/* @var $pohodaProduct PohodaItem */
		foreach ($pohodaItems as $pohodaProduct) {
			$listedCount++;
			$totalSumValue = $pohodaRepo->getSumCountGroupedBy($pohodaProduct->code);
			$totalCount = 0;
			if (is_array($totalSumValue) && array_key_exists(1, $totalSumValue)) {
				$totalCountRaw = (int)$totalSumValue[1];
				$totalCount = $totalCountRaw > 0 ? $totalCountRaw : 0;
			}

			/* @var $stock Stock */
			if ($pohodaProduct->code) {

				$stock = $stockRepo->findOneBy([
					'pohodaCode' => $pohodaProduct->code,
					'active' => TRUE,
					'deletedAt' => NULL,
				]);
				$change = FALSE;
				$skipFullActualize = FALSE;

				if (!$stock) {
					Debugger::log('SKIPPED Code: ' . $pohodaProduct->code, 'pohoda-synchronized-counts');
					$pohodaRepo->updateSkipped($pohodaProduct->code, TRUE);
					continue;
				}

				$isFromToday = $stock->createdAt >= DateTime::from('-24 hours');
				$isChangedFromShop = $stock->updatedPohodaDataAt > $pohodaProduct->updatedAt;
				if ($isChangedFromShop && !$isFromToday) {
					$skipFullActualize = TRUE;
				}

				if ($stock->quantity != $totalCount) {
					$stock->quantity = $totalCount;
					$change = TRUE;
				}

				if (!$skipFullActualize && $totalCount) {

					$translation = $stock->product->translateAdd($language);
					if ($translation->name != $pohodaProduct->name) {
						$translation->name = $pohodaProduct->name;
						$stock->product->mergeNewTranslations();
						$change = TRUE;
					}

					if (!$stock->purchasePrice || round($stock->purchasePrice->withoutVat, 2) != round($pohodaProduct->purchasingPrice, 2)) {
						$stock->purchasePrice = $pohodaProduct->purchasingPrice;
						$change = TRUE;
					}

					if (!$stock->price || round($stock->price->withoutVat, 2) != round($pohodaProduct->recountedSellingWithoutVat, 2)) {
						$stock->price = $pohodaProduct->recountedSellingWithoutVat;
						$change = TRUE;
					}

					if ($stock->barcode != $pohodaProduct->ean) {
						$stock->barcode = $pohodaProduct->ean;
						$change = TRUE;
					}

					$vat = $this->getVatFromPohodaString($pohodaProduct->sellingRateVAT);
					if ($vat && (!$stock->vat || $stock->vat->id != $vat->id)) {
						$stock->vat = $vat;
						$change = TRUE;
					}
				}

				if ($change) {
					if ($limit) {
						Debugger::log('UPDATED Code: ' . $stock->pohodaCode, 'pohoda-synchronized-counts');
					}
					$this->em->persist($stock);
					$this->em->flush();
					$changedCount++;
				}
				$pohodaRepo->updateSynchronized($pohodaProduct->code, TRUE);
			}
		}

		Debugger::log('LISTED: ' . $listedCount . '; UPDATED: ' . $changedCount, 'pohoda-synchronized-counts');
	}

	private function getVatFromPohodaString($string)
	{
		$vatRates = $this->settings->modules->pohoda->vatRates;
		switch ($string) {
			case PohodaItem::VALUE_VAT_HIGH:
			case PohodaItem::VALUE_VAT_LOW:
			case PohodaItem::VALUE_VAT_NONE:
				$value = $vatRates->$string;
				break;
			default:
				$value = $vatRates->{PohodaItem::VALUE_VAT_NONE};
				break;
		}
		$vatRepo = $this->em->getRepository(Vat::getClassName());
		return $vatRepo->findOneByValue($value);
	}

	public function getNewCode()
	{
		$pohodaRepo = $this->em->getRepository(PohodaItem::getClassName());
		$tries = 5;
		for ($i = 0; $i < $tries; $i++) {
			$newCodeLenght = $this->settings->modules->pohoda->newCodeLenght;
			$newCodeCharlist = $this->settings->modules->pohoda->newCodeCharlist;
			$newCode = Random::generate($newCodeLenght, $newCodeCharlist);
			$found = $pohodaRepo->findBy(['code' => $newCode]);
			if (!$found) {
				return $newCode;
			}
		}
		throw new UniqueConstraintViolationException('Generating unique code was failed.');
	}

	public function recieveStore($xml)
	{
		$type = self::STORE;
		$filename = $this->recieveXml($xml, $type);
		return $this->parseXml($filename, $type);
	}

	public function recieveShortStock($xml)
	{
		$type = self::SHORT_STOCK;
		$filename = $this->recieveXml($xml, $type);
		return $this->parseXml($filename, $type);
	}

	protected function recieveXml($xml, $type)
	{
		if ($xml) {
			$savedFilename = $this->createXml($xml, $type);
			$this->onDoneRecieveXml();
			return $savedFilename;
		} else {
			throw new Exception('XML file is empty');
		}
	}

	public function setLastSync($type, $sync = self::LAST_UPDATE)
	{
		$filename = $this->getFilenameLastSync($type, $sync);
		file_put_contents($filename, time());
	}

	public function getLastSync($type, $sync = self::LAST_UPDATE)
	{
		$time = NULL;
		switch ($type) {
			case self::STORE:
			case self::SHORT_STOCK:
			case self::ANY_IMPORT:
				$time = $this->getLastSyncDate(Strings::webalize($type), $sync);
				break;
		}
		return $time;
	}

	public function clearLastSync($type, $sync)
	{
		$filename = $this->getFilenameLastSync($type, $sync);
		FileSystem::delete($filename);
	}

	protected function parseXml($filename, $type, $startLine = NULL, $finishLine = NULL)
	{
		$productRepo = $this->em->getRepository(PohodaItem::getClassName());
		$storageRepo = $this->em->getRepository(PohodaStorage::getClassName());

		$this->onStartParseXml($type);

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
									$reader->name === 'stk:isSales' ||
									$reader->name === 'stk:isInternet'
								)
							) {
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
										)
									) {
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
										)
									) {
										$stockPriceItem->{$reader->name} = $reader->readString();
									}
								}
							}
						}
					} // stk:stockPriceItem
				} // product end

				if (isset($item->storage) && isset($item->storage->typ_id) && isset($item->storage->typ_ids)) { // update/add storage
					$storage = $storageRepo->find($item->storage->typ_id);
					if (!$storage) {
						$storage = new PohodaStorage($item->storage->typ_id);
					}
					$storage->name = $item->storage->typ_ids;
					$this->em->persist($storage);
				}

				// update/add product
				if (isset($item->stk_id)) {
					$product = $productRepo->find($item->stk_id);
					if (!$product && isset($storage) && isset($item->stk_code)) {
						$product = new PohodaItem($item->stk_id);
					}
				}
				if (isset($product) && $product) {
					if (isset($storage)) {
						$product->storage = $storage;
					}
					if (isset($item->stk_code)) {
						$product->code = $item->stk_code;
					}

					$optional = [
						'stk_name' => 'name',
						'stk_isSales' => 'isSales',
						'stk_isInternet' => 'isInternet',
						'stk_purchasingRateVAT' => 'purchasingRateVAT',
						'stk_purchasingPrice' => 'purchasingPrice',
						'stk_sellingRateVAT' => 'sellingRateVAT',
						'stk_sellingPrice' => 'sellingPrice',
						'stk_sellingPriceWithVAT' => 'sellingPriceWithVAT',
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
								$item->stockPrice1 = $priceItem->typ_price;
								$product->priceItem1 = $priceItem->typ_price;
							}
						}
					}

					// recount selling price
					$vatRates = $this->settings->modules->pohoda->vatRates;
					$sellingVatRate = $vatRates[$product->sellingRateVAT];
					if (isset($item->stk_sellingPrice) && isset($item->stk_sellingPriceWithVAT)) {
						if (!isset($item->stk_sellingRateVAT)) {
							list($product->sellingRateVAT, $sellingVatRate) = $this->recountVatRate($item->stk_sellingPrice, $item->stk_sellingPriceWithVAT);
						}
						$product->setRecountedSellingPrice($item->stk_sellingPrice, $item->stk_sellingPriceWithVAT, $sellingVatRate);
					} else if (isset($item->stockPrice1)) {
						$product->setRecountedSellingPrice(NULL, $item->stockPrice1, $sellingVatRate);
					}

					$product->resetSynchronize();
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

		$this->onDoneParseXml($type);

		$minusTime = '-' . $this->settings->modules->pohoda->removeParsedXmlOlderThan;
		$this->removeOlderParsedXml(DateTime::from($minusTime), $type);
		$this->moveXml($filename, self::FOLDER_PARSED, $type);
	}

	private function recountVatRate($withoutVat, $withVat)
	{
		$vatRates = (array)$this->settings->modules->pohoda->vatRates;
		asort($vatRates);

		if ($withoutVat >= $withVat) {
			return [PohodaItem::VALUE_VAT_NONE, 0];
		}

		$diff = $withVat - $withoutVat;
		$onePercent = $withoutVat / 100;
		$vatValue = round($diff / $onePercent);

		$previous = [];
		foreach ($vatRates as $key => $value) {
			if (count($previous)) {
				$previousValue = $previous[1];
				$middleValue = ($previousValue + $value) / 2;
				if ($vatValue < $middleValue) {
					return $previous;
				}
			}
			$previous = [$key, $value];
		}
		return $previous;
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

	protected function removeOlderParsedXml(DateTime $date, $type = NULL)
	{
		$folders = [];
		if ($type) {
			$folders[] = $type;
		} else {
			$folders[] = self::STORE;
			$folders[] = self::SHORT_STOCK;
		}
		$paths = [];
		foreach ($folders as $folder) {
			$paths[] = realpath($this->getDirForXml(self::FOLDER_PARSED, $folder));
		}
		foreach (Finder::findFiles('*.xml')->in($paths) as $filename => $file) {
			if (preg_match('/(\d+).xml$/', $filename, $matches)) {
				$xmlTime = DateTime::from($matches[1]);
				if ($xmlTime < $date) {
					FileSystem::delete($filename);
				}
			}
		}
	}

	protected function getRootDir()
	{
		return $this->filesManager->getDir(FilesManager::POHODA_IMPORT);
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
		return $this->getDirForXml($dir, $folder) . DIRECTORY_SEPARATOR . $filename;
	}

	protected function getFilenameLastSync($type, $sync)
	{
		$filename = Strings::webalize($type) . '.time';
		$dir = Helpers::getPath($this->getRootDir(), $sync);
		FileSystem::createDir($dir);
		return $dir . DIRECTORY_SEPARATOR . $filename;
	}

	protected function getLastSyncDate($type, $sync)
	{
		$filename = $this->getFilenameLastSync($type, $sync);
		if (is_file($filename)) {
			$content = file_get_contents($filename);
			if ($content) {
				return DateTime::from($content);
			}
		}
		return NULL;
	}

}
