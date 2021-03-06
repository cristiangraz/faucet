<?php

namespace Faucet\Dom;

use Faucet\Client;
use Faucet\Dom\Parser;
use Faucet\Exception;

use Zend\Filter\FilterChain;
use Zend\Filter\FilterInterface;

use Symfony\Component\CssSelector\CssSelector;

class Element
{
    /**
     * Holds a reference to the DOMElement object
     *
     * @param DOMElement
     **/
    protected $element;
    
    /**
     * Holds a reference to Faucet parser
     *
     * @param Faucet/Dom/Parser
     **/
    protected $parser;

    /**
     * Object construct takes the DOMElement object and saves it
     *
     * @param DOMElement
     **/
    public function __construct(\DOMElement $element, Parser $parser)
    {
        $this->element = $element;
        $this->parser = $parser;
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

        if ($this->element->hasAttribute($name)) {
            return $this->element->getAttribute($name);
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
     * Returns the tag of the dom node
      *
     * @return string The tag
     */
    public function getTag()
    {
        return $this->element->nodeName;
    }

    /**
     * Returns the parser object
     *
     * @return object Faucet\Dom\Parser
     */
    public function getParser()
    {
        return $this->parser;
    }
    
    /** 
     * Find within the context of this element
     *
     * @param string $selector The xpath selector
     * @param mixed int limit or bool false for no limit
     *
     * @return Faucet\Elements
     **/
    public function find($selector, $limit = -1)
    {
        return $this->parser->find($selector, $limit, $this->element);
    }

    /**
     * Find one within the context of this element
     *
     * @param string $selector The xpath selector
     *
     * @return Faucet\Elements
     */
    public function findOne($selector)
    {
        return $this->parser->findOne($selector, $this->element);
    }

    /**
     * Find any element within the context of this element using a css selector selector
     *
     * @return Faucet/Elements
     **/
    public function select($selector, $limit = -1)
    {
        return $this->find(CssSelector::toXPath($selector), $limit);
    }

    /**
     * Convenience method to find any element within the context of this element using a css selector but only return the first result
     *
     * @return Faucet/Elements
     **/
    public function selectOne($selector)
    {
        return $this->findOne(CssSelector::toXPath($selector));
    }

    /**
     * Checks to see if the node has text
     * 
     * @return bool
     */
    public function hasText()
    {
        $text = trim($this->element->textContent);

        return !empty($text);
    }
    /**
     * Returns the text for the node and applies an optional filter
     *
     * @param mixed $filterChain Either bool false or an array of Zend\Filter objects
     *
     * @return string
     **/
    public function getText($filterChain = false)
    {
        return $this->filter(trim($this->element->textContent), $filterChain);
    }
    
    /**
     * Returns the HTML for the node and applies an optional filter
     *
     * @param mixed $filterChain Either bool false or an array of Zend\Filter objects
     * @return string
     */
    public function getHtml($filterChain = false)
    {
        $dom = new \DOMDocument;
        $dom->appendChild($dom->importNode($this->getElement(), true));

        return $this->filter($dom->saveHTML(), $filterChain);
    }
    
    /**
     * Extract a portion of text from the node by applying a regex
     *
     * @param string $pattern The regex pattern
     * @param int $index The array index to return on the matches
     *
     * @return mixed String on successful match; bool false on failure.
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
     * @param mixed $filterChain Either bool false or an array of Zend\Filter objects
     *
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
     * 
     * @param string the string to filter
     * @param mixed $filterChain Either bool false or an array of Zend\Filter objects
     *
     * @return string
     * @throws Faucet\Exception
     **/
    protected function filter($string, $filterChain = false)
    {
        $string = trim($string);

        if (false === $filterChain) {
            return $string;
        }
        
        if (is_array($filterChain)) {
            $chain = new FilterChain;
            foreach ($filterChain as $filter) {
                if (!$filter instanceof FilterInterface) {
                    throw new Exception('Filter is not a valid Zend Filter');
                }
                $chain->attach($filter);
            }
            $filterChain = $chain;
            unset($chain);
        }
        
        if ($filterChain instanceof FilterInterface) {
            return $filterChain->filter($string);
        }
        
        return $string;
    }
}