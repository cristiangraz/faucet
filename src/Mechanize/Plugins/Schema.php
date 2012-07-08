<?php

namespace Mechanize\Plugins;

class Schema extends AbstractPlugin implements PluginInterface
{
	public function getSchemas()
	{
		$schemas = $this->find('//*[@itemscope]');

		$elements = array();
		foreach ($schemas as $i => $schema) {
			// Find Properties
			$elements[$i]['type'] = $schema->itemtype;

			// Find more elements
			$properties = $schema->find('//*[@itemprop]');

			if ($properties->length === 0) {
				continue;
			}

			foreach ($properties as $property) {
				if ($property->hasAttribute('content')) {
					$elements[$i][$property->itemprop] = $property->content;
				} else {
					$elements[$i][$property->itemprop] = trim($property->getText());
				}
			}
		}

		echo '<pre>';
		print_r($elements);

		exit;
	}

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