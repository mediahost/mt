<?php

namespace App\Model\Entity\Traits;

use App\Model\Entity\Parameter;
use App\Model\Entity\Tag;

/**
 * @property array $tags
 * @property array $signs
 * @property array $parameters
 */
trait ProductParameters
{

	/** @ORM\OneToMany(targetEntity="Parameter", mappedBy="product") */
	protected $parameters;

	/** @ORM\ManyToMany(targetEntity="Tag", inversedBy="products") */
	protected $tags;
	
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
	
	protected function setTagsOrSigns(array $tags, $type = Tag::TYPE_TAG)
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

}