<?php

namespace App\Model\Entity\Buyout;

use App\Model\Entity\ProducerModel;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="buyout_model_question")
 *
 * @property ProducerModel $model
 * @property Question $question
 * @property float $priceA
 * @property float $priceB
 */
class ModelQuestion extends BaseEntity
{

	use \Kdyby\Doctrine\Entities\Attributes\Identifier;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Model\Entity\ProducerModel", inversedBy="questions")
	 * @ORM\JoinColumn(name="model_id", referencedColumnName="id")
	 */
	protected $model;

	/**
	 * @ORM\ManyToOne(targetEntity="Question")
	 * @ORM\JoinColumn(name="question_id", referencedColumnName="id")
	 */
	protected $question;

	/**
	 * @ORM\Column(type="float", nullable=true)
	 */
	protected $priceA;

	/**
	 * @ORM\Column(type="float", nullable=true)
	 */
	protected $priceB;

	/**
	 * @param ProducerModel $model
	 * @return ModelQuestion
	 */
	public function setModel(ProducerModel $model)
	{
		$this->model = $model;
		$model->addQuestion($this);
		return $this;
	}
	
	public function setPrice($a, $b)
	{
		$this->priceA = $a;
		$this->priceB = $b;
		return $this;
	}

}
