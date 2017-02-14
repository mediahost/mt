<?php

namespace App\Model\Entity\Buyout;

use App\Model\Entity\ProducerModel;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="buyout_model_question")
 *
 * @property ProducerModel $model
 * @property Question $question
 * @property float $priceYes
 * @property float $priceNo
 * @property float $price1
 * @property float $price2
 * @property float $price3
 * @property float $price4
 * @property float $price5
 * @property float $price6
 * @property array $pricesArray
 */
class ModelQuestion extends BaseEntity
{

	use Identifier;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Model\Entity\ProducerModel", inversedBy="questions")
	 * @ORM\JoinColumn(name="model_id", referencedColumnName="id", onDelete="CASCADE")
	 */
	protected $model;

	/**
	 * @ORM\ManyToOne(targetEntity="Question", fetch="EAGER")
	 * @ORM\JoinColumn(name="question_id", referencedColumnName="id", onDelete="CASCADE")
	 */
	protected $question;

	/** @ORM\Column(type="float", nullable=true) */
	private $priceA; // TODO: delete

	/** @ORM\Column(type="float", nullable=true) */
	private $priceB; // TODO: delete

	/** @ORM\Column(type="float", nullable=true) */
	protected $priceYes;

	/** @ORM\Column(type="float", nullable=true) */
	protected $priceNo;

	/** @ORM\Column(type="float", nullable=true) */
	protected $price1;

	/** @ORM\Column(type="float", nullable=true) */
	protected $price2;

	/** @ORM\Column(type="float", nullable=true) */
	protected $price3;

	/** @ORM\Column(type="float", nullable=true) */
	protected $price4;

	/** @ORM\Column(type="float", nullable=true) */
	protected $price5;

	/** @ORM\Column(type="float", nullable=true) */
	protected $price6;

	public function setModel(ProducerModel $model)
	{
		$this->model = $model;
		$model->addQuestion($this);
		return $this;
	}

	public function setPriceBool($yesPrice, $noPrice)
	{
		$this->priceYes = $yesPrice;
		$this->priceNo = $noPrice;
		return $this;
	}

	public function setPriceRadio()
	{
		$args = func_get_args();
		if (count($args)) {
			if (count($args) == 1 && is_array($args[0])) {
				$args = $args[0];
			}
			foreach ($args as $key => $value) {
				$property = 'price' . $key;
				if (property_exists($this, $property)) {
					$this->$property = $value;
				}
			}
		}
		return $this;
	}

	public function getPriceRadio($key)
	{
		$property = 'price' . $key;
		if (property_exists($this, $property)) {
			return $this->$property;
		}
		return NULL;
	}

	public function getPricesArray()
	{
		$prices = [];
		if ($this->question->isBool()) {
			$prices = [
				'yes' => $this->priceYes,
				'no' => $this->priceNo,
			];
		} else if ($this->question->isRadio()) {
			$prices = [
				1 => $this->price1,
				2 => $this->price2,
				3 => $this->price3,
				4 => $this->price4,
				5 => $this->price5,
				6 => $this->price6,
			];
		}
		return $prices;
	}

}
