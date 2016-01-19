<?php

namespace App\Model\Facade;

use App\Model\Entity\Basket;
use App\Model\Entity\Order;
use App\Model\Entity\Voucher;
use App\Model\Repository\BasketRepository;
use App\Model\Repository\OrderRepository;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Nette\Object;

class VoucherFacade extends Object
{

	/** @var EntityManager @inject */
	public $em;

	/** @var EntityRepository */
	private $voucherRepo;

	/** @var OrderRepository */
	private $orderRepo;

	/** @var BasketRepository */
	private $basketRepo;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->voucherRepo = $this->em->getRepository(Voucher::getClassName());
		$this->orderRepo = $this->em->getRepository(Order::getClassName());
		$this->basketRepo = $this->em->getRepository(Basket::getClassName());
	}

	public function isUnique($code)
	{
		return $this->voucherRepo->findOneBy(['code' => $code]) === NULL;
	}

	public function delete(Voucher $voucher)
	{
		if ($this->isDeletable($voucher)) {
			return $this->voucherRepo->delete($voucher);
		} else {
			throw new Exception\FacadeException('This voucher is used');
		}
	}

	public function isDeletable(Voucher $voucher)
	{
		return !$this->isUsed($voucher);
	}

	public function isUsed(Voucher $voucher)
	{
		if (count($voucher->orders)) {
			return TRUE;
		}
		if (count($voucher->baskets)) {
			return TRUE;
		}
		return FALSE;
	}

}
