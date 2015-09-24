<?php

namespace App\Service\PaymentNotification;

use Nette\Object;
use Nette\Utils\Finder;

class PaymentNotificationParser extends Object
{

	/** @var string */
	private $regContent = '/^([^:]+:[^\n]+\n(\t.+\n)*)*\s*(?<content>(.*\n)*)/';

	/** @var string */
	protected $regCharset = '/Content-Type: text\/plain; charset="(?<charset>[^"]+)"/';

	/** @var string */
	protected $regFrom = '/From:( "[^"\n]+")? <(?<from>[^>\n]+)>/';

	/** @var IResolver[] */
	public $resolvers = [];

	/**
	 * @var callable[] function (Payment $payment);
	 */
	public $onResolve = [];

	/**
	 * @var callable[] function ($mailContent);
	 */
	public $onFailed = [];

	public function parseMail($mailContent)
	{
		$matchedContent = preg_match($this->regContent, $mailContent, $matchesContent);
		$matchedCharset = preg_match($this->regCharset, $mailContent, $matchesCharset);
		$matchedFrom = preg_match($this->regFrom, $mailContent, $matchesFrom);

		if (!$matchedContent || !$matchedCharset || !$matchedFrom) {
			$this->onFailed($mailContent);
			return;
		}

		// zatial predpokladame ze vsetky mail contenty su kodovane v base64
		$mailContent = base64_decode($matchesContent['content']);

		// dekodujeme podla Content-type: charset="..."
		$charset = $matchesCharset['charset'];
		if (strtolower($charset) != 'utf-8') {
		    $mailContent = iconv($charset, 'UTF-8', $mailContent);
		}

		// emailova adresa odosielatel
		$from = $matchesFrom['from'];

		foreach ($this->resolvers as $resolver) {
			$paymentList = $resolver->resolve($mailContent, $from);
			foreach ($paymentList as $payment) {
				$this->onResolve($payment);
			}
		}
	}

	public function getTestMails()
	{
		$finder = Finder::findFiles('*')->in(__DIR__ . '/testMails');
		$return = [];
		foreach ($finder as $key => $file) {
			$return[] = file_get_contents($key);
		}
		return $return;
	}

}