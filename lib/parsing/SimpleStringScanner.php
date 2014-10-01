<?php

class SimpleStringScanner
{
	private $string = null;
	private $regexp = null;
	private $pos    = 0;
	private $internal_pos = 0;
	private $got = array();

	public function setString($string)
	{
		$this->string = $string;
		$this->pos = 0;
		$this->internal_pos = 0;
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

	public static function strlen($string)
	{
		return mb_strlen($string, 'UTF-8');
	}

	public static function substr($string, $start = 0, $length = 1)
	{
		return mb_substr($string, $start, $length, 'UTF-8');
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
		if ($this->pos >= self::strlen($this->string))
			return false;

		$m = array();
		$matched = preg_match(
			$this->regexp,
			$this->string,
			$m,
			PREG_OFFSET_CAPTURE,
			$this->internal_pos
		);

		$pos = $this->pos;

		if ($matched)
		{
			$total_part_skipped = substr($this->string, 0, $m[0][1]);			
			$mb_offset = self::strlen($total_part_skipped);
			$this->pos = $mb_offset + self::strlen($m[0][0]);

			$this->internal_pos = $m[0][1] + strlen($m[0][0]);

			if ($this->pos >= self::strlen($this->string))
				return false;

			if ($this->pos <= $pos)
				throw new \Exception('SimpleStringScanner::advance did not change internal position. Aborting before infinite loop.');

			return $this->pos;
		}
		else
		{
			$this->pos = self::strlen($this->string);
			return false;
		}
	}

	public function peek($n = 1)
	{
		if ($this->pos + $n > self::strlen($this->string))
			return false;

		return self::substr($this->string, $this->pos, $n);
	}

	public function get($n = 1)
	{
		$str = $this->peek($n);
		if (false !== $str)
		{
			$this->pos += $n;
			$this->internal_pos += strlen($str);

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
			$this->pos -= self::strlen($str);
			$this->internal_pos -= strlen($str);

			return $this->pos;
		}
	}
}