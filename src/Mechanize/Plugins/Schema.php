<?php

namespace Mechanize\Plugins;

class Schema extends AbstractPlugin implements PluginInterface
{
	/**
	 * Define the plugin's alias
	 *
	 * @return string
	 */
	public function getAlias()
	{
		return 'schema';
	}
}