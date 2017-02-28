<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\PageRepository")
 * @ORM\EntityListeners({"App\Listeners\Model\Entity\PageListener"})
 *
 * @property string $name
 * @property string $html
 * @property string $comment
 * @property string $link
 * @property string $linkHeadline
 * @property string $linkSubscribe
 * @property string $type
 * @property string $rawType
 * @property Shop $shop
 * @property ShopVariant $shopVariant
 */
class Page extends BaseTranslatable
{

	const TYPE_CONTACT = 'contact';
	const TYPE_TERMS = 'terms';
	const TYPE_CALL_ORDER = 'call_order';
	const TYPE_COMPLAINT = 'complaint';

	use Model\Translatable\Translatable;

	/** @ORM\Column(type="string", length=100, nullable=true) */
	protected $comment;

	/** @ORM\Column(type="string", length=256, nullable=true) */
	protected $link;

	/** @ORM\ManyToOne(targetEntity="Shop") */
	protected $shop;

	/** @ORM\ManyToOne(targetEntity="ShopVariant") */
	protected $shopVariant;

	/** @ORM\Column(type="string", length=20, nullable=true) */
	protected $type;

	public function __construct($currentLocale = NULL, $id = NULL)
	{
		if ($id) {
			$this->setId($id);
		}
		parent::__construct($currentLocale);
	}

	public function isInterLink()
	{
		$isExtern = preg_match('@(^http|^/\w+|\./)@', $this->link);
		return $this->link && !$isExtern;
	}

	public function __toString()
	{
		return (string)$this->name;
	}

	public function isNew()
	{
		return $this->id === NULL;
	}

	public function setId($id)
	{
		$this->id = $id;
		return $this;
	}

	public function getType($formated = TRUE)
	{
		if ($formated) {
			$types = self::getTypes();
			if (array_key_exists($this->type, $types)) {
				return $types[$this->type];
			}
		}
		return $this->type;
	}

	public function getRawType()
	{
		return $this->getType(FALSE);
	}

	public static function getTypes()
	{
		return [
			self::TYPE_CONTACT => 'Contact',
			self::TYPE_TERMS => 'Terms and Conditions',
			self::TYPE_CALL_ORDER => 'Order by phone',
			self::TYPE_COMPLAINT => 'Complaint',
		];
	}

}
