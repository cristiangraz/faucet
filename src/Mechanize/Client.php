<?php

namespace Mechanize;

use Mechanize\Delay\DelayInterface;
use Mechanize\Delay\NoDelay;
use Mechanize\Plugins\PluginInterface;
use Mechanize\Dom\Parser;

use Guzzle\Http\Client as HttpClient;
use Guzzle\Http\CookieJar\ArrayCookieJar;
use Guzzle\Http\Plugin\CookiePlugin;
use Guzzle\Http\Exception\BadResponseException; 
use Guzzle\Http\Message\Response;

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
     * Whether or not to freeze the referrer header on a specific url
     * Designed to be used during loops where the header should appear to be coming from
     * the same place
     *
     * @var bool
     */
    protected $freezeReferrer = false;

    /**
     * Holds the response object
     *
     * @var object Guzzle\Http\Message\Response
     */
    protected $response = null;

    /**
     * Holds an array of headers to send with the request
     *
     * @var array
     */
    protected $headers = array();

    /**
     * Holds the parser object
     *
     * @var Mechanize\Dom\Parser
     */
    protected $parser = null;

    /**
     * Holds an array of plugin objects
     *
     * @var array
     */
    protected $plugins = array();

    /** 
     * User Agent Constants 
     *
     */
    const UA_MOZILLA_MAC        =   'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; en-US; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3';
    const UA_MOZILLA_WINDOWS    =   'Mozilla/5.0 (Windows; U; Windows NT 5.2; en-US; rv:1.9.2) Gecko/20091111 Firefox/3.6';
    const UA_IE_WINDOWS         =   'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0)';

    /**
     * Sets the HttpClient and default user agents and delayStrategy
     *
     * @param mixed Guzzle\Http\Client or bool false
     */
    public function __construct($httpClient = false)
    {
        if ($httpClient instanceof HttpClient) {
            $this->httpClient = $httpClient;
        } else {
            $this->httpClient = new HttpClient;
            $this->addClientSubscriber(new CookiePlugin(new ArrayCookieJar));
        }

        $this->setUserAgent(self::UA_MOZILLA_MAC);
        $this->setDelayStrategy(new NoDelay);
    }

    /**
     * Adds a plugin
     *
     * @param object $plugin Mechanize\Plugins\PluginInterface
     *
     * @return Mechanize\Client;
     */
    public function addPlugin(PluginInterface $plugin)
    {
        $this->plugins[$plugin->getAlias()] = $plugin;

        return $this;
    }

    /**
     * Returns a plugin object or false if the plugin does not exist
     *
     * @param string $alias The plugin alias
     *
     * @return mixed The plugin object or bool false
     */
    public function getPlugin($alias)
    {
        if (array_key_exists($alias, $this->plugins)) {
            return $this->plugins[$alias];
        }

        return false;
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
     * Change the user agent with either a string or one of the class constants
     *
     * @param string $agent
     * @return object Mechanize\Client
     */
    public function setUserAgent($agent)
    {
        $this->httpClient->setUserAgent($agent, false);

        return $this;
    }

    /**
     * Sets the delay strategy to use
     *
     * @param object Mechanize/Delay/DelayInterface
     *
     * @return object Mechanize/Client
     */
    public function setDelayStrategy(DelayInterface $delay)
    {
        $this->delayStrategy = $delay;

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
     * Returns the uri of the current request
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
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
     * Returns the page's contents (with any DOM modifications) similar to View > Page Source.
     *
     * @return string
     */
    public function getContents()
    {
        return $this->parser->getContents();
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
     * Convenience method to return the absolute url of a url
     *
     * @param string $url The Url to make absolute
     *
     * @return string The absolute url
     */
    public function getAbsoluteUrl($url)
    {
        return $this->parser->getAbsoluteUrl($url);
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
     * Set custom headers
     *
     * @return object Mechanize/Client;
     */
    public function setHeaders(array $headers = array())
    {
        $this->headers = $headers;

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

        if (!is_null($this->uri) && !isset($headers['Referer'])) {
            // Set the Referer header on subsequent requests
            $this->addHeaders(array(
                'Referer'  => $this->uri
            ));
        }

        // Reset the request elements
        $this->uri = $uri;
        $this->body = null;
        $this->dom = null;
        $this->domxpath = null;

        $headers = array_merge($this->headers, $headers);

        try {
            $this->response = $this->httpClient->get($this->uri, $headers)->send();
        } catch (BadResponseException $e) {
            $this->response = $e->getResponse();
        }

        // Reset headers so they are empty for future requests
        $this->setHeaders(array());

        $this->dom = new \DOMDocument;
        @$this->dom->loadHtml($this->getBody());
        $this->domxpath = new \DOMXPath($this->dom);

        $this->parser = new Parser($this->response, $this->dom, $this->domxpath);
        $this->parser->setUri($this->uri);

        return $this->response;
    }

    /**
     * Convenience method. Find any element on the page using an xpath selector
     *
     * @return Mechanize/Elements
     **/
    public function find($selector = false, $limit = -1, $context = false)
    {
        return $this->parser->find($selector, $limit, $context);
    }

    /**
     * Convenience method. Find any element on the page using an xpath selector but only return the first result
     *
     * @return Mechanize/Elements
     **/
    public function findOne($selector = false, $context = false)
    {
        return $this->parser->find($selector, 1, $context);
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
     * Follow a link on the page by specifying an xpath selector
     *
     * @param string
     *
     * @return Mechanize/Client
     **/
    public function follow($selector = false)
    {
        $element = $this->getElements($selector, 1);

        return $this->get($this->parser->absoluteUrl($element->href));
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
     * Retrieves a file and returns an stdClass with filename, contentType, and contents keys
     * @todo move this into a plugin, but keep in mind plugins cannot currently make get() requests because
     *      they don't hold an instance of Mechanize\Client
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

    /**
     * Magic method to return the contents of the current page on echo() or print() calls
     *
     * @return string The contents of the page
     */
    public function __toString()
    {
        return $this->getContents();
    }
}