<?php

namespace App\NotificationModule\Presenters;

use App\Extensions\PaymentNotification\PaymentNotificationParser;
use App\Model\Facade\OrderFacade;
use Nette\Utils\Callback;

class BankPresenter extends BasePresenter
{

	/** @var PaymentNotificationParser @inject */
	public $paymentNotificationParser;

	/** @var OrderFacade @inject */
	public $orderFacade;

	public function actionDefault($email)
	{
		$this->paymentNotificationParser->onResolve[] = Callback::closure($this->orderFacade, 'payOrderByNotification');
		$this->paymentNotificationParser->parseMail($email);
		$this->terminate();
	}

}
