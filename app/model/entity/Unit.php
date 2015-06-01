<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\UnitRepository")
 *
 * @property string $name
 */
class Unit extends BaseTranslatable
{

	const PAIR = "pair";
	const PIECES = "pcs";
	const SET = "set";
	const DEFAULT_NAME = self::PIECES;

	use Model\Translatable\Translatable;

	public function __construct($name = self::DEFAULT_NAME, $currentLocale = NULL)
	{
		parent::__construct($currentLocale);
		switch ($name) {
			case self::PAIR:
			case self::PIECES:
			case self::SET:
				$this->name = $name;
				break;
			default:
				$this->name = self::DEFAULT_NAME;
				break;
		}
		parent::__construct();
	}

	public function __toString()
	{
		return (string) $this->name;
	}

	public static function getAllNames()
	{
		return [
			self::PAIR,
			self::PIECES,
			self::SET,
		];
	}

}
