<?php

namespace Mechanize\Plugins;

use Zend\Validator\Regex;

class Html extends AbstractPlugin implements PluginInterface
{
	/**
	 * Holds the open graph tags
	 *
	 * @var array
	 */
	protected $tags = null;

    /**
     * Determine if the page uses open graph tags
     *
     * @return bool
     */
    public function hasTags()
    {
        if (is_null($this->tags)) {
            $this->getTags();
        }

        return !empty($this->tags);
    }

	/**
     * Returns the page title of the current page
     *
     * @return string
     **/
    public function getTags()
    {
        if (!is_null($this->tags)) {
            return $this->tags;
        }

        $elements = $this->find('/html/head/meta')->validate(array(
        	new Regex('/#^(og|article|book|profile|video|):/')
        ));

        if ($elements->length === 0) {
        	return false;
        }

        foreach ($elements as $element) {
        	$property = explode(':', $element->property);
        	$value = $element->content;

        	$base = $property[0];
        	if ($base === 'og') {
        		$this->tags['og'][$property[1]] = $value;
        		continue;
        	}

        	// Finish building out tags array
        }
    }

    public function isArticle()
    {
        $this->getTags();

    	return isset($this->tags['article']);
    }

    public function hasVideo()
    {
        $this->tags();
        
    	return isset($this->tags['video']);
    }
	
	/**
	 * Define the plugin's alias
	 *
	 * @return string
	 */
	public function getAlias()
	{
		return 'facebook.opengraph';
	}
}