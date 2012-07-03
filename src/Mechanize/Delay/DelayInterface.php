<?php

namespace Mechanize\Delay;

interface DelayInterface
{
    public function delay();

    public function getLastDelay();
}