<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 *
 * @property string $name
 */
class Role extends BaseEntity
{

	use Identifier;

	const GUEST = 'guest';
	const SIGNED = 'signed';
	const USER = 'user';
	const DEALER = 'dealer';
	const ADMIN = 'admin';
	const SUPERADMIN = 'superadmin';
	const ROLE_IS_SAME = 0;
	const ROLE_IS_UPPER = 1;
	const ROLE_IS_LOWER = -1;

	/** @ORM\Column(type="string", length=128) */
	protected $name;

	public function __construct($name = NULL)
	{
		if ($name) {
			$this->name = $name;
		}
		parent::__construct();
	}

	public function __toString()
	{
		return (string) $this->name;
	}

	public static function getMaxRole(array $roles)
	{
		usort($roles, [self::getClassName(), 'compareRoles']);
		$max = end($roles);
		if ($max instanceof Role) {
			$maxRole = $max;
		} else {
			$maxRole = new Role;
			$maxRole->name = (string) $max;
		}
		return $maxRole;
	}

	public static function compareRoles($roleA, $roleB)
	{
		$roleOrder = [
				self::GUEST,
				self::SIGNED,
				self::USER,
				self::DEALER,
				self::ADMIN,
				self::SUPERADMIN,
		];
		$roleAName = $roleA instanceof Role ? $roleA->name : (string) $roleA;
		$roleBName = $roleB instanceof Role ? $roleB->name : (string) $roleB;

		$roleAPosition = array_search($roleAName, $roleOrder);
		$roleBPosition = array_search($roleBName, $roleOrder);

		if ($roleAPosition == $roleBPosition) {
			return self::ROLE_IS_SAME;
		}
		return ($roleAPosition < $roleBPosition) ? self::ROLE_IS_LOWER : self::ROLE_IS_UPPER;
	}

}
