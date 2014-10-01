<?php

require_once dirname(__FILE__).'/SimpleStringParser.php';

class PrestaShopTranslatableStringParser extends SimpleStringParser
{
	public function __construct($type_or_regexp, $file = null, $string = null)
	{
		if ($type_or_regexp === 'smarty')
			$this->setRegExp('/{\s*l\s+s\s*=\s*/');
		elseif ($type_or_regexp === 'php')
			$this->setRegExp('/\bl\s*\(\s*/');
		else
			$this->setRegExp($type_or_regexp);

		if ($file)
			$this->setFile($file);
		elseif ($string)
			$this->setString($string);
	}

	public function parse()
	{
		$strings = array();
		while (false !== $this->advance())
		{
			$str = $this->parseString();
			if (false !== $str)
				$strings[] = $str;
		}
		return $strings;
	}
}