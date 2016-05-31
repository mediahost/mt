<?php

namespace App\Model\Entity;

use App\Helpers;
use App\Model\Entity\Buyout\ModelQuestion;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model;
use Nette\Http\FileUpload;
use Nette\Utils\Image as ImageUtils;
use Nette\Utils\Strings;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\ProducerModelRepository")
 * @ORM\EntityListeners({"App\Listeners\Model\Entity\ProducerModelListener"})
 *
 * @property string $name
 * @property string $html
 * @property ProducerLine $line
 * @property float $buyoutPrice
 * @property ArrayCollection $questions
 */
class ProducerModel extends BaseTranslatable implements IProducer
{

	const ID = 'm';

	use Model\Translatable\Translatable;
	use Model\Sluggable\Sluggable;

	/** @ORM\Column(type="string", length=256) */
	protected $name;

	/** @ORM\OneToOne(targetEntity="Image", cascade="all") */
	protected $image;

	/** @ORM\ManyToOne(targetEntity="ProducerLine", inversedBy="models") */
	protected $line;

	/** @ORM\OneToMany(targetEntity="ParameterPrice", mappedBy="model", cascade={"persist", "remove"}) */
	protected $parameterPrices;

	/** @ORM\ManyToMany(targetEntity="Product", mappedBy="accessoriesFor") */
	protected $products;

	/** @ORM\Column(type="float") */
	protected $buyoutPrice = 0;

	/** @ORM\OneToMany(targetEntity="App\Model\Entity\Buyout\ModelQuestion", mappedBy="model", indexBy="id") */
	protected $questions;

	public function __construct($name, $currentLocale = NULL)
	{
		$this->name = $name;
		$this->parameterPrices = new ArrayCollection();
		$this->questions = new ArrayCollection();
		parent::__construct($currentLocale);
	}

	public function setImage($file)
	{
		if (!$this->image instanceof Image) {
			$this->image = new Image($file);
		} else if ($file instanceof FileUpload || $file instanceof ImageUtils) {
			$this->image->setSource($file);
		}
		$this->image->requestedFilename = 'producer_model_' . Strings::webalize(microtime());
		$this->image->setFolder(Image::FOLDER_PRODUCERS);

		return $this;
	}

	public function getParameterPriceByParameter(ModelParameter $parameter, $create = FALSE)
	{
		$isForParameter = function (ParameterPrice $parameterPrice) use ($parameter) {
			return (int) $parameterPrice->parameter->id === (int) $parameter->id;
		};
		$parameterPrices = $this->parameterPrices->filter($isForParameter);
		if ($parameterPrices->count()) {
			$parameterPrice = $parameterPrices->first();
		} else if ($create) {
			$parameterPrice = new ParameterPrice($parameter);
			$parameterPrice->model = $this;
			$this->parameterPrices->add($parameterPrice);
		} else {
			$parameterPrice = NULL;
		}

		return $parameterPrice;
	}

	public function getFullName($glue = ' / ')
	{
		return Helpers::concatStrings($glue, (string) $this->line->producer, (string) $this->line, (string) $this);
	}

	/**
	 * @param Question2 $question
	 * @return ProducerModel
	 */
	public function addQuestion(ModelQuestion $question)
	{
		$this->questions->add($question);
		return $this;
	}

	public function __toString()
	{
		return (string) $this->name;
	}

	public function isNew()
	{
		return $this->id === NULL;
	}

	public function getSluggableFields()
	{
		return ['name'];
	}

}
