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

	/**
	 * Return dofollow links
	 *
	 * @param mixed $returnElements string or bool false
	 */
	public function followLinks($returnElements = false)
	{
		$links = $this->find('//a');

		if ($links->length === 0) {
			return false;
		}

		$followLinks = array();
		foreach ($links as $index => $link) {
			if ($link->hasAttribute('rel') && false !== strpos('nofollow', $link->rel)) {
				$followLinks[] = $this->getParser()->getAbsoluteUrl($link->href);
				continue;
			}

			$link->removeElement($index);
		}

		if ($returnElements) {
			return $links;
		}

		return $followLinks;
	}

	/**
	 * Return dofollow links
	 *
	 * @param mixed $returnElements string or bool false
	 */
	public function nowFollowLinks($returnElements = false)
	{
		$links = $this->find('//a[contains(@rel, "nofollow")]');

		if ($links->length === 0) {
			return false;
		}

		if ($returnElements) {
			return $links;
		}

		$noFollowLinks = array();
		foreach ($links as $link) {
			$noFollowLinks[] = $this->getParser()->getAbsoluteUrl($link->href);
		}
	}

	public function getAlias()
	{
		return 'seo';
	}
}