<?php

namespace Mechanize;

use Mechanize\Element;

class Elements implements \Iterator
{
    /**
     * An array of Mechanize\Element objects
     *
     * @var array
     */
    protected $elements = array();
    
    /**
     * The number of nodes in the list
     *
     * @var int
     **/
    public $length = 0;

    /**
     * Object construct. Currently does nothing.
     *
     **/
    public function __construct() {}
    
    /**
     * Adds a Mechanize\Element to the list of nodes
     *
     * @param Mechanize\Element
     *
     * @return Mechanize\Elements
     **/
    public function addElement(Element $element) 
    {
        $this->elements[] = $element;
        $this->length += 1;
        
        return $this;
    }
    
    public function __call($method, $args)
    {
        if (method_exists($this, $method)) {
            return call_user_func_array(array($this, $method), $args);
        }
        
        if ($this->length == 1) {
            return call_user_func_array(array($this->elements[0], $method), $args);
        }
        
        foreach ($this->elements as $element) {
            call_user_func_array(array($element, $method), $args);
        }
    }
    
    public function __set($name, $value)
    {
        foreach ($this->elements as $element) {
            $element->{$name} = $value;
        }
    }
    
    public function __get($name)
    {
        return $this->elements[0]->{$name};
    }
    
    /**
     * Set the text (node value) for all of the nodes
     *
     * @param string
     * @return Mechanize\Elements
     **/
    public function setText($value)
    {
        foreach ($this->elements as $element) {
            $element->nodeValue = $value;
        }
        
        return true;
    }
    
    /**
     * Add criteria to narrow your list of elements.
     * @todo Remove support for Zend
     *
     * @param string the attribute we are checking. _text is a special attribute that will use the node value
     * @param bool false|array|Zend_Validate. You can pass an array of Zend_Validators which will be turned into a chain, or pass in a validate chain directly
     * @return Compass_Mechanize_Elements. If no validator, the object is returned back to you. 
     *      Otherwise, returns a new Compass_Mechanize_Elements object with the nodes that matched as elements.
     **/
    public function addCriteria($attr, $validator = false)
    {
        if (false === $validator) {
            return $this;
        }
    
        $results = new Elements;
        
        if ($validator && is_array($validator)) {
            $chain = new Zend_Validate;
            foreach ($validator as $v) {
                $chain->addValidator($v);
            }
            $validator = $chain;
            unset($chain);
        }
        
        if ($validator && $validator instanceof Zend_Validate_Interface) {
            foreach ($this->getElements() as $k => $element) {
                if ($attr == '_text') {
                    if ($validator->isValid($element->getText())) {
                        $results->addElement($element);
                        continue;
                    }
                } elseif ($element->hasAttribute($attr) && $validator->isValid($element->getAttribute($attr))) {
                    $results->addElement($element);
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Returns the array of Mechanize/Element objects
     *
     * @return array
     **/
    public function getElements()
    {       
        return $this->elements;
    }
    
    /**
     * Returns an element identified by its array key
     *
     * @param string
     * @return Mechanize\element
     **/
    public function getElement($index)
    {
        return $this->elements[$index];
    }
    
    /**
     * Randomizes the order of the elements
     *
     * @return Mechanize\Elements
     **/
    public function randomize()
    {
        shuffle($this->elements);
        
        return $this;
    }
    
    /**
     * Cuts down on the number of elements by some limit
     *
     * @param int the limit
     * @return Mechanize\Elements
     **/
    public function setLimit($limit)
    {
        array_splice($this->elements, 0, count($this->elements) - $limit);
        $this->length = count($this->elements);
        
        return $this;
    }
    
    /**
     * Removes node from the DOM
     * Example: To remove all meta tags:
     * $mech->find('/html/head/meta')->remove()
     *
     * @return void
     */
    public function remove()
    {
        foreach ($this->elements as $element) {
            $element->parentNode->removeChild($element->getElement());
        }
    }
    
    /**
     * Remove any elements with duplicate attributes
     *
     * @param string the attribute
     * @param bool whether or not the attribute contains urls
     * @return Mechanize\Elements
     **/
    public function unique($attr, $isUrl=false) 
    {
        $vals = array();
        foreach ($this->elements as $num => $element) {
            $attributeValue = $element->getAttribute($attr);
            if ($isUrl) {
                $attributeValue = $this->absoluteUrl($attributeValue);
            }
            if (in_array($val, $vals)) {
                unset($this->elements[$num]);
                $this->length -= 1;
            } else {
                $vals[] = $attributeValue;
            }
        }
        
        return $this;
    }

    public function rewind()
    {
        reset($this->elements);
    }

    public function current()
    {
        return current($this->elements);
    }

    public function key()
    {
        return key($this->elements);
    }

    public function next()
    {
        return next($this->elements);
    }

    public function valid()
    {
        $key = key($this->elements);
        
        return null !== $key && false !== $key;
    }
}