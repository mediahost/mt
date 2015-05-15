<?php

namespace App\Extensions\Settings\Model\Service;

/**
 * @property-read int $length Length of password
 */
class PasswordService extends BaseService
{

	public function getLength()
	{
		return (int) $this->defaultStorage->passwords->length;
	}

}
