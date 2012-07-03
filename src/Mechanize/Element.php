<?php

namespace Mechanize;

use Mechanize\Client;
use Mechanize\Exception;

class Element
{
    /**
     * Holds a reference to the DOMElement object
     *
     * @param DOMElement
     **/
    protected $element;
    
    /**
     * Holds a reference to Mechanize object
     *
     * @param Mechanize/Client
     **/
    protected $client;

    /**
     * Object construct takes the DOMElement object and saves it
     *
     * @param DOMElement
     **/
    public function __construct(\DOMElement $element, Client $client)
    {
        $this->element = $element;
        $this->client = $client;
    }
    
    /**
     * Overloading to pass method calls to the DOMElement when we don't have a function here for it
     *
     **/
    public function __call($method, $args)
    {
        if (method_exists($this, $method)) {
            return call_user_func_array(array($this, $method), $args);
        }
        
        if (method_exists($this->element, $method)) {
            return call_user_func_array(array($this->element, $method), $args);
        }
    }
    
    /**
     * Overloading to have a setter for DOMElement
     *
     **/
    public function __set($name, $value)
    {
        $this->element->{$name} = $value;
    }
    
    /**
     * Overloading to have a getter for DOMElement
     *
     **/
    public function __get($name)
    {
        if (isset($this->element->{$name})) {
            return $this->element->{$name};
        }
        
        return false;
    }
    
    /**
     * Get the DOMElement
     *
     * @return DOMElement
     **/
    public function getElement()
    {
        return $this->element;
    }
    
    /** 
     * Find within the context of this element
     *
     * @param string|Compass_Xpath
     * @param mixed int limit or bool false for no limit
     * @return Compass_Mechanize_Elements
     **/
    public function find($selector = false, $limit = -1)
    {
        return $this->client->find($selector, $limit, $this->getElement());
    }
    
    /**
     * Returns the text for the node and applies an optional filter
     * @todo remove support for Zend
     *
     * @param bool false|array|Zend_Filter_Interface. An array of Zend_Filter(s) will create a filter chain, or you can pass the filter chain directly
     * @return string
     **/
    public function getText($filterChain = false)
    {
        return $this->filter($this->element->textContent, $filterChain);
    }
    
    /**
     * Returns the HTML for the node and applies an optional filter
     * @todo remove support for Zend
     *
     * @param bool false|array|Zend_Filter_Interface. An array of Zend_Filter(s) will create a filter chain, or you can pass the filter chain directly
     * @return string
     */
    public function getHtml($filterChain = false)
    {
        $dom = new DOMDocument;
        $dom->appendChild($dom->importNode($this->getElement(), true));
        return $this->filter($dom->saveHTML(), $filterChain);
    }
    
    /**
     * Extract a portion of text from the node by applying a regex
     *
     * @param string the regex pattern
     * @param int the array index to return on the matches
     * @return string|bool false. String on successful match; bool false on failure.
     **/
    public function extractText($pattern, $index = 0) 
    {
        if (false !== preg_match($pattern, $this->element->textContent, $match)) {
            return $match[$index];
        }
        
        return false;
    }
    
    /**
     * Retrieve an attribute from the element and apply on optional filter
     *
     * @param string the attribute name
     * @param bool false|array|Zend_Filter_Interface. An array of Zend_Filter(s) will create a filter chain, or you can pass the filter chain directly
     * @return string
     **/
    public function getAttribute($attr, $filterChain = false)
    {
        if ('' !== $attribute = $this->element->getAttribute($attr)) {
            return $this->filter($attribute, $filterChain);
        }
        
        return '';
    }
    
    /**
     * Internal function to provide filtering
     * Note: To apply a php function like htmlentities, use Zend_Filter_Callback('htmlentities');
     * @todo remove support for Zend
     * 
     * @param string the string to filter
     * @param bool false|array|Zend_Filter_Interface. An array of Zend_Filter(s) will create a filter chain, or you can pass the filter chain directly
     * @return string
     * @throws Compass_Mechanize_Exception
     **/
    protected function filter($string, $filterChain = false)
    {
        if (false === $filterChain) {
            return $string;
        }
        
        if (is_array($filterChain)) {
            $chain = new Zend_Filter;
            foreach ($filterChain as $f) {
                if (!$f instanceof Zend_Filter_Interface) {
                    throw new Exception('Filter is not a valid Zend_Filter');
                }
                $chain->addFilter($f);
            }
            $filterChain = $chain;
            unset($chain);
        }
        
        if ($filterChain instanceof Zend_Filter_Interface) {
            return $filterChain->filter($string);
        }
        
        return $string;
    }
}