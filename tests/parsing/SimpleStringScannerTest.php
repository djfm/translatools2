<?php

require_once dirname(__FILE__).'/../../lib/parsing/SimpleStringScanner.php';

class SimpleStringScannerTest extends PHPUnit_Framework_TestCase
{
	public function testBaseCase()
	{
		$scanner = new SimpleStringScanner();
		$scanner
		->setString("hello world ")
		->setRegExp('/world/');

		$this->assertEquals(11, $scanner->advance());
		$this->assertEquals(false, $scanner->advance());
	}

	public function testBaseCaseMatchAtEnd()
	{
		$scanner = new SimpleStringScanner();
		$scanner
		->setString("hello world")
		->setRegExp('/world/');

		$this->assertEquals(false, $scanner->advance());
		$this->assertEquals(false, $scanner->advance());
	}

	public function testWithTwoMatches()
	{
		$scanner = new SimpleStringScanner();
		$scanner
		->setString("hello world world ")
		->setRegExp('/world/');

		$this->assertEquals(11, $scanner->advance());
		$this->assertEquals(17, $scanner->advance());
		$this->assertEquals(false, $scanner->advance());
	}
	
	public function testBaseCaseUTF8()
	{
		$scanner = new SimpleStringScanner();
		$scanner
		->setString("hello wérld ")
		->setRegExp('/wérld/u');

		$this->assertEquals(11, $scanner->advance());
		$this->assertEquals(false, $scanner->advance());
	}

	public function testWithTwoMatchesUTF8()
	{
		$scanner = new SimpleStringScanner();
		$scanner
		->setString("hello wéàld wéàld ")
		->setRegExp('/wéàld/u');

		$this->assertEquals(11, $scanner->advance());
		$this->assertEquals(17, $scanner->advance());
		$this->assertEquals(false, $scanner->advance());
	}

	public function testPeek()
	{
		$scanner = new SimpleStringScanner();
		$scanner
		->setString("hello wéàld wéàld ")
		->setRegExp('/wéàld/u');

		$scanner->advance();
		$this->assertEquals(' ', $scanner->peek());
		$this->assertEquals(' w', $scanner->peek(2));

		$scanner->advance();
		$this->assertEquals(' ', $scanner->peek());
		$this->assertEquals(false, $scanner->peek(2));
	}

	public function testGet()
	{
		$scanner = new SimpleStringScanner();
		$scanner
		->setString("hello wéàld wéàld ")
		->setRegExp('/wéàld/u');

		$this->assertEquals('he', $scanner->get(2));
		$this->assertEquals('llo ', $scanner->get(4));
		$this->assertEquals(11, $scanner->advance());
		$this->assertEquals(' ', $scanner->peek());
		$this->assertEquals(' ', $scanner->get());
		$this->assertEquals('wé', $scanner->get(2));
		$this->assertEquals(false, $scanner->advance());
	}

	public function testUnGet()
	{
		$scanner = new SimpleStringScanner();
		$scanner
		->setString("héllo wéàld wéàld ")
		->setRegExp('/wéàld/u');

		$this->assertEquals('hé', $scanner->get(2));
		$this->assertEquals('llo ', $scanner->get(4));
		$this->assertEquals(2, $scanner->unget(4));
	}
}