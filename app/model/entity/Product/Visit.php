<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;
use Nette\Utils\DateTime;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\VisitRepository")
 *
 * @property string $ip
 * @property DateTime $visitedAt
 * @property Stock $stock
 * @property User $user
 */
class Visit extends BaseEntity
{

	use Identifier;

	/** @ORM\Column(type="string", nullable=false) */
	protected $ip;

	/** @ORM\ManyToOne(targetEntity="Stock", inversedBy="visits") */
	protected $stock;

	/** @ORM\ManyToOne(targetEntity="User", inversedBy="visits") */
	protected $user;

	/** @ORM\Column(type="datetime") */
	protected $visitedAt;

	public function __construct(Stock $stock, $ip)
	{
		$this->stock = $stock;
		$this->ip = $ip;
		$this->visitedAt = new DateTime();
		parent::__construct();
	}
}
