<?php

namespace Mechanize\Delay;

use Mechanize\DelayInterface;

class ExactDelay implements DelayInterface
{
    /**
     * The delay in microseconds
     *
     * @var int
     */
    protected $delay;

    /**
     * Takes a delay time and sets it
     *
     * @param float $delay The delay time in seconds
     */
    public function __construct(float $delay)
    {
        $this->delay = $delay * 1000000;
    }

    /**
     * Implements a random delay within the range specified
     *
     * @return void
     */
    public function delay()
    {
        usleep($this->delay);
    }

    /**
     * Return the length of time used in the last delay
     *
     * @var int
     */
    public function getLastDelay()
    {
        return $this->delay;
    }
}