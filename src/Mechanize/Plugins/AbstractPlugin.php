<?php

namespace Mechanize\Plugins;

use Mechanize\Dom\Parser;

abstract class AbstractPlugin
{
	/**
	 * Parser object
	 *
	 * @var object Mechanize\Dom\Parser
	 */
	protected $parser = null;

	/**
	 * Holds the plugin's options. Currently does nothing.
	 * @todo build this out with support for default options
	 *
	 * @var array
	 */
	protected $options = array();

	/**
	 * Sets the Parser object
	 *
	 * @param object Mechanize\Dom\Parser
	 *
	 * @return void
	 */
	public function __construct() {}

	/**
	 * Return the parser object
	 *
	 * @return object Mechanize\Dom\Parser
	 */
	public function getParser()
	{
		return $this->parser;
	}

	/**
	 * Set the parser
	 *
	 * @param object $parser Mechanize\Dom\Parser
	 */
	public function setParser(Parser $parser)
	{
		$this->parser = $parser;

		return $this;
	}

	/**
     * Convenience method. Find any element on the page using an xpath selector
     *
     * @return Mechanize/Elements
     **/
	public function find($selector = false, $limit = -1, $context = false)
	{
		return $this->parser->find($selector, $limit, $context);
	}

	/**
     * Convenience method. Find any element on the page using an xpath selector but only return the first result
     *
     * @return Mechanize/Elements
     **/
	public function findOne($selector = false, $context = false)
	{
		return $this->parser->findOne($selector, $context);
	}
}