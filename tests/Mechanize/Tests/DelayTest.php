<?php

namespace Mechanize\Tests;

use Mechanize\Delay\ExactDelay;
use Mechanize\Delay\NoDelay;
use Mechanize\Delay\RandomDelay;

class DelayTest extends \PHPUnit_Framework_TestCase
{

    public function testExactDelay()
    {
        $delay = new ExactDelay(0.1);
        $delay->delay();

        $this->assertEquals(100000, $delay->getLastDelay());
    }

    public function testNoDelay()
    {
        $delay = new NoDelay();
        $delay->delay();

        $this->assertEquals(0, $delay->getLastDelay());
    }

    public function testRandomDelay()
    {
        $delay = new ExactDelay(0.1, 0.5);
        $delay->delay();

        $this->assertTrue($delay->getLastDelay() >= 100000 && $delay->getLastDelay() <= 500000);
    }
}