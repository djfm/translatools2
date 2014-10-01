<?php

require_once dirname(__FILE__).'/../../lib/parsing/PrestaShopTranslatableStringParser.php';

class PrestaShopTranslatableStringParserTest extends PHPUnit_Framework_TestCase
{
	public function simpleExamples()
	{
		return array(
			array('php', 'l("bob")', array('bob')),
			array('php', 'l("bob")  Mail::l("marley")', array('bob', 'marley')),
			array('php', 'l("bob")  Mail::l(\'marley\')', array('bob', 'marley')),
			array('php', 'l ( "bob")  Mail::l(\'marley\')', array('bob', 'marley')),
			array('php', 'l ( "hello")  Mail::l(\'world\'', array('hello', 'world')),

			array('smarty', 'l ( "hello")  {l s="world"} Mail::l(\'world\'', array('world')),
			array('smarty', 'l ( "hello")  {l s="world"} Mail::l(\'world\' {l s    =    \'yay\'}', array('world', 'yay'))
		);
	}


	/**
	 * @dataProvider simpleExamples
	 */
	public function testSimpleL($type, $string, $expected)
	{
		$parser = new PrestaShopTranslatableStringParser($type);
		$parser->setString($string);
		$this->assertEquals($expected, $parser->parse());
	}	
}