<?php

namespace Mechanize\Dom;

use Mechanize\Dom\Element;

use Zend\Validator\ValidatorChain;
use Zend\Validator\ValidatorInterface;

class Elements implements \Iterator
{
    /**
     * An array of Mechanize\Dom\Element objects
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
     * Adds a Mechanize\Dom\Element to the list of nodes
     *
     * @param Mechanize\Dom\Element
     *
     * @return Mechanize\Dom\Elements
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
     * @return Mechanize\Dom\Elements
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
     *
     * @param string the attribute we are checking. _text is a special attribute that will use the node value
     * @param bool false|array|Zend\Validate. You can pass an array of Validators which will be turned into a chain, or pass in a validate chain directly
     * @return object Mechanize\Dom\Elements. If no validator, the object is returned back to you. 
     *      Otherwise, returns a new Mechanize\Elements object with the nodes that matched as elements.
     **/
    public function validate($attr, $validator = false)
    {
        if (false === $validator) {
            return $this;
        }
    
        $results = new Elements;
        
        if (is_array($validator)) {
            $validatorChain = new ValidatorChain;
            foreach ($validator as $v) {
                if ($v instanceof ValidatorInterface) {
                    $validatorChain->addValidator($v, true);
                }
            }

            foreach ($this->getElements() as $element) {
                if ($attr == '_text') {
                    if ($validatorChain->isValid($element->getText())) {
                        $results->addElement($element);
                    }
                } elseif ($element->hasAttribute($attr) && $validatorChain->isValid($element->getAttribute($attr))) {
                    $results->addElement($element);
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Returns the array of Mechanize\Dom\Element objects
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
     * @return Mechanize\Dom\Element
     **/
    public function getElement($index)
    {
        return $this->elements[$index];
    }

    /**
     * Removes an element from elements array given an index
     *
     * @param int $index
     *
     * @return Mechanize\Dom\Elements
     */
    public function removeElement($index)
    {
        if (isset($this->elements[$index])) {
            unset($this->elements[$index]);
            $this->length -= 1;
        }

        return $this;
    }
    
    /**
     * Randomizes the order of the elements
     *
     * @return Mechanize\Dom\Elements
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
     * @return Mechanize\Dom\Elements
     **/
    public function limit($limit)
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
     * @return Mechanize\Dom\Elements
     **/
    public function unique($attr, $isUrl = false) 
    {
        $vals = array();
        foreach ($this->elements as $num => $element) {
            $attributeValue = $element->getAttribute($attr);
            if ($isUrl) {
                $attributeValue = $element->getParser()->getAbsoluteUrl($attributeValue);
            }

            if (in_array($attributeValue, $vals)) {
                unset($this->elements[$num]);
                $this->length -= 1;
            } else {
                $vals[] = $attributeValue;
            }
        }
        
        return $this;
    }

    /**
     * Needed for Iterator. Reset the array pointer to the first element.
     *
     */
    public function rewind()
    {
        reset($this->elements);
    }

    /**
     * Needed for Iterator. Return the current element in the array.
     *
     */
    public function current()
    {
        return current($this->elements);
    }

    /**
     * Needed for Iterator. Return the array key for the current array element
     *
     */
    public function key()
    {
        return key($this->elements);
    }

    /**
     * Needed for Iterator. Move the array pointer forward.
     *
     */
    public function next()
    {
        return next($this->elements);
    }

    /**
     * Needed for iterator. Checks if the current array position is valid
     *
     */
    public function valid()
    {
        $key = key($this->elements);
        
        return null !== $key && false !== $key;
    }
}