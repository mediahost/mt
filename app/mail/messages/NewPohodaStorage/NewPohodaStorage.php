<?php

namespace App\Mail\Messages\NewPohodaStorage;

use App\Mail\Messages\BaseMessage;
use App\Model\Entity\PohodaStorage;

class NewPohodaStorage extends BaseMessage
{
	
	protected function beforeSend()
	{
		$this->setSubject('Nový sklad v Pohodě');
		parent::beforeSend();
	}

	public function setPohoda(PohodaStorage $storage, $pohodaCode)
	{
		$this->pohodaStorage = $storage->name;
		$this->pohodaProductCode = $pohodaCode;

		return $this;
	}

}

interface INewPohodaStorageFactory
{

	/**
	 * @return NewPohodaStorage
	 */
	public function create();
}
