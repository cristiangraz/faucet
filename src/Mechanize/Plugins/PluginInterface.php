<?php

namespace Mechanize\Plugins;

interface PluginInterface
{
	/**
	 * Define the plugin's alias
	 *
	 * @return string
	 */
	public function getAlias();
}