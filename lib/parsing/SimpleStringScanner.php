<?php

class SimpleStringScanner
{
	private $string = null;
	private $string_length = 0;
	private $regexp = null;
	private $pos    = 0;
	private $got = array();

	public function setString($string)
	{
		$this->string = $string;
		$this->string_length = strlen($string);
		$this->pos = 0;
		$this->got = array();
		return $this;
	}

	public function setRegExp($regexp)
	{
		$this->regexp = $regexp;
		return $this;
	}

	public function getPos()
	{
		return $this->pos;
	}

	/**
	 * Advances $pos to the next position after
	 * the RegExp matches.
	 *
	 * Returns false if none found, in which case pos is set to
	 * the length of the string.
	 * 
	 * @return [type] [description]
	 */
	public function advance()
	{
		if ($this->pos >= $this->string_length)
			return false;

		$m = array();
		$matched = preg_match(
			$this->regexp,
			$this->string,
			$m,
			PREG_OFFSET_CAPTURE,
			$this->pos
		);

		$pos = $this->pos;

		if ($matched)
		{
			$this->pos = $m[0][1] + strlen($m[0][0]);

			if ($this->pos >= $this->string_length)
				return false;

			if ($this->pos <= $pos)
				throw new \Exception('SimpleStringScanner::advance did not change internal position. Aborting before infinite loop.');

			return $this->pos;
		}
		else
		{
			$this->pos = $this->string_length;
			return false;
		}
	}

	public function peek($n = 1)
	{
		$next_chars = substr($this->string, $this->pos, 2*$n);
		$peeked = mb_substr($next_chars, 0, $n, 'UTF-8');

		if (!$peeked)
			return false;

		if (mb_strlen($peeked, 'UTF-8') !== $n)
			return false;

		return $peeked;
	}

	public function get($n = 1)
	{
		$str = $this->peek($n);
		if (false !== $str)
		{
			$this->pos += strlen($str);

			$this->got[] = $str;
			if (count($this->got) > 10)
				array_shift($this->got);
		}
		return $str;
	}

	public function unget()
	{
		if (count($this->got) === 0)
			throw new \Exception('Nothing to unget!');
		else
		{
			$str = array_pop($this->got);
			$this->pos -= strlen($str);

			return $this->pos;
		}
	}
}