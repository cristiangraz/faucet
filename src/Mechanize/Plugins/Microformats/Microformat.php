<?php

namespace Mechanize\Plugins\Microformats;

use Mechanize\Plugins\AbstractPlugin;

abstract class Microformat extends AbstractPlugin
{
	/**
	 * Checks the node for a value-title and returns either value-title or the text of the original node
	 * 
	 * @param  object $node Mechanize\Dom\Elements
	 * 
	 * @return string The value
	 */
	protected function getValue($node)
	{
		if ($node->length === 0) {
			return;
		}

		$value = $node->selectOne('.value-title');

		if ($value->length === 1) {
			return $value->title;
		}

		return $node->getText();
	}
}