<?php

namespace Mechanize\Plugins;

class Seo extends Html implements PluginInterface
{
	protected $canonicalUrl = null;

	/**
	 * Determines if the url has a canonical url
	 *
	 * @return mixed The canonical url or bool false
	 */
	public function hasCanonical()
	{
		if (!is_null($this->canonicalUrl)) {
			return !false === $this->canonicalUrl;
		}

		$canonical = $this->findOne('/html/head/link[@rel="canonical"]');

		if ($canonical->length === 1) {
			$this->canonicalUrl = $this->getParser()->absoluteUrl($canonical->href);

			return $this->hasCanonical();
		}

		return false;
	}

	/**
	 * Returns the absolute canonical url to the page
	 *
	 * @return mixed The canonical url or bool false
	 */
	public function getCanonical()
	{
		if ($this->hasCanonical()) {
			return $this->canonicalUrl;
		}

		return false;
	}

	public function getAlias()
	{
		return 'seo';
	}
}