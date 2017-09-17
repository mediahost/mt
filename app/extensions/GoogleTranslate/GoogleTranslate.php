<?php

namespace App\Extensions;

use Exception;
use Nette\Http\Url;
use Nette\Object;
use Tracy\Debugger;

class GoogleTranslate extends Object
{

	const TRANSLATE_URL = 'https://www.googleapis.com/language/translate/v2';

	/** @var string */
	private $apiKey;

	// <editor-fold desc="setters">

	public function setAuth($apiKey)
	{
		$this->apiKey = $apiKey;
		return $this;
	}

	// </editor-fold>

	public function translate($text, $source, $target)
	{
		if (empty($text)) {
			return NULL;
		}
		$url = new Url(self::TRANSLATE_URL);
		$url->setQueryParameter('key', $this->apiKey);
		$url->setQueryParameter('q', $text);
		$url->setQueryParameter('source', $source);
		$url->setQueryParameter('target', $target);

		$handle = curl_init((string)$url->getHostUrl() . $url->getPath());
		curl_setopt($handle, CURLOPT_POST, 1);
		curl_setopt($handle, CURLOPT_POSTFIELDS, (string)$url->getQuery());
		curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($handle, CURLOPT_HEADER, 0);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($handle);
		$responseDecoded = json_decode($response, true);
		curl_close($handle);

		if (array_key_exists('error', $responseDecoded)) {
			throw new GoogleTranslateException($responseDecoded['error']['message']);
		} else if (array_key_exists('data', $responseDecoded)) {
			return $responseDecoded['data']['translations'][0]['translatedText'];
		}
	}

}

class GoogleTranslateException extends Exception
{

}
