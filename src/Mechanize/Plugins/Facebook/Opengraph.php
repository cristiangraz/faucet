<?php

namespace Mechanize\Plugins\Facebook;

use Mechanize\Plugins\AbstractPlugin;
use Mechanize\Plugins\PluginInterface;

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
     * Returns the list of open graph tags
     *
     * @return string
     **/
    public function getTags()
    {
        if (!is_null($this->tags)) {
            return $this->tags;
        }

        $this->tags = array();

        $elements = $this->find('/html/head/meta')->validate('property', array(
            new Regex('/^(og|article|book|profile|video|[a-z]+app):/')
        ));

        if ($elements->length === 0) {
            return false;
        }

        foreach ($elements as $element) {
            $property = str_replace(':', '.', $element->property);
            $value = trim($element->content);

            $keys = explode('.', $property);

            if ('tag' === array_pop($keys)) {
                $this->tags[$property][] = $value;

                continue;
            }

            $this->tags[$property] = $value;
        }

        return $this->tags;
    }

    /**
     * Returns an opengraph tag
     * 
     * @param  string $tag The name of the tag in the format og.video.width
     * 
     * @return mixed String or bool false if that tag doesn't exist
     */
    public function getTag($tag)
    {
        $this->getTags();

        if (isset($this->tags[$tag])) {
            return $this->tags[$tag];
        }

        return false;
    }

    /**
     * Returns the type of document this is based on the open graph tags
     *
     * @return string
     */
    public function getType()
    {
        return $this->getTag('og.type');
    }

    /**
     * Returns the site name defined in the open graph tags
     *
     * @return mixed String or bool false
     */
    public function getSiteName()
    {
        return $this->getTag('og.sitename');
    }

    /**
     * Returns the title of the page as defined in the open graph tags
     * 
     * @return mixed String or bool false
     */
    public function getTitle()
    {
        return $this->getTag('og.title');
    }

    /**
     * Returns the description of the page as defined in the open graph tags
     * 
     * @return mixed String or bool false
     */
    public function getDescription()
    {
        return $this->getTag('og.description');
    }

    /**
     * Returns the image for this page defined in the open graph tags
     *
     * @return mixed String or bool false
     */
    public function getImage()
    {
        return $this->getTag('og.image');
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