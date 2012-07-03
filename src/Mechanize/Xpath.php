<?php

namespace Mechanize;

class Xpath
{
    protected $baseSelector = '';
    
    protected $selectors = array();
    
    protected $combined = '';
    
    protected $isLiteral = false;
    
    public function __construct() {}
    
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
    
    public function literal($literal)
    {
        $this->combined .= $literal;
        $this->isLiteral = true;
        
        return $this;
    }
    
    public function setBase($base)
    {
        $this->baseSelector = $base;
        
        return $this;
    }
    
    public function hasAttribute($attr, $negate = false)
    {
        $this->selectors[] = $this->negate('@' . $attr, $negate);
        
        return $this;
    }
    
    public function attributeEquals($attr, $val, $negate = false)
    {
        if ($attr == 'class') {
            $this->selectors[] = $this->negate('contains(@class, "' . $val . '")', $negate);
        } else {
            $this->selectors[] = $this->negate('@' . $attr . '=\'' . $val . '\'', $negate);
        }
        
        return $this;
    }
    
    public function attributeStartsWith($attr, $val, $negate = false)
    {
        $this->selectors[] = $this->negate('starts-with(@' . $attr . ', \'' . $val . '\')', $negate);
        
        return $this;
    }
    
    // TODO: Add negate
    public function attributeEndsWith($attr, $val, $negate = false)
    {
        $this->selectors[] = '\'' . $val . '\' = substring(@' . $attr . ', string-length(@' . $attr . ') - string-length(\'' . $val . '\') +1)';
        
        return $this;
    }
    
    public function attributeContains($attr, $val, $negate = false)
    {
        $this->selectors[] = $this->negate('contains(@' . $attr . ', \'' . $val . '\')', $negate);
        
        return $this;
    }
    
    public function textEquals($text)
    {
        $this->selectors[] = 'text() = \'' . $text . '\'';
        
        return $this;
    }
    
    public function textStartsWith($text)
    {
        $this->selectors[] = 'starts-with(text(), \'' . $text . '\')';
        
        return $this;
    }
    
    public function textEndsWith($text)
    {
        $this->selectors[] = '\'' . $text . '\' = substring(text(), string-length(text()) - string-length(\'' . $text . '\') +1)';
        
        return $this;
    }
    
    public function textContains($text)
    {
        $this->selectors[] = 'contains(text(), \'' . $text . '\')';
        
        return $this;
    }

    public function __toString()
    {   
        $this->combine();
        
        return $this->combined;
    }
    
    protected function negate($string, $negate = false)
    {
        $start = $negate === true ? 'not(' : '';
        $end = $negate === true ? ')' : '';
        
        return $start . $string . $end;
    }
}