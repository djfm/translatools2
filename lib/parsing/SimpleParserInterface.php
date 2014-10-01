<?php

interface SimpleParserInterface
{
	public function setString($string);
	public function setFile($file_path);
	/**
	 * Parses the current input
	 * @return array of things parsed
	 */
	public function parse();
}