<?php

namespace App\Model\Facade\Exception;

use Exception;

class FacadeException extends Exception
{
	
}

class BasketFacadeException extends FacadeException
{
	
}

class InsufficientQuantityException extends BasketFacadeException
{

	public function __construct($message = NULL, $code = NULL, $previous = NULL)
	{
		if (!$message) {
			$message = 'Requested quantity is bigger than product count in store';
		}
		parent::__construct($message, $code, $previous);
	}

}

class MissingItemException extends BasketFacadeException
{

	public function __construct($message = NULL, $code = NULL, $previous = NULL)
	{
		if (!$message) {
			$message = 'Requested product is not in basket';
		}
		parent::__construct($message, $code, $previous);
	}

}
