<?php

namespace Mechanize;

use Guzzle\Http\Client as HttpClient;
use Guzzle\Http\CookieJar\ArrayCookieJar;
use Guzzle\Http\Plugin\CookiePlugin;
use Guzzle\Http\Plugin\HistoryPlugin;
use Guzzle\Http\Exception\BadResponseException; 
use Guzzle\Http\Message\Response;

use Mechanize\Delay\DelayInterface;
use Mechanize\Delay\NoDelay;
use Mechanize\Elements;
use Mechanize\Element;

/**
 * A PHP implementation of Andy Lester's WWW::Mechanize for Perl
 * Although the interface is similar at times, this is not a direct port of WWW::Mechanize
 *
 * @package Mechanize;
 * @copyright Copyright 2012 Cristian Graziano
 */
class Client
{
    /**
     * Holds an instance of the HTTP Client
     *
     * @var object Guzzle\Client
     */
    protected $httpClient;

    /**
     * The address to the current page
     *
     * @var string
     */
    protected $uri = null;

    /**
     * Holds an instance of the Delay object
     *
     * @var object Mechanize\Delay
     */
    protected $delayStrategy;

    /**
     * The maximum number of redirects to allow for request
     *
     * @var int
     */
    protected $maxRedirects = 3;

    /**
     * The maximum timeout for requests
     *
     * @var int
     */
    protected $timeout = 20;

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
     * Whether or not to freeze the referrer header on a specific url
     * Designed to be used during loops where the header should appear to be coming from
     * the same place
     *
     * @var bool
     */
    protected $freezeReferrer = false;

    /**
     * Holds the history of http requests
     *
     * @var object Guzzle\Http\Plugin\HistoryPlugin
     */
    protected $history = null;

    /**
     * Holds the response object
     *
     * @var object Guzzle\Http\Message\Response
     */
    protected $response = null;

    /**
     * HTML body of the request
     *
     * @var string
     */
    protected $body = null;

    /**
     * Holds an array of headers to send with the request
     *
     * @var array
     */
    protected $headers = array();

    /** 
     * User Agent Constants 
     *
     */
    const UA_MOZILLA_MAC        =   'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; en-US; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3';
    const UA_MOZILLA_WINDOWS    =   'Mozilla/5.0 (Windows; U; Windows NT 5.2; en-US; rv:1.9.2) Gecko/20091111 Firefox/3.6';
    const UA_IE_WINDOWS         =   'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0)';

    public function __construct($httpClient = false)
    {
        if ($httpClient instanceof HttpClient) {
            $this->httpClient = $httpClient;
        } else {
            $this->httpClient = new HttpClient;
            $this->history = new HistoryPlugin;

            $this->addClientSubscriber(new CookiePlugin(new ArrayCookieJar));
            $this->addClientSubscriber($this->history);
        }

        $this->setAgent(self::UA_MOZILLA_MAC);
        $this->delayStrategy(new NoDelay);
    }

    /**
     * Set the maximum number of redirects
     *
     * @param int
     *
     * @return object Mechanize/Client
     */
    public function setMaxRedirects($redirects)
    {
        $this->maxRedirects = $redirects;

        return $this;
    }

    /**
     * Set the timeout time in seconds
     *
     * @param int
     *
     * @return object Mechanize/Client
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Add a Guzzle plugin to the HTTP Client
     *
     * @param object
     */
    public function addClientSubscriber($subscriber)
    {
        $this->httpClient->addSubscriber($subscriber);

        return $this;
    }

    /**
     * Change the user agent with either a string or one of the class constants
     *
     * @param string $agent
     * @return object Mechanize\Client
     */
    public function setAgent($agent)
    {
        $this->httpClient->setUserAgent($agent, false);

        return $this;
    }

    /**
     * Return the response object
     *
     * @return Guzzle\Http\Message\Response;
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Returns the uri of the current request
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Fetches a url
     *
     * @param string $uri The uri to request
     * @param array $headers The headers to send with the request
     *
     * @return object Guzzle\Http\Message\Response
     */
    public function get($uri, $headers = array())
    {
        $this->delayStrategy->delay();

        // @todo why did I put this here again?
        if (false !== is_null($this->uri)) {
            $uri = $this->absoluteUrl($uri);
        }

        if (!is_null($this->uri) && !isset($headers['Referer'])) {
            $this->addHeaders(array(
                'Referer'  => $this->uri
            ));
        }

        // Reset the request elements
        $this->uri = $uri;
        $this->body = null;
        $this->dom = null;
        $this->domxpath = null;

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

        $headers = array_merge($this->headers, $headers);

        try {
            $this->response = $this->httpClient->get($this->uri, $headers)->send();
        } catch (BadResponseException $e) {
            $this->response = $e->getResponse();
        }

        // Reset headers so they are empty for future requests
        $this->resetHeaders();

        // Save the body
        $this->body = $this->response->getBody();

        return $this->response;
    }

