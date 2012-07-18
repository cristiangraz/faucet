<?php

namespace Faucet\Plugins;

class Seo extends Html
{
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

	/**
	 * Checks for a noindex in the robots meta tag.
	 * Note: This does not check against the robots.txt file
	 * 
	 * @return bool
	 */
	public function isIndexable()
	{
		$robots = $this->findOne('/html/head/meta[@name="robots"]');

		if ($robots->length === 0) {
			return true;
		}

		return false === strpos($robots->content, 'noindex');
	}

	/**
	 * Define the plugin's alias
	 *
	 * @return string
	 */
	public function getAlias()
	{
		return 'seo';
	}
}