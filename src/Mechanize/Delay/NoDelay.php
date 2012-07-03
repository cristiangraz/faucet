<?php

namespace Mechanize\Delay;

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

    /**
     * Return the length of time used in the last delay
     *
     * @var int
     */
    public function getLastDelay()
    {
        return 0;
    }
}