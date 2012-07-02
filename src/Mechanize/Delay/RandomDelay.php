<?php

namespace Mechanize\Delay;

use Mechanize\DelayInterface;

class RandomDelay implements DelayInterface
{
	/**
	 * The minimum delay in microseconds
	 *
	 * @var int
	 */
	protected $minimum;

	/**
	 * The maximum delay in microseconds
	 *
	 * @var int
	 */
	protected $maximum;

	/**
	 * Takes a minimum and maximum delay range and sets them
	 *
	 * @param float $minimum The minimum delay time in seconds
	 * @param float $maximum The maximum delay time in seconds
	 */
	public function __construct(float $minimum, float $maximum)
	{
		$this->minimum = $minimum * 1000000;
		$this->maximum = $maximum * 1000000;
	}

	/**
	 * Implements a random delay within the range specified
	 *
	 * @return void
	 */
	public function delay()
	{
		usleep(rand($this->minimum, $this->maximum));
	}
}