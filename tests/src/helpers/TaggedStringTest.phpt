<?php

namespace Test\Helpers;

use App\TaggedString;
use Tester\Assert;
use Tester\TestCase;

$container = require __DIR__ . '/../bootstrap.php';

/**
 * TEST: Tagged String
 *
 * @testCase
 * @phpVersion 5.4
 */
class TaggedStringTest extends TestCase
{

	public function testTaggedString()
	{
		$toReplace1 = 'test %s test %d test';
		$taggedString1 = new TaggedString($toReplace1, 'test1', 2);
		Assert::same('test test1 test 2 test', (string) $taggedString1);
	}

}

$test = new TaggedStringTest();
$test->run();