    /**
     * Convenience method to determine if the response was successful (2xx | 304)
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->response->isSuccessful();
    }

    /**
     * Return the response body if a request has been made
     *
     * @return string
     */
    public function getBody()
    {
        if (!$this->response instanceof Response) {
            return false;
        }

        return $this->response->getBody();
    }

    /**
     * Add custom headers to the request
     *
     * @param array $headers An array of headers to send
     *
     * @return object Mechanize/Client
     */
    public function addHeaders(array $headers = array())
    {
        foreach ($headers as $k => $v) {
            $this->headers[$k] = $v;
        }

        return $this;
    }

    /**
     * Reset all custom headers
     *
     * @return object Mechanize/Client;
     */
    public function resetHeaders()
    {
        $this->headers = array();

        return $this;
    }

    /**
     * Return the array of custom headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Sets the delay strategy to use
     *
     * @param object Mechanize/Delay/DelayInterface
     *
     * @return object Mechanize/Client
     */
    public function delayStrategy(DelayInterface $delay)
    {
        $this->delayStrategy = $delay;

        return $this;
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
     * Follow a link on the page by specifying an xpath selector
     * @todo Does the href need to be converted to an absolute url for 100% compatability?
     *
     * @param string
     *
     * @return Mechanize/Client
     **/
    public function follow($selector = false)
    {
        $element = $this->getElements($selector, 1);

        return $this->get($element->getAttribute('href'));
    }

    /**
     * Takes a url and returns it as an absolute url
     *
     * @param string the url
     * @return string the absolute url
     **/
    public function absoluteUrl($url)
    {
        if (false !== preg_match('#https?://#', $url)) {
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

    public function submitForm(array $options = array()) {}

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
     * Move backwards one or more steps in the history
     *
     * @param int How many steps to move back (must be negative)
     */
    public function back($step = -1)
    {
        if (false === is_int($step) || $step >= 0) {
            throw new Exception('Moving backwards requires a negative integer');
        }

        return $this->move($step);
    }

    /**
     * Move forward one or more steps in the history
     *
     * @param int How many steps to move forward
     **/
    public function forward($step = 1)
    {
        if (false === is_int($step) || $step <= 0) {
            throw new Exception('Moving forward requires an integer greater than zero');
        }

        return $this->move($step);
    }

    protected function move(integer $step) {}

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
        if (false !== preg_match('#https?://#', $final)) {
            $scheme = substr($final, 0, 5) == 'https' ? 'https' : 'http';

            $final = str_replace($scheme . ':', '', $final);
            $final = $scheme . '://' . ltrim($final, '/');
        }

        return $final;
    }

    /**
     * Freeze the referrer on the current uri, or unfreeze
     *
     * @param bool true to freeze; false to unfreeze
     *
     * @return Mechanize\Client
     **/
    public function freezeReferrer($freeze = true)
    {
        if (true === $freeze) {
            $this->freezeReferrer = $this->getUri();
        } else {
            $this->freezeReferrer = false;
        }

        return $this;
    }

    /**
     * Returns the page's contents (with any DOM modifications) similar to View > Page Source.
     *
     * @return string
     */
    public function getContents()
    {
        if (is_null($this->dom)) {
            if (is_null($this->body)) {
                return '';
            }

            return $this->body;
        } else {
            return $this->dom->saveHTML();
        }

        return '';
    }

    /**
     * Retrieves a file and returns an stdClass with filename, contentType, and contents keys
     *
     * @param string $url The url where the file exists
     *
     * @return object stdClass
     */
    public function getFile($url)
    {
        $response = $this->get($url);

        if ($response->getStatusCode() != 200) {
            return false;
        }

        $file = new \stdClass;

        $file->filename = null;
        if (!is_null($response->getHeader('Content-Disposition'))) {
            if (preg_match('/.*filename="?([^";]+)"?.*/i', $response->getHeader('Content-Disposition'), $m)) {
                $file->filename = $m[1];
            }
        }
        
        $file->contentType = $response->getHeader('Content-Type');
        $file->contents = $this->getBody();

        return $file;
    }

    public function xpath() {}

    /**
     * Magic method to return the contents of the current page on echo() or print() calls
     *
     * @return string The contents of the page
     */
    public function __toString()
    {
        return $this->getContents();
    }

    /**
     * Sets up the DOMDocument and DOMXPath if it's not already in a lazy-load fashion
     *
     * @return void
     */
    protected function setupDom()
    {
        if (is_null($this->dom)) {
            $this->dom = new \DOMDocument;
            @$this->dom->loadHtml($this->body);
            $this->domxpath = new \DOMXPath($this->dom);
        }
    }
}