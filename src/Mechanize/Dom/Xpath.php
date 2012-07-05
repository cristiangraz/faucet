<?php

namespace Mechanize\Dom;

class Xpath
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
    protected $combined = '';
    
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
     * @return Mechanize\Xpath
     */
    public function combine()
    {
        if (false === $this->isLiteral) {
            $selectors = '';
            
            if (false === empty($this->selectors)) {
                foreach ($this->selectors as $s) {
                    $selectors .= $s . ' and ';
                }
        
                $selectors = '[' . rtrim($selectors, ' and ') . ']';
            }
        
            $this->combined .= $this->baseSelector . $selectors;
        }

        $this->baseSelector = '';
        $this->selectors = array();
        $this->isLiteral = false;
        
        return $this;
    }
    
    /**
     * Set a literal xpath expression
     *
     * @param string $literal
     *
     * @return Mechanize\Xpath
     */
    public function literal($literal)
    {
        $this->combined .= $literal;
        $this->isLiteral = true;
        
        return $this;
    }
    
    /**
     * Set a base xpath expression
     *
     * @param string $base
     *
     * @return Mechanize\Xpath
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
     * @return Mechanize\Xpath
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
     * @return Mechanize\Xpath
     */
    public function attributeEquals($attr, $value, $negate = false)
    {
        if ($attr == 'class') {
            $this->selectors[] = $this->negate('contains(@class, "' . $value . '")', $negate);
        } else {
            $this->selectors[] = $this->negate('@' . $attr . '=\'' . $value . '\'', $negate);
        }
        
        return $this;
    }
    
    /**
     * See if an attribute begins with a particular character
     *
     * @param string $attr The attribute to check against
     * @param string $value The value to check against
     * @param bool Whether or not to negate the expression
     *
     * @return Mechanize\Xpath
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
     * @return Mechanize\Xpath
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
     * @return Mechanize\Xpath
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
     * @return Mechanize\Xpath
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
     * @return Mechanize\Xpath
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
     * @return Mechanize\Xpath
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
     * @return Mechanize\Xpath
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
        $this->combine();
        
        return $this->combined;
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