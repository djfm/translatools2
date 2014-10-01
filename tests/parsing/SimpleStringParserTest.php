<?php

require_once dirname(__FILE__).'/../../lib/parsing/SimpleStringParser.php';

class SimpleStringParserTest extends PHPUnit_Framework_TestCase
{

	public function simpleExamples()
	{
		return array(
			array("'hello'", 'hello'),
			array("'he\'llo'", 'he\'llo'),
			array("'he\\\\llo'", 'he\\llo'),
			array("'he\\\\\'llo'", 'he\\\'llo'),
			array("'hi\\\\'", 'hi\\'),

			array('"hello"', "hello"),
			array('"he\"llo"', "he\"llo"),
			array('"he\\\\llo"', "he\\llo"),
			array('"he\\\\\"llo"', "he\\\"llo"),
			array('"hi\\\\"', 'hi\\')
		);
	}

	/**
	 * @dataProvider simpleExamples
	 */
	public function testSimpleString($input, $expected)
	{
		$parser = new SimpleStringParser();
		$parser->setString($input);
		$this->assertEquals(array($expected), $parser->parse());
		$this->assertEquals(false, $parser->peek());
	}

	public function failingExamples()
	{
		return array(
			array("'hello"),
			array('"hello'),
			array('"hello\''),
			array('\'hello"')
		);
	}

	/**
	 * @dataProvider failingExamples
	 */
	public function testParsingFailsWhenExpected($input)
	{
		$parser = new SimpleStringParser();
		$parser->setString($input);
		$this->assertEquals(array(), $parser->parse());
	}
}