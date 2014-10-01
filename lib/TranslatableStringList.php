<?php

class TranslatableStringList
{
	private $id;
	private $identifier_array;
	private $strings;

	public static function makeID($identifier_array)
	{
		ksort($identifier_array);
		$tmp = array();
		foreach ($identifier_array as $key => $value)
		{
			$tmp[] = "$key:$value";
		}
		return implode(",", $tmp);
	}

	public function __construct($identifier_array)
	{
		$this->identifier_array = $identifier_array;
		$this->id = self::makeID($identifier_array);
	}

	public function prop($key, $default = null)
	{
		if (isset($this->identifier_array[$key]))
			return $this->identifier_array[$key];
		else
			return $default;
	}

	public function getId()
	{
		return $this->id;
	}

	public function addString($key, $string)
	{
		$this->strings[$key] = $string;
		return $this;
	}
}