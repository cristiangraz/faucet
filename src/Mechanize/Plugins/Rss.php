<?php

namespace Mechanize\Plugins;

use Zend\Feed\Reader\Reader;

class Rss extends AbstractPlugin implements PluginInterface
{
	/**
	 * Reads the rss feed
	 *
	 * @return Zend\Feed\Reader\Feed\FeedInterface
	 */
	public function getFeed()
	{
		return Reader::importString($this->getParser()->getBody());
	}

	/**
	 * Define the plugin's alias
	 *
	 * @return string
	 */
	public function getAlias()
	{
		return 'rss';
	}
}