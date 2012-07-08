<?php

namespace Mechanize\Plugins\Pinterest;

use Mechanize\Plugins\AbstractPlugin;
use Mechanize\Plugins\PluginInterface;

class Pin extends AbstractPlugin implements PluginInterface
{
	/**
	 * Internal retrieved flag to determine if the data has been parsed
	 *
	 * @var bool
	 */
	private $parsed = false;

	/**
	 * Holds the pinner's name and href
	 *
	 * @var array
	 */
	private $pinner = array();

	/**
	 * The name and url for the source of the pin
	 *
	 * @var array
	 */
	private $source = array();

	/**
	 * The DateTime and pretty time for when this item was pinned
	 *
	 * @var array
	 */
	private $time = array();

	/**
	 * Information about how this pin was pinned
	 *
	 * @var array
	 */
	private $via = array();

	/**
	 * Returns the DateTime object representing when this pin occurred
	 *
	 * @return object DateTime
	 */
	public function getTime()
	{
		$this->getPin();

		return $this->time['datetime'];
	}

	/**
	 * Returns the pretty time representing when this pin occured
	 *
	 * @return string
	 */
	public function getPrettyTime()
	{
		$this->getPin();

		return $this->time['pretty'];
	}

	/**
	 * Returns information about the pinner
	 *
	 * @var mixed $attr The attribute key you want or bool false for the array
	 *
	 * @return mixed
	 */
	public function getPinner($attr = false)
	{
		$this->getPin();

		if ($attr === 'name' || $attr === 'href') {
			return $this->pinner[$attr];
		}

		return $this->pinner;
	}

	/**
	 * Returns information about the source of the pin
	 *
	 * @var mixed $attr The attribute key you want or bool false for the array
	 *
	 * @return mixed
	 */
	public function getSource($attr = false)
	{
		$this->getPin();

		if ($attr === 'href' || $attr === 'display') {
			return $this->source[$attr];
		}

		return $this->source;
	}

	/**
	 * Returns information about the how this pin was pinned
	 *
	 * @var mixed $attr The attribute key you want or bool false for the array
	 *
	 * @return mixed
	 */
	public function getPostedVia($attr = false)
	{
		$this->getPin();

		if ($attr === 'name' || $attr === 'href') {
			return $this->via[$attr];
		}

		return $this->via;
	}

	/**
	 * Define the plugin's alias
	 *
	 * @return string
	 */
	public function getAlias()
	{
		return 'pinterest.pin';
	}

	/**
	 * Lazy-load internal method to parse out the pin data
	 *
	 * @return void
	 */
	private function getPin()
	{
		if (true === $this->parsed) {
			return;
		}

		$pinner = $this->selectOne('p#PinnerName > a');
		$this->pinner = array(
			'name' => $pinner->getText(),
			'href' => $this->getParser()->getAbsoluteUrl($pinner->href)
		);

		$stats = $this->selectOne('p#PinnerStats');
		preg_match('/Pinned (([0-9]+) ((?:second|minute|hour|day|week|month|year)s?)) ago via (.*)/', $stats->getText(), $matches);

		$this->time = array(
			'datetime' => new \DateTime(date('Y-m-d g:i:s', strtotime('-' . $matches[1]))),
			'pretty' => $matches[1] . ' ago'
		);
		
		$this->via = array(
			'name' => $matches[4],
			'href' => $this->selectOne('p#PinnerStats a')->href
		);

		$source = $this->selectOne('p#PinSource a');

		$this->source = array(
			'href' => $this->getParser()->getAbsoluteUrl($source->href),
			'display' => trim($source->getText())
		);

		$this->parsed = true;
	}
}