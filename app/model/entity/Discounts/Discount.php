<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @property string $type
 * @property float $value
 * @property Discount $discount
 */
class Discount extends DiscountBase
{

}
