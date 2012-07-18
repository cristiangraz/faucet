<?php

namespace Faucet\Plugins\Microformats;

use Faucet\Plugins\AbstractPlugin;
use Faucet\Plugins\PluginInterface;

use Faucet\Dom\Xpath\Expression;

class Hreview extends Microformat implements PluginInterface
{
	private $review = null;

	private $ratings = array();

	/**
	 * Parses the hReview microformat
	 * 
	 * @return mixed Array or bool false
	 */
	public function getReview()
	{
		if (!is_null($this->review)) {
			return $this;
		}

		$expression = new Expression;
		$expression->setBase('//*')->hasClass('hreview-aggregate');

		$this->review = $this->findOne($expression);

		if ($this->review->length === 0) {
			return false;
		}

		$votes = $this->review->selectOne('.count');
		$votes = $this->getValue($votes);

		$average = $this->review->selectOne('.rating');
		$averageTry = $average->selectOne('.average');

		if ($averageTry->length === 1) {
			$average = $averageTry;
		}
		$average = preg_match('/([0-5](?:\.[0-9])?)/', $this->getValue($average), $matches);
		$average = $matches[0];

		$best = $this->review->selectOne('.rating .best');

		if ($best->length === 0) {
			$best = 5;
		} else {
			$best = $this->getValue($best);
		}

		$this->ratings = array(
			'rating' => $average,
			'best.rating' => $best,
			'total.votes' => preg_replace('/[^0-9]/', '', $votes)
		);

		return $this;
	}

	/**
	 * Checks to see if the page has hRecipe markup
	 * 
	 * @return bool
	 */
	public function hasRating()
	{
		$this->getReview();

		return $this->review->length > 0;
	}

	/**
	 * Returns the average voted rating
	 * 
	 * @return int
	 */
	public function getRating()
	{
		return $this->ratings['rating'];
	}

	/**
	 * Returns the best rating this item can have
	 * 
	 * @return int
	 */
	public function getBestRating()
	{
		return $this->ratings['best.rating'];
	}

	/**
	 * Returns the total number of votes cast
	 * 
	 * @return int
	 */
	public function getTotalVotes()
	{
		return $this->ratings['total.votes'];
	}

	/**
	 * Define the plugin's alias
	 *
	 * @return string
	 */
	public function getAlias()
	{
		return 'microformats.hreview';
	}
}