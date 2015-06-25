<?php

namespace App\Extensions\Csv\Exceptions;

use Exception;

abstract class ParserException extends Exception
{
	
}

class InternalException extends ParserException
{
	
}

class BeforeProcessException extends ParserException
{
	
}

class WhileProcessException extends ParserException
{

	/** @var array */
	private $executed;

	public function __construct(array $executed = NULL, $message = NULL)
	{
		$this->executed = $executed;
		parent::__construct($message);
	}

	public function getExecuted()
	{
		return $this->executed;
	}

}
