<?php

namespace Mechanize;

interface DelayInterface
{
	public function delay();

	public function getLastDelay();
}