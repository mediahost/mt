<?php

namespace App\Extensions\PaymentNotification;

use Nette\Object;
use Nette\Utils\Finder;

class PaymentNotificationParser extends Object
{

	const RESOLVER_SK_SPORITELNA = 'skSporitelna';

	/** @var string */
	private $regContent = '/^([^:]+:[^\n]+\n(\t.+\n)*)*\s*(?<content>(.*\n)*)/';

	/** @var string */
	protected $regCharset = '/Content-Type: text\/plain; charset="(?<charset>[^"]+)"/';

	/** @var string */
	protected $regFrom = '/From:(( "[^"\n]+")|( =\?.*\?=))? <(?<from>[^>\n]+)>/';

	/** @var IResolver[] */
	public $mailResolvers = [];

	/** @var IXmlResolver */
	public $xmlResolver;

	/** @var SkSporitelnaResolver */
	public $skSporitelnaResolver;

	/** @var callable[] function (Payment $payment); */
	public $onResolve = [];

	/** @var callable[] function ($content); */
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

		foreach ($this->mailResolvers as $resolver) {
			$paymentList = $resolver->resolve($mailContent, $from);
			foreach ($paymentList as $payment) {
				$this->onResolve($payment);
			}
			if (count($paymentList)) {
				return;
			}
		}
		$this->onFailed($mailContent);
	}

	public function parseXml($xml, $resolver)
	{
		switch ($resolver) {
			case self::RESOLVER_SK_SPORITELNA:
				$this->xmlResolver = $this->skSporitelnaResolver;
				break;
			default:
				throw new \Exception('Wrong XML Resolver');
		}

		$paymentList = $this->xmlResolver->resolve($xml);
		foreach ($paymentList as $payment) {
			try {
				$this->onResolve($payment);
			} catch (\Exception $e) {
				$this->onFailed($xml, $e->getMessage());
			}
		}
	}

	public function setSkSporitelnaResolver(SkSporitelnaResolver $resolver)
	{
		$this->skSporitelnaResolver = $resolver;
		return $this;
	}

	public function getTestMails()
	{
		$finder = Finder::findFiles('*')->in(__DIR__ . '/testMails');
		$return = [];
		foreach ($finder as $key => $file) {
			$return[$key] = file_get_contents($key);
		}
		return $return;
	}

}