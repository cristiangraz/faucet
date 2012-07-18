<?php

namespace Faucet\Dom\Xpath;

class Expression
{
    /**
     * Holds the base selector
     *
     * @var string
     */
    protected $baseSelector = '';
    
    /**
     * Holds all of the selectors that need to be combined
     *
     * @var array
     */
    protected $selectors = array();
    
    /**
     * Holds the final (combined) xpath expression
     *
     * @param string
     */
    protected $expression = '';
    
    /**
     * Whether or not the expression is literal
     *
     * @var bool
     */
    protected $isLiteral = false;
    
    /**
     * Object construct. Currently does nothing
     *
     */
    public function __construct() {}
    
    /**
     * Combines the xpath expressions into one
     *
     * @return Faucet\Xpath
     */
    public function build()
    {
        if (false === $this->isLiteral) {
            $selectors = '';
            
            if (false === empty($this->selectors)) {
                foreach ($this->selectors as $s) {
                    $selectors .= $s . ' and ';
                }
        
                $selectors = '[' . rtrim($selectors, ' and ') . ']';
            }
        
            $this->expression .= $this->baseSelector . $selectors;
        }

        $this->baseSelector = '';
        $this->selectors = array();
        $this->isLiteral = false;

        // Apply convenience functions
        if (false !== strpos($this->expression, 'lower-case')) {
            $this->expression = preg_replace('#lower-case\(([^\)]+)\)#', 'translate(\\1, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")', $this->expression);
        }

        if (false !== strpos($this->expression, 'upper-case')) {
            $this->expression = preg_replace('#upper-case\(([^\)]+)\)#', 'translate(\\1, "abcdefghijklmnopqrstuvwxyz", "ABCDEFGHIJKLMNOPQRSTUVWXYZ")', $this->expression);
        }
        
        return $this->expression;
    }
    
    /**
     * Set a literal xpath expression
     *
     * @param string $literal
     *
     * @return Faucet\Xpath
     */
    public function literal($literal)
    {
        $this->expression .= $literal;
        $this->isLiteral = true;
        
        return $this;
    }
    
    /**
     * Set a base xpath expression
     *
     * @param string $base
     *
     * @return Faucet\Xpath
     */
    public function setBase($base)
    {
        $this->baseSelector = $base;
        
        return $this;
    }
    
    /**
     * Add a hasAttribute() check to your xpath
     *
     * @param string $attr The attribute to check against
     * @param bool Whether or not to negate the expression
     *
     * @return Faucet\Xpath
     */
    public function hasAttribute($attr, $negate = false)
    {
        $this->selectors[] = $this->negate('@' . $attr, $negate);
        
        return $this;
    }
    
    /**
     * Verify an attribute equals a particular value
     *
     * @param string $attr The attribute to check against
     * @param string $value The value to check against
     * @param bool Whether or not to negate the expression
     *
     * @return Faucet\Xpath\Expression
     */
    public function attributeEquals($attr, $value, $negate = false)
    {
        if ($attr == 'class') {
            $this->hasClass($value, $negate);
        } else {
            $this->selectors[] = $this->negate('@' . $attr . '=\'' . $value . '\'', $negate);
        }
        
        return $this;
    }

    /**
     * Xpath to find a node containing a class
     * This lowercases the class for standardization
     * 
     * @param  string $className The name of the class
     * @param  bool Whether or not to negate the expression
     * 
     * @return Faucet\Xpath\Expression
     */
    public function hasClass($className, $negate = false)
    {
        $this->selectors[] = $this->negate('contains(concat(" ", normalize-space(lower-case(@class)), " "), " ' . strtolower($className) . ' ")', $negate);

        return $this;
    }
    
    /**
     * See if an attribute begins with a particular character
     *
     * @param string $attr The attribute to check against
     * @param string $value The value to check against
     * @param bool Whether or not to negate the expression
     *
     * @return Faucet\Xpath
     */
    public function attributeStartsWith($attr, $value, $negate = false)
    {
        $this->selectors[] = $this->negate('starts-with(@' . $attr . ', \'' . $value . '\')', $negate);
        
        return $this;
    }
    
    /**
     * See if an attribute ends with a particular character
     * @todo add negate support
     *
     * @param string $attr The attribute to check against
     * @param string $value The value to check against
     * @param bool Whether or not to negate the expression
     *
     * @return Faucet\Xpath
     */
    public function attributeEndsWith($attr, $value, $negate = false)
    {
        $this->selectors[] = '\'' . $value . '\' = substring(@' . $attr . ', string-length(@' . $attr . ') - string-length(\'' . $value . '\') +1)';
        
        return $this;
    }
    
    /**
     * See if the attribute contains a value
     *
     * @param string $attr The attribute to check against
     * @param string $value The value to check against
     * @param bool Whether or not to negate the expression
     *
     * @return Faucet\Xpath
     */
    public function attributeContains($attr, $value, $negate = false)
    {
        $this->selectors[] = $this->negate('contains(@' . $attr . ', \'' . $value . '\')', $negate);
        
        return $this;
    }
    
    /**
     * See if the node's text equals a particular value
     *
     * @param string $text The text value to check
     *
     * @return Faucet\Xpath
     */
    public function textEquals($text)
    {
        $this->selectors[] = 'text() = \'' . $text . '\'';
        
        return $this;
    }
    
    /**
     * See if the node's text beings with a particular value
     *
     * @param string $text The text value to check
     *
     * @return Faucet\Xpath
     */
    public function textStartsWith($text)
    {
        $this->selectors[] = 'starts-with(text(), \'' . $text . '\')';
        
        return $this;
    }
    
    /**
     * See if the node's text ends with a particular value
     *
     * @param string $text The text value to check
     *
     * @return Faucet\Xpath
     */
    public function textEndsWith($text)
    {
        $this->selectors[] = '\'' . $text . '\' = substring(text(), string-length(text()) - string-length(\'' . $text . '\') +1)';
        
        return $this;
    }
    
    /**
     * See if the node's text contains a particular value
     *
     * @param string $text The text value to check
     *
     * @return Faucet\Xpath
     */
    public function textContains($text)
    {
        $this->selectors[] = 'contains(text(), \'' . $text . '\')';
        
        return $this;
    }

    /**
     * Magic method creating the xpath expression
     *
     * @return string
     */
    public function __toString()
    {   
        $this->build();
        
        return $this->expression;
    }
    
    /**
     * Negate any xpath expression
     *
     * @param string $string The xpath expression
     * @param bool $negate Whether or not to negate the expression
     *
     * @return string
     */
    protected function negate($string, $negate = false)
    {
        $start = $negate === true ? 'not(' : '';
        $end = $negate === true ? ')' : '';
        
        return $start . $string . $end;
    }
}