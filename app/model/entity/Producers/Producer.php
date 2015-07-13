<?php

namespace App\Model\Entity;

use App\Helpers;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model;
use Nette\Http\FileUpload;
use Nette\Utils\Strings;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\ProducerRepository")
 *
 * @property Producer $parent
 * @property array $children
 * @property-read bool $hasChildren
 * @property string $name
 * @property string $serviceHtml
 * @property Image $image
 * @property array $products
 * @property array $path
 * @property string $url
 * @property array $lines
 * @property-read bool $hasLines
 */
class Producer extends BaseTranslatable implements IProducer
{

	const ID = 'p';
	const SEPARATOR = '-';

	use Model\Translatable\Translatable;
	use Model\Sluggable\Sluggable;

	/** @ORM\Column(type="string", length=256) */
	protected $name;

	/** @ORM\OneToOne(targetEntity="Image", cascade="all") */
	protected $image;

	/** @ORM\OneToMany(targetEntity="Product", mappedBy="producer") */
	protected $products;

	/** @ORM\OneToMany(targetEntity="ProducerLine", mappedBy="producer", cascade={"persist"}) */
	protected $lines;

	public function __construct($name = NULL, $currentLocale = NULL)
	{
		if ($name) {
			$this->name = $name;
		}
		$this->lines = new ArrayCollection();
		parent::__construct($currentLocale);
	}

	public function setImage(FileUpload $file)
	{
		if (!$this->image instanceof Image) {
			$this->image = new Image($file);
		} else {
			$this->image->setFile($file);
		}
		$this->image->requestedFilename = 'producer_' . Strings::webalize(microtime());
		$this->image->setFolder(Image::FOLDER_PRODUCERS);

		return $this;
	}

	public function addLine(ProducerLine $line)
	{
		$line->producer = $this;
		$this->lines->add($line);
		return $this;
	}

	public function getHasLines()
	{
		return (bool) count($this->lines);
	}

	protected function getSluggableFields()
	{
		return ['name'];
	}

	public function __toString()
	{
		return (string) $this->name;
	}

	public function isNew()
	{
		return $this->id === NULL;
	}

	public static function getItemId($id, &$type = NULL)
	{
		$allowedTypes = Helpers::concatStrings('|', self::ID, ProducerLine::ID, ProducerModel::ID);
		$separator = preg_quote(self::SEPARATOR);
		if (preg_match('/^(' . $allowedTypes . ')' . $separator . '(\d+)$/i', $id, $matches)) {
			$type = $matches[1];
			return $matches[2];
		}
		return NULL;
	}

}
