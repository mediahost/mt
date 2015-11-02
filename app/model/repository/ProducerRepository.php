<?php

namespace App\Model\Repository;

use App\Model\Entity\Producer;
use App\Model\Entity\ProducerLine;

class ProducerRepository extends BaseRepository
{

	public function delete($entity)
	{
		if ($entity instanceof Producer) {
			$lineRepo = $this->_em->getRepository(ProducerLine::getClassName());
			foreach ($entity->lines as $line) {
				$lineRepo->delete($line);
			}
		}

		return parent::delete($entity);
	}

}
