<?php

namespace Faucet\Delay;

interface DelayInterface
{
    public function delay();

    public function getLastDelay();
}