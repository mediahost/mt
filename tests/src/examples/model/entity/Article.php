<?php

namespace Test\Examples\Model\Entity;

use App\Model\Entity\BaseTranslatable;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity
 * https://github.com/Zenify/DoctrineBehaviors#translatable
 * https://github.com/KnpLabs/DoctrineBehaviors/blob/master/tests/fixtures/BehaviorFixtures/ORM/TranslatableEntity.php
 *
 * @property string $title
 */
class Article extends BaseTranslatable
{

	use Model\Translatable\Translatable;
}
