<?php

namespace Mechanize\Plugins;

use Zend\Validator\Regex;

class OpenGraph extends AbstractPlugin implements PluginInterface
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

        $elements = $this->find('/html/head/meta')->validate('property', array(
            new Regex('/^(og|article|book|profile|video):/')
        ));

        if ($elements->length === 0) {
            return false;
        }

        foreach ($elements as $element) {
            $property = explode(':', $element->property);
            $value = trim($element->content);

            $base = $property[0];
            if ($base === 'og') {
                $this->tags['og'][$property[1]] = $value;
                continue;
            }

            // Finish building out tags array
            if (isset($property[2])) {
                $this->tags[$property[0]][$property[1]][$property[2]] = $value;
            } elseif (isset($property[1])) {
                $this->tags[$property[0]][$property[1]] = $value;
            }
        }

        return $this->tags;
    }

    /**
     * Returns the type of document this is based on the open graph tags
     *
     * @return string
     */
    public function getType()
    {
        $this->getTags();

        if (!isset($this->tags['og']['type'])) {
            return false;
        }

        return $this->tags['og']['type'];
    }

    /**
     * Returns the site name defined in the open graph tags
     *
     * @return mixed String or bool false
     */
    public function getSiteName()
    {
        $this->getTags();

        if (!isset($this->tags['og']['site_name'])) {
            return false;
        }

        return $this->tags['og']['site_name'];
    }

    /**
     * Returns the image for this page defined in the open graph tags
     *
     * @return mixed String or bool false
     */
    public function getImage()
    {
        $this->getTags();

        if (!isset($this->tags['og']['image'])) {
            return false;
        }

        return $this->tags['og']['image'];
    }

    /**
     * Returns bool true if the document type is an article
     *
     * @return bool
     */
    public function isArticle()
    {
        $this->getTags();

        return 'article' === $this->getType();
    }

    /**
     * Returns bool true if the document has video defined in the open graph tags
     *
     * @return bool
     */
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