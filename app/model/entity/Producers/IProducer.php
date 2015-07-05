<?php

namespace App\Model\Entity;

interface IProducer
{

	public function isNew();

	public function __toString();
}
