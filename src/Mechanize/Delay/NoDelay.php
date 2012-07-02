<?php

namespace Mechanize\Delay;

use Mechanize\DelayInterface;

class NoDelay implements DelayInterface
{
	/**
	 * Does nothing
	 *
	 * @return void
	 */
	public function delay()
	{
		return;
	}
}