<?php

require_once dirname(__FILE__).'/SimpleStringScanner.php';
require_once dirname(__FILE__).'/SimpleParserInterface.php';

class SimpleStringParser extends SimpleStringScanner implements SimpleParserInterface
{
	public function setFile($path)
	{
		if (!file_exists($path))
			throw new \Exception("File not found: $path.");
		$this->setString(file_get_contents($path));
		return $this;
	}

	public function parseString()
	{
		if (!in_array($this->peek(), array("'", '"')))
			return false;

		$quote = $this->get();

		$escaping = false;
		$string = '';

		for (;;)
		{
			$c = $this->get();

			if (false === $c)
				return false;

			if ($c === $quote)
			{
				if ($escaping)
				{
					$string .= $c;
					$escaping = false;
				}
				else
					return $string;
			}
			elseif ($c === '\\')
			{
				if ($escaping)
				{
					$string .= $c;
					$escaping = false;
				}
				else
					$escaping = true;
				
			}
			else
			{
				if ($escaping)
				{
					$string .= '\\'.$c;
					$escaping = false;
				}
				else
					$string .= $c;
			}
		}
	}

	public function parse()
	{
		$str = $this->parseString();
		if (false !== $str)
			return array($str);
		else
			return array();
	}
}