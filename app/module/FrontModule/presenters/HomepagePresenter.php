<?php

namespace App\FrontModule\Presenters;

use App\Model\Facade\PohodaFacade;

class HomepagePresenter extends BasePresenter
{
	
	/** @var PohodaFacade @inject */
	public $pohodaFacade;

	public function actionDefault()
	{
		$this->showSlider = TRUE;
		$this->showBrands = TRUE;
//		$this->showSteps = FALSE;
	}

	public function renderDefault()
	{
		
	}
	
	public function actionTest()
	{
//		$xml = file_get_contents('./short_stock_20150715015849.xml');
//		$this->pohodaFacade->recieveShortStock($xml);
		$this->terminate();
	}
	
	public function actionTestFull()
	{
//		$xml = file_get_contents('./stock_20150721070056.xml');
//		$this->pohodaFacade->recieveStore($xml);
		$xml = file_get_contents('./short_stock_20150715015849.xml');
		$this->pohodaFacade->recieveShortStock($xml);
//		var_dump($this->pohodaFacade->getLastUpdate(PohodaFacade::TYPE_SHORT_STOCK));
//		$this->pohodaFacade->removeOlderParsedXml(\Nette\Utils\DateTime::from('2015-07-22 13:57:50'));
//		$time = $this->pohodaFacade->getLastSync(PohodaFacade::ALL_PRODUCTS, PohodaFacade::LAST_UPDATE);
//		\Tracy\Debugger::barDump($time);
		$this->terminate();
	}

}
