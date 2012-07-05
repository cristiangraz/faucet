<?php

namespace Mechanize\Plugins;

class Seo extends Html
{
	protected $canonicalUrl = null;

	/**
	 * Determines if the url has a canonical url
	 *
	 * @return mixed The canonical url or bool false
	 */
	public function hasCanonicalUrl()
	{
		if (!is_null($this->canonicalUrl)) {
			return $this->canonicalUrl !== null;
		}

		$canonical = $this->findOne('/html/head/link[@rel="canonical"]');

		if ($canonical->length === 1) {
			$this->canonicalUrl = $this->getParser()->getAbsoluteUrl($canonical->href);

			return $this->hasCanonicalUrl();
		}

		return false;
	}

	/**
	 * Returns the absolute canonical url to the page
	 *
	 * @return mixed The canonical url or bool false
	 */
	public function getCanonicalUrl()
	{
		if ($this->hasCanonicalUrl()) {
			return $this->canonicalUrl;
		}

		return false;
	}

	public function getAlias()
	{
		return 'seo';
	}
}