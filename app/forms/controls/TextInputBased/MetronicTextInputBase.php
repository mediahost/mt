<?php

namespace App\Forms\Controls\TextInputBased;

use Nette\Forms\Controls\TextInput;

/**
 * TextInputBase for Metronic style
 */
abstract class MetronicTextInputBase extends TextInput
{

	const SIZE_FLUID = NULL;
	const SIZE_XL = 'input-xlarge';
	const SIZE_L = 'input-large';
	const SIZE_M = 'input-medium';
	const SIZE_S = 'input-small';
	const SIZE_XS = 'input-xsmall';

	/** @var array */
	private $dataSetters = [];

	/** @var array */
	protected $dataAttributes = [];

	public function __construct($label = NULL, $maxLength = NULL)
	{
		$this->getSettersFromAnnotations();
		parent::__construct($label, $maxLength);
	}

	public function __call($name, $args)
	{
		if (in_array($name, $this->dataSetters)) {
			if (preg_match('~^set(\w+)~', $name, $matches)) {
				list($value) = $args;
				return $this->setDataAttribute($matches[1], $value);
			}
		}
		return parent::__call($name, $args);
	}

	/**
	 * Get setters for data attributes
	 */
	private function getSettersFromAnnotations()
	{
		$annotations = $this->getReflection()->getAnnotations();
		if (array_key_exists('method', $annotations)) {
			foreach ($annotations['method'] as $method) {
				if (preg_match('~^\S*\s+(set\w+)\(~', $method, $matches)) {
					$this->dataSetters[] = $matches[1];
				}
			}
		}
	}

	/**
	 * Set attribute with prefix 'data-'
	 * @param string $name
	 * @param mixed $value
	 * @return self
	 */
	protected function setDataAttribute($name, $value)
	{
		return $this->setAttribute('data-' . $this->getDataAttributeName($name), $value);
	}

	/**
	 * Translate attribute by $this->dataAttributes
	 * @param string $attributeKey
	 * @return string
	 */
	protected function getDataAttributeName($attributeKey)
	{
		if (array_key_exists($attributeKey, $this->dataAttributes)) {
			return $this->dataAttributes[$attributeKey];
		}
		return $attributeKey;
	}

	/**
	 * Set size of main element
	 * @param string $size
	 * @return self
	 */
	public function setSize($size = self::SIZE_FLUID)
	{
		return $this->setDataAttribute('size', $this->getStandardedSize($size));
	}
	
	/**
	 * Get standarded size
	 * @param string $size
	 * @return string
	 */
	protected function getStandardedSize($size = self::SIZE_FLUID)
	{
		switch ($size) {
			case self::SIZE_FLUID:
			case self::SIZE_XL:
			case self::SIZE_L:
			case self::SIZE_M:
			case self::SIZE_S:
			case self::SIZE_XS:
				break;
			default:
				$size = self::SIZE_FLUID;
				break;
		}
		return $size;
	}

}
