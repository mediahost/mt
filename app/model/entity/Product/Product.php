<?php

namespace App\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model;
use Nette\Utils\DateTime;

/**
 * @ORM\Entity(repositoryClass="App\Model\Repository\ProductRepository")
 *
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property string $perex
 * @property ProductSeo $perex
 * @property mixed $createdBy
 * @property mixed $updatedBy
 * @property mixed $deletedBy
 * @property DateTime $createdAt
 * @property DateTime $updatedAt
 * @property DateTime $deletedAt
 * @property boolean $active
 * @property string $ean
 * @property Producer $producer
 * @property Category $mainCategory
 * @property array $categories
 * @property array $tags
 * @property array $signs
 * @property array $parameters
 */
class Product extends BaseTranslatable
{

	use Model\Translatable\Translatable;
	use Model\Blameable\Blameable;
	use Model\Loggable\Loggable;
	use Model\Timestampable\Timestampable;
	use Model\SoftDeletable\SoftDeletable;

	/** @ORM\Column(type="boolean") */
	protected $active = TRUE;

	/** @ORM\Column(type="string", length=100, nullable=true) */
	protected $ean;

	/** @ORM\ManyToOne(targetEntity="Producer", inversedBy="products") */
	protected $producer;

	/** @ORM\ManyToOne(targetEntity="Category") */
	protected $mainCategory;

	/** @ORM\ManyToMany(targetEntity="Category", inversedBy="products") */
	protected $categories;

	/** @ORM\OneToMany(targetEntity="Parameter", mappedBy="product") */
	protected $parameters;

	/** @ORM\ManyToMany(targetEntity="Tag", inversedBy="products") */
	protected $tags;

	public function __construct($currentLocale = NULL)
	{
		$this->categories = new ArrayCollection();
		$this->parameters = new ArrayCollection();
		$this->tags = new ArrayCollection();
		parent::__construct($currentLocale);
	}
	
	public function setMainCategory(Category $category)
	{
		$this->mainCategory = $category;
		$this->addCategory($category);
	}

	public function setCategories(array $categories)
	{
		$removeIdles = function ($key, Category $category) use ($categories) {
			if (!in_array($category, $categories, TRUE)) {
				$this->removeCategory($category);
			}
			return TRUE;
		};
		$this->categories->forAll($removeIdles);
		foreach ($categories as $category) {
			$this->addCategory($category);
		}
		return $this;
	}

	public function addCategory(Category $category)
	{
		if (!$this->categories->contains($this->mainCategory)) {
			$this->mainCategory = $category;
		}
		if (!$this->categories->contains($category)) {
			$this->categories->add($category);
		}
		return $this;
	}

	public function removeCategory(Category $category)
	{
		if ($category === $this->mainCategory) {
			$this->mainCategory = NULL;
		}
		return $this->categories->removeElement($category);
	}
	
	public function setParameters(array $parameters)
	{
		$removeIdles = function ($key, Parameter $parameter) use ($parameters) {
			if (!in_array($parameter, $parameters, TRUE)) {
				$this->removeTag($parameter);
			}
			return TRUE;
		};
		$this->parameters->forAll($removeIdles);
		foreach ($parameters as $parameter) {
			$this->addParameter($parameter);
		}
		return $this;
	}

	public function addParameter(Parameter $parameter)
	{
		if (!$this->parameters->contains($parameter)) {
			$parameter->product = $this;
			$this->parameters->add($parameter);
		}
		return $this;
	}

	public function removeParameter(Parameter $parameter)
	{
		return $this->parameters->removeElement($parameter);
	}
	
	public function getTags()
	{
		$onlyTags = function (Tag $tag) {
			return $tag->type === Tag::TYPE_TAG;
		};
		return $this->tags->filter($onlyTags);
	}
	
	public function getSigns()
	{
		$onlyTags = function (Tag $tag) {
			return $tag->type === Tag::TYPE_SIGN;
		};
		return $this->tags->filter($onlyTags);
	}
	
	private function setTagsOrSigns(array $tags, $type = Tag::TYPE_TAG)
	{
		$removeIdles = function ($key, Tag $tag) use ($tags, $type) {
			if ($tag->type === $type && !in_array($tag, $tags, TRUE)) {
				$this->removeTag($tag);
			}
			return TRUE;
		};
		$this->tags->forAll($removeIdles);
		foreach ($tags as $tag) {
			$this->addTag($tag);
		}
		return $this;
	}
	
	public function setTags(array $tags)
	{
		return $this->setTagsOrSigns($tags, Tag::TYPE_TAG);
	}
	
	public function setSigns(array $signs)
	{
		return $this->setTagsOrSigns($signs, Tag::TYPE_SIGN);
	}

	public function addTag(Tag $tag)
	{
		if (!$this->tags->contains($tag)) {
			$this->tags->add($tag);
		}
		return $this;
	}

	public function addSign(Tag $sign)
	{
		return $this->addTag($sign);
	}

	public function removeTag(Tag $tag)
	{
		return $this->tags->removeElement($tag);
	}

	public function removeSign(Tag $sign)
	{
		return $this->removeTag($sign);
	}

	public function __toString()
	{
		return (string) $this->name;
	}

}
