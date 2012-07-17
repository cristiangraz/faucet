<?php

namespace Mechanize\Plugins\Pinterest;

use Mechanize\Plugins\AbstractPlugin;
use Mechanize\Plugins\PluginInterface;
use Mechanize\Plugins\Facebook\OpenGraph;

class Pin extends AbstractPlugin implements PluginInterface
{
	/**
	 * Holds the OpenGraph plugin to parse og tags
	 *
	 * @var object Mechanize\Plugins\Facebook\OpenGraph;
	 */
	private $openGraph = null;

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
	 * The url to this pin
	 *
	 * @var string
	 */
	private $url = null;

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
	 * Returns the canonical url for this pin
	 *
	 * @return string
	 */
	public function getUrl()
	{
		$this->getPin();
		
		return $this->url;
	}

	/**
	 * Return the absolute url to the pinned image
	 *
	 * @var string
	 */
	public function getImage()
	{
		$this->getPin();

		return $this->openGraph->getTag('og.image');
	}

	/**
	 * Return the pin's description
	 *
	 * @var string
	 */
	public function getDescription()
	{
		$this->getPin();

		return $this->openGraph->getTag('og.description');
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

		if ($attr === 'href' || $attr === 'name') {
			return $this->source[$attr];
		}

		return $this->source;
	}

	/**
	 * Whether or not this pin has been repinned
	 *
	 * @return bool
	 */
	public function hasRepins()
	{
		$this->getPin();

		return $this->openGraph->getTag('pinterestapp.repins') != 0;
	}

	/**
	 * Whether or not this pin has any likes
	 *
	 * @return bool
	 */
	public function hasLikes()
	{
		$this->getPin();

		return $this->openGraph->getTag('pinterestapp.likes') != 0;
	}

	/**
	 * Whether or not this pin has any comments
	 *
	 * @return bool
	 */
	public function hasComments()
	{
		$this->getPin();

		return $this->openGraph->getTag('pinterestapp.comments') != 0;
	}

	/**
	 * Returns the number of repins
	 * 
	 * @return int
	 */
	public function getRepins()
	{
		$this->getPin();

		return intval($this->openGraph->getTag('pinterestapp.repins'));
	}

	/**
	 * Returns the number of likes
	 * 
	 * @return int
	 */
	public function getLikes()
	{
		$this->getPin();

		return intval($this->openGraph->getTag('pinterestapp.likes'));
	}

	/**
	 * Returns the number of comments
	 * 
	 * @return int
	 */
	public function getComments()
	{
		$this->getPin();

		return intval($this->openGraph->getTag('pinterestapp.comments'));
	}

	/**
	 * Returns the url to the pinboard this pin belongs to
	 *
	 * @return string
	 */
	public function getPinboard($attr)
	{
		$this->getPin();

		if ($attr === 'name') {
			return $this->openGraph->getTag('og.title');
		}

		if ($attr === 'href') {
			return $this->openGraph->getTag('pinterestapp.pinboard');
		}

		if ($attr === 'pin.count') {
			return (int) preg_replace('/ pins$/', '', $this->selectOne('div.pinBoard h4')->getText());
		}
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
		if (!is_null($this->openGraph)) {
			return;
		}

		$this->openGraph = new OpenGraph;
		$this->openGraph->setParser($this->getParser());

		$pinner = $this->selectOne('p#PinnerName > a');
		$this->pinner = array(
			'name' => $pinner->getText(),
			'href' => $this->openGraph->getTag('pinterestapp:pinner')
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
			'name' => trim($source->getText()),
			'href' => $this->openGraph->getTag('pinterestapp.source')
		);

		$this->url = $this->getParser()->getAbsoluteUrl($this->find('/html/head/link[@rel="canonical"]')->href);
	}
}