<?php

namespace Mechanize\Plugins;

class Rss extends AbstractPlugin implements PluginInterface
{
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