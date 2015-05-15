<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * Page design settings (for Metronic)
 * @ORM\Entity
 *
 * @property-read string $color
 * @property-read boolean $layoutBoxed
 * @property-read boolean $containerBgSolid
 * @property-read boolean $headerFixed
 * @property-read boolean $footerFixed
 * @property-read boolean $sidebarClosed
 * @property-read boolean $sidebarFixed
 * @property-read boolean $sidebarReversed
 * @property-read boolean $sidebarMenuHover
 * @property-read boolean $sidebarMenuLight
 * @property-read array $notNullValuesArray
 */
class PageDesignSettings extends BaseEntity
{

	use Identifier;

	/** @ORM\Column(type="string", length=50, nullable=true) */
	protected $color;

	/** @ORM\Column(type="boolean", nullable=true) */
	protected $layoutBoxed;

	/** @ORM\Column(type="boolean", nullable=true) */
	protected $containerBgSolid;

	/** @ORM\Column(type="boolean", nullable=true) */
	protected $headerFixed;

	/** @ORM\Column(type="boolean", nullable=true) */
	protected $footerFixed;

	/** @ORM\Column(type="boolean", nullable=true) */
	protected $sidebarClosed;

	/** @ORM\Column(type="boolean", nullable=true) */
	protected $sidebarFixed;

	/** @ORM\Column(type="boolean", nullable=true) */
	protected $sidebarReversed;

	/** @ORM\Column(type="boolean", nullable=true) */
	protected $sidebarMenuHover;

	/** @ORM\Column(type="boolean", nullable=true) */
	protected $sidebarMenuLight;

	public function setValues(array $values)
	{
		foreach ($values as $property => $value) {
			if ($this->getReflection()->hasProperty($property)) {
				$this->$property = $value;
			}
		}
		return $this;
	}

	public function getNotNullValuesArray()
	{
		return $this->toArray(TRUE);
	}

	public function toArray($onlyNotNull = FALSE)
	{
		$array = [];
		foreach ($this->getReflection()->getProperties() as $property) {
			if (!$onlyNotNull || ($onlyNotNull && $this->{$property->name} !== NULL)) {
				$array[$property->name] = $this->{$property->name};
			}
		}
		return $array;
	}

	public function append(PageDesignSettings $imported, $rewriteExisting = FALSE)
	{
		if ($rewriteExisting || $this->color === NULL) {
			$this->color = $imported->color;
		}
		if ($rewriteExisting || $this->containerBgSolid === NULL) {
			$this->containerBgSolid = $imported->containerBgSolid;
		}
		if ($rewriteExisting || $this->headerFixed === NULL) {
			$this->headerFixed = $imported->headerFixed;
		}
		if ($rewriteExisting || $this->footerFixed === NULL) {
			$this->footerFixed = $imported->footerFixed;
		}
		if ($rewriteExisting || $this->sidebarClosed === NULL) {
			$this->sidebarClosed = $imported->sidebarClosed;
		}
		if ($rewriteExisting || $this->sidebarFixed === NULL) {
			$this->sidebarFixed = $imported->sidebarFixed;
		}
		if ($rewriteExisting || $this->sidebarReversed === NULL) {
			$this->sidebarReversed = $imported->sidebarReversed;
		}
		if ($rewriteExisting || $this->sidebarMenuHover === NULL) {
			$this->sidebarMenuHover = $imported->sidebarMenuHover;
		}
		if ($rewriteExisting || $this->sidebarMenuLight === NULL) {
			$this->sidebarMenuLight = $imported->sidebarMenuLight;
		}
		return $this;
	}

}
