<?php

namespace Mechanize\Dom;

use Mechanize\Elements;
use Mechanize\Element;

use Guzzle\Http\Message\Response;

class Parser
{
	/**
	 * Body, ResponseObject. NEED Uri info
	 */

	/**
	 * Holds the response object
	 *
	 * @var object Guzzle\Http\Message\Response
	 */
	protected $response = null;

	/**
	 * Holds the body associated with the response
	 *
	 * @var string
	 */
	protected $body = null;

	/**
	 * Holds the DOMDocument object
	 *
	 * @var object DOMDocument
	 */
	protected $dom = null;

	/**
	 * Holds the DOMXPath object
	 *
	 * @var object DOMXpath
	 */
	protected $xpath = null;

	/**
     * Holds the scheme for the request
     *
     * @var string
     */
    protected $scheme = null;

    /**
     * Holds the hostname for the current request
     *
     * @var string
     */
    protected $host = null;

    /**
     * Holds the url scheme and hostname for the current request
     *
     * @var string
     */
    protected $domainRoot = null;

    /**
     * Holds the url scheme hostname and path for the current request
     *
     * @var string
     */
    protected $base = null;

	/**
	 * Sets the Response, DOMDocument, DomXPath objects
	 *
	 * @param object $response Guzzle\Http\Message\Response
	 * @param object $dom DomDocument
	 * @param object $xpath DOMXPath
	 *
	 * @return void
	 */
	public function __construct(Response $response, \DOMDocument $dom, \DOMXPath $xpath)
	{
		$this->response = $response;
		$this->body = $response->getBody();

		$this->dom = $dom;
		$this->xpath = $xpath;
	}

	/**
	 * Sets the URI of the current request. Needed for generating absolute urls
	 *
	 * @param string $uri
	 *
	 * @return void
	 */
	public function setUri($uri)
	{
		$url = parse_url($uri);

        $this->scheme = $url['scheme'];
        $this->host = $url['host'];
        $this->domainRoot = $url['scheme'] . '://' . $url['host'];
        $this->base = $this->domainRoot;

        if (isset($url['path'])) {
            // Does path end with an extension?
            if (false !== strpos($url['path'], '.')) {
                $path = rtrim($url['path'], '/');
                $remove = strrchr($path, '/');
                $path = str_replace($remove, '', $path);
                $this->base .= $path;
            } else {
                $this->base .= rtrim($this->base, '/');
            }
        }
	}

	public function getBody()
	{
		return $this->body;
	}

	/**
     * Find any element on the page using an xpath selector
     *
     * @return Mechanize/Elements
     **/
    public function find($selector = false, $limit = -1, $context = false)
    {
        return $this->getElements($selector, $limit, $context);
    }

    /**
     * Convenience method to find any element on the page using an xpath selector but only return the first result
     *
     * @return Mechanize/Elements
     **/
    public function findOne($selector = false, $context = false)
    {
        return $this->find($selector, 1, $context);
    }

    /**
     * Returns the page's contents (with any DOM modifications) similar to View > Page Source.
     *
     * @return string
     */
    public function getContents()
    {
    	if (is_null($this->dom)) {
            if (!$this->getBody()) {
                return '';
            }

            return $this->getBody();
        } else {
            return $this->dom->saveHTML();
        }

        return '';
    }

	/**
     * Takes a url and returns it as an absolute url
     *
     * @param string the url
     * @return string the absolute url
     **/
    public function absoluteUrl($url)
    {
        if (false !== preg_match('#^https?://#', $url)) {
            return $url;
        } elseif (substr($url, 0, 2) == '//') {
            // Path is absolute but relative to the url scheme
            return $this->scheme . $url;
        } elseif (substr($url, 0, 1) == '/') {
            // Path is relative to website root. $src already contains a slash separator
            return $this->domainRoot . $url;
        } elseif (substr($url, 0, 2) == './') {
            $base = rtrim($this->base, '/');
            return $this->domainRoot !== $base ? rtrim(str_replace(strrchr($base, '/'), '', $base), '/') . '/' . substr($url, 2) : $base . '/' . substr($url, 2);
        } elseif (substr($url, 0, 3) == '../') {
            // Relative url from current location
            return $this->moveBack($url, $this->base);
        } else {
            return rtrim($this->base, '/') . '/' . ltrim($url, '/');
        }
    }

	/**
     * Internal method used to convert relative urls in the ../ format to an absolute url
     *
     * @param string the relative url
     * @param bool false|string the baseUrl (scheme, hostname, and path)
     **/
    protected function moveBack($relativeUrl, $baseUrl = false)
    {
        $baseUrl = $baseUrl === false ? $this->baseUrl : $baseUrl;
    
        if (substr($relativeUrl, 0, 3) == '../') {
            $relativeUrl = substr($relativeUrl, 3);

            // Move the baseUrl Back One step
            $remove = strrchr($baseUrl, '/');
            $baseUrl = str_replace($remove, '', $baseUrl);

            if (substr($relativeUrl, 0, 3) == '../') {
                return $this->moveBack($relativeUrl, $baseUrl);
            }
        }

        $final = $baseUrl . '/' . ltrim($relativeUrl, '/');

        // Add this in for sites that use ../ when they are already in the root directory
        if (false !== preg_match('#^https?://#', $final)) {
            $scheme = substr($final, 0, 5) == 'https' ? 'https' : 'http';

            $final = str_replace($scheme . ':', '', $final);
            $final = $scheme . '://' . ltrim($final, '/');
        }

        return $final;
    }

	/**
     * Internal method used to get elements based on an xpath selector and an optional limit
     *
     * @param string $selector The xPath selector
     * @param int $limit The number of elements to return
     * @return mixed $context The dom node to use as the context for the search
     **/
    protected function getElements($selector = false, $limit = -1, $context = false)
    {
        $this->setupDom();

        if ($context === false) {
            $nodes = $this->domxpath->evaluate($selector);
        } else {
            if ($context instanceof Element) {
                $context = $context->getElement();
            }

            if ($context instanceof \DOMNode) {
                $nodes = $this->domxpath->evaluate($selector, $context);
            } else {
                throw new Exception('Invalid context node');
            }
        }

        $elements = new Elements;

        if ($nodes->length > 0) {
            $i = 1;
            foreach ($nodes as $node) {
                $elements->addElement(new Element($node, $this));

                if ($limit > 0 && $i === $limit) {
                    break;
                }

                ++$i;
            }
        }

        return $elements;
    }
}