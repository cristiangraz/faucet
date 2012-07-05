<?php

namespace Mechanize\Plugins;

use Zend\Validator\Regex;

class Html extends AbstractPlugin implements PluginInterface
{
	/**
     * Returns the page title of the current page
     *
     * @return string
     **/
    public function getTitle()
    {
        return $this->findOne('/html/head/title')->getText();
    }

    /**
     * Helper method to get all links on the page
     *
     * @return Mechanize/Elements
     **/
    public function getLinks()
    {
        return $this->find('/html/body//a[@href]');
    }

    /**
     * Helper method to get all images on the page
     *
     * @return Mechanize/Elements
     **/
    public function getImages()
    {
        return $this->find('/html/body//img[@src]');
    }

    /**
     * Helper method to get all forms on the page
     *
     * @return Mechanize/Elements
     **/
    public function getForms()
    {
        return $this->find('/html/body//form');
    }

    /**
     * Helper method to get all javascript files on the page
     *
     * @return Mechanize/Elements
     **/
    public function getJavascript()
    {
        return $this->find('//script[@href]');
    }

    /**
     * Helper method to get all the stylesheets on the page
     * TODO: Grab @import stylesheets
     *
     * @return Mechanize/Elements
     **/
    public function getStylesheets()
    {
        return $this->find('//link[@rel=\'stylesheet\' and @href]');
    }

    /**
     * Returns an array of meta keywords
     *
     * @return mixed array of keywords or false
     */
    public function getMetaKeywords()
    {
        $keywords = $this->findOne('/html/head/meta[@name="keywords"]');

        if ($keywords->length === 0) {
            return false;
        }

        return preg_split('/, */', $keywords->content, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Returns the meta description
     *
     * @return mixed string or false
     */
    public function getMetaDescription()
    {
        $description = $this->findOne('/html/head/meta[@name="description"]');

        if ($description->length === 0) {
            return false;
        }

        return trim($description->content);
    }

    /**
     * Returns the site's favicon
     *
     * @return mixed Either the url to the favicon or false
     */
    public function getFavicon()
    {
        $favicon = $this->find('/html/head/link')->validate('rel', array(
            new Regex('/^(?:shortcut )?icon$/')
        ))->limit(1);

        if ($favicon->length === 0) {
            return false;
        }

        return $favicon->href;
    }
	
	/**
	 * Define the plugin's alias
	 *
	 * @return string
	 */
	public function getAlias()
	{
		return 'html';
	}
}