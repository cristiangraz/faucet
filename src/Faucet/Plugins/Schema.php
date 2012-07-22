<?php

namespace Faucet\Plugins;

class Schema extends AbstractPlugin implements PluginInterface
{
	/**
	 * Class constants
	 */
	const ANCESTOR_OF 		= 	'data-ancestor-of';
	const ANCESTOR_TYPE		=	'data-ancestor-type';

	/**
	 * Parses for Schemas as defined at http://schema.org
	 * 
	 * @return array An array of schemas
	 */
	public function getSchemas()
	{
		// Create a clone of the original parser - getSchema() will remove nodes as they are found
		$originalParser = clone $this->getParser();

		$i=0;
		while ($this->getSchema($i)) {
			// Finds the innermost schema and works its way outwards
			$i++;
		}

		// Revert back to the original Parser object
		$this->setParser($originalParser);

		// Cleanup
		foreach ($this->schemas as &$element) {
			if (isset($element[self::ANCESTOR_OF])) {
				$id = $element[self::ANCESTOR_OF];

				$type = $this->schemas[$id]['type'];
				if (isset($element[self::ANCESTOR_TYPE])) {
					$type = $element[self::ANCESTOR_TYPE];

					unset($element[self::ANCESTOR_TYPE]);
				}

				$element[$type] = $this->schemas[$id];

				unset($element[self::ANCESTOR_OF]);
				unset($this->schemas[$id]);
			}
		}

		// Return everything the proper order since we went from the innermost node outwards
		$this->schemas = array_reverse($this->schemas);

		return $this->schemas;
	}

	/**
	 * Parses out the Schema type from the itemtype attribute
	 * 
	 * @param string $type The value of the itemtype attribute
	 *
	 * @return string
	 */
	public function getType($type)
	{
		if (false !== preg_match('#/([a-z]+)$#i', $type, $matches)) {
			return strtolower($matches[1]);
		}

		return $type;
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

	/**
	 * Parses an individual schema
	 * 
	 * @param integer $schemaIndex The current index used for pointers to other schemas
	 * @return bool
	 */
	private function getSchema($schemaIndex = 0)
	{
		// Find the innermost schema
		$schemas = $this->findOne('//*[@itemscope and not(descendant::*[@itemscope])]');

		if ($schemas->length === 0) {
			return false;
		}

		$elements = array();
		foreach ($schemas as $i => $schema) {
			// Find Properties
			$elements['type'] = $this->getType($schema->itemtype);
			$elements['schema'] = $schema->itemtype;

			// If a pointer was previously set, add it to the element
			if ($schema->hasAttribute(self::ANCESTOR_OF)) {
				$elements[self::ANCESTOR_OF] = $schema->getAttribute(self::ANCESTOR_OF);
			}

			if ($schema->hasAttribute(self::ANCESTOR_TYPE)) {
				$elements[self::ANCESTOR_TYPE] = $schema->getAttribute(self::ANCESTOR_TYPE);
			}

			// Set a unique id on each schema type for identification purposes
			$schema->setAttribute('id', 'schema-id-' . $i);

			// See if this Schema extends from a parent and set a pointer on the parent
			$ancestor = $this->findOne('//*[@id="schema-id-' . $i . '"]/ancestor::*[@itemscope][last()-1]');
			if ($ancestor->length === 1) {
				$ancestor->setAttribute(self::ANCESTOR_OF, $schemaIndex);

				if ($elements['type'] === 'person' && ($schema->itemprop === 'author' || false !== strpos($schema->rel, 'author'))) {
					// This is a Person schema representing an author
					$ancestor->setAttribute(self::ANCESTOR_TYPE, 'author');
				}
			}
			
			$properties = $this->find('//*[@itemscope and @id = "schema-id-' . $i . '"]//*[@itemprop]');

			if ($properties->length > 0) {
				foreach ($properties as $property) {

					$isUrl = false !== stripos($property->itemprop, 'url');

					// If the property is an ancestor of anything beginning with schema-id- that does not equal schema-id-$i, skip
					if ($property->hasAttribute('content')) {
						$elements[$property->itemprop] = $property->content;
					} else {
						if ($property->getTag() === 'img') {
							$elements[$property->itemprop] = $this->getParser()->getAbsoluteUrl($property->src);
						} elseif ($property->itemprop === 'image' || $property->itemprop === 'thumbnailUrl') {
							// Look for divs, etc marked up as an img where an img tag is embedded
							$img = $property->findOne('img');

							if ($img->length === 1) {
								$elements[$property->itemprop] = $this->getParser()->getAbsoluteUrl($img->src);
							}
						} elseif ($isUrl && ($property->getTag() === 'a' || $property->getTag() === 'link')) {
							$elements[$property->itemprop] = array(
								'text' => $property->getText(),
								'href' => $this->getParser()->getAbsoluteUrl($property->href)
							);
						} elseif ($property->itemprop === 'datePublished') {
							$elements[$property->itemprop] = new \DateTime($property->itemprop);
						} else {
							$elements[$property->itemprop] = $property->getText();
						}
					}
				}

				// Remove from the dom
				$schemas->remove();
			}
		}

		$this->schemas[$schemaIndex] = $elements;

		return true;
	}
}