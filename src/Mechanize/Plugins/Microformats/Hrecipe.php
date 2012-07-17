<?php

namespace Mechanize\Plugins\Microformats;

use Mechanize\Plugins\AbstractPlugin;
use Mechanize\Plugins\PluginInterface;
use Mechanize\Dom\Elements;

use Mechanize\Dom\Xpath\Expression;

class Hrecipe extends Microformat implements PluginInterface
{
	/**
	 * Holds the recipe Element
	 * 
	 * @var object Mechanize\Dom\Elements
	 */
	private $recipe = null;

	/**
	 * Holds the context to search for the recipe
	 * 
	 * @var object Mechanize\Dom\Elements
	 */
	private $context = false;

	/**
	 * Searches for the recipe object
	 * 
	 * @return Mechanize\Dom\Elements
	 */
	public function getRecipe()
	{
		if (is_null($this->recipe)) {
			$expression = new Expression;
			$expression->setBase('//*')->hasClass('hrecipe');

			$this->recipe = $this->findOne($expression, $this->context);
		}

		return $this->recipe;
	}

	/**
	 * Sets the context to search the recipe in
	 * 
	 * @param object Mechanize\Dom\Elements
	 * 
	 * @return object Mechanize\Plugins\Microformats\Hrecipe
	 */
	public function setContext(Elements $context)
	{
		$this->context = $context;

		return $this;
	}

	/**
	 * Checks to see if the page has hRecipe markup
	 * 
	 * @return bool
	 */
	public function hasRecipe()
	{
		return $this->getRecipe()->length > 0;
	}

	/**
	 * Gets the recipe title
	 * 
	 * @return string
	 */
	public function getTitle()
	{
		$title = $this->getRecipe()->selectOne('.fn');

		if ($title->length === 0) {
			return false;
		}

		return $title->getText();
	}

	/**
	 * Gets the recipe summary
	 * 
	 * @return string
	 */
	public function getSummary()
	{
		$summary = $this->getRecipe()->selectOne('.summary');

		if ($summary->length === 0) {
			return false;
		}

		return $summary->getText();
	}

	/**
	 * Gets the rating based on the hReview microformat
	 * 
	 * @return object Mechanize\Plugins\Microformats\Hreview
	 */
	public function getRating()
	{
		$rating = new Hreview;

		return $rating->setParser($this->getParser())->getReview();
	}

	/**
	 * Finds the recipe prep time
	 * 
	 * @return array An array with hours/minutes keys
	 */
	public function getPrepTime()
	{
		$time = $this->getRecipe()->selectOne('.preptime');

		if ($time->length === 0) {
			return false;
		}

		$value = $time->selectOne('.value-title');

		if ($value->length === 1) {
			$time = $value->title;

			return $this->convertTime($time);
		}

		return $this->convertTime($time->getText());
	}

	/**
	 * Finds the recipe cook time
	 * 
	 * @return array An array with hours/minutes keys
	 */
	public function getCookTime()
	{
		$time = $this->getRecipe()->selectOne('.cooktime');

		if ($time->length === 0) {
			return false;
		}

		$value = $time->selectOne('.value-title');

		if ($value->length === 1) {
			$time = $value->title;

			return $this->convertTime($time);
		}

		return $this->convertTime($time->getText());
	}

	/**
	 * Finds the recipe ingredients
	 * 
	 * @return array
	 */
	public function getIngredients()
	{
		$i = array();

		// Find all .ingredient classes, but don't match .ingredients
		// This selector also ignores when child nodes of an ingredient are wrapped in a tag with a class of ingredient
		$ingredients = $this->getRecipe()->find('//*[contains(concat(" ", normalize-space(@class), " "), " ingredient ") and not(ancestor::*[contains(concat(" ", normalize-space(@class), " "), " ingredient ")])]');

		if ($ingredients->length > 0 ) {
			foreach ($ingredients as $index => $ingredient) {
				$name = $ingredient->selectOne('.name');
				$value = $ingredient->selectOne('.value');
				$type = $ingredient->selectOne('.type');

				if ($type->length === 1 && $value->length === 1) {
					$i[$index]['value'] = $value->getText();
					$i[$index]['type'] = $type->getText();
					$i[$index]['name'] = $name->getText();

					continue;
				}
				
				$i[$index] = $ingredient->getText();
			}
		}

		return $i;
	}

	/**
	 * Finds the recipe instructions
	 * 
	 * @param string $separator The xpath that separates a singular instructions field
	 *  For example, when one .instructions element is returned but each instruction is separated by p tags
	 * 
	 * @return mixed an array of instructions or xhtml instructions
	 */
	public function getInstructions($separator = false)
	{
		$i = array();
		$instructions = $this->getRecipe()->select('.instructions');

		if ($instructions->length === 0) {
			return false;
		}

		// Look for individually tagged instructions (.instruction maybe?)
		if ($instructions->getTag() === 'ol' || $instructions->getTag() === 'ul' || 1 === $length = $instructions->findOne('ol | ul')->length) {
			if (isset($length) && $length === 1) {
				$instructions = $instructions->findOne('ol | ul');
			}

			// Try looking for li instructions
			$instructions = $instructions->find('li');

			foreach ($instructions as $index => $instruction) {
				$i[$index] = $instruction->getText();
			}
		} else {
			if ($instructions->length === 1) {
				$i = $instructions->getText();
			} else {
				if (false !== $separator) {
					$instructions = $instructions->getElement(0)->find($separator);
				}

				foreach ($instructions as $instruction) {
					if ($instruction->hasText()) {
						$i[] = $instruction->getText();
					}
				}
			}
		}

		return $i;
	}

	/**
	 * Gets the photo associated with the recipe
	 * 
	 * @return mixed The absolute url of the photo or bool false if one isn't found
	 */
	public function getPhoto()
	{
		$photo = $this->getRecipe()->selectOne('.photo');

		if ($photo->length === 1) {
			return $this->getParser()->getAbsoluteUrl($photo->src);
		}

		return false;
	}

	/**
	 * Define the plugin's alias
	 *
	 * @return string
	 */
	public function getAlias()
	{
		return 'microformats.hrecipe';
	}

	/**
	 * Converts microformat time into hours and minutes
	 * 
	 * @param string $time The microformat time
	 * 
	 * @return array An array with hours/minutes keys
	 */
	private function convertTime($time)
	{
		preg_match('/^(?:P|C)T(?:([0-9]+)H)?(?:([0-9]+)M)?/', $time, $matches);

		return array(
			'hours' => (int) $matches[1],
			'minutes' => isset($matches[2]) ? (int) $matches[2] : 0
		);
	}

	/**
	 * Parses ingredients to pull out name, type, and value
	 * @todo nowhere near being ready
	 */
	private function parseIngredient($ingredient)
	{
		// What about 1 1/3

		preg_match('#^\s*(?:\(?([0-9]+(?:\s*/\s*[0-9])?))?\s*(c|cups?|tsps?|teaspoons?|tbsps?|tablespoons?|lbs?|pounds?)? (.*)$#i', $ingredient, $matches);

		print_r($matches);

		return $ingredient;
	}
}