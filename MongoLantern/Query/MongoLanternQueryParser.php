<?php

/*
******************************************************************************************   

  Package            : MongoLantern
  Version            : 1.0
      
  Lead Architect     : Sougata Pal. [ skall.paul@techunits.com ]     
  Year               : 2011 - 2012

  Site               : http://www.techunits.com
  Contact / Support  : mongolantern@googlegroups.com

  Copyright (C) 2009 - 2012 by TECHUNITS

  Permission is hereby granted, free of charge, to any person obtaining a copy
  of this software and associated documentation files (the "Software"), to deal
  in the Software without restriction, including without limitation the rights
  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
  copies of the Software, and to permit persons to whom the Software is
  furnished to do so, subject to the following conditions:

  The above copyright notice and this permission notice shall be included in
  all copies or substantial portions of the Software.

  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
  THE SOFTWARE.
  
******************************************************************************************   
*/


class MongoLanternQueryParser extends MongoLanternBase implements MongoLanternQueryParserInterface {

  /**
   * 	MongoLanternQuery class constructor
   * 	It determines the MongoExtension availability and few other checks if required 
   * 
   */
  public function __construct($keyword = false) {
    global $mongoLanternVar;
    
    $this->availableIndexes = $mongoLanternVar['availableIndexes'];
    
    if(false !== $keyword) {
    //  $this->Parse($keyword);
    }
  }

  
  public function Parse($keyword) {
    $matches = array();
    $pattern = '/\+\b|\-\b|\~\b/i';
    $replace=array(
       '*',  // value for 'and'
       '+',  // value for 'or'
       '1',  // value for 'foo'
       '0',  // value for 'bar'
       '!'
    ); // value for 'not'

    print preg_match_all($pattern, "php -php.net", $matches);
    print_r( $matches);
    $this->setQueryTerm($keyword);
    exit();
  }
  
  
  /**
   * Set Keyword to it's required form to the class instance
   * @param string $keyword
   */
  public function setQueryTerm($keyword) {
  
    //  rectify query term using aspell library
    $keyword = MongoLanternAspell::rectifyQuery($keyword);

    $this->queryObj = $this->_tokenize($keyword);
    $this->queryObj['keyword'] = strtolower($keyword);
    
    return $this;
  }
  
  /**
   * Set Keyword to it's required form to the class instance
   * @param string $field
   * @param string $value
   * @param boolean $enableExactMatch
   */
  public function setSubQueryTerm($field, $value, $enableExactMatch = true, $mustExists = true) {
    global $MongoLanternGLOBAL;
    
    if(empty($this->queryObj['keyword'])) {
      die('Lantern Error: Subquery can\'t be used without MongoLanternQuery::setQuery()');
    }

    //  check for valid index availability
    if(false === MongoLanternValidate::isFieldIndexed($MongoLanternGLOBAL->availableIndexes, 'document.'.$field)) {
      die('Lantern Error: Specified field "'.$field.'" is not optimized for search. Please add it to field list and run optimizer.');
    }
    
    //  normalize the string
    $value = $this->_normalizeString($value);
    if(true === $enableExactMatch) {
      $subQuery = array(
        'document.'.$field  =>  $value,
      );
    }
    else {
      $subQuery = array(
        'document.'.$field  =>  new MongoRegex('/'.preg_quote($value).'/')
      );
    }
    
    //  Check Positive / Negative Match pattern. Whether the subquery term must exists or must not exists.
    if(true === $mustExists) {
      $this->queryObj['subquery'][] = $subQuery;
    }
    else {
      $this->queryObj['subquery'][] = array(
        'document.'.$field =>  array(
          '$ne' =>  $value,
        ),
      );
    }
    
    return $this;
  }
  
  
  /**
   * Set Keyword to it's required form to the class instance
   * @param string $keyword
   */
  public function setRange($field, $start, $end) {
    global $MongoLanternGLOBAL;
    
    //  check for valid index availability
    if(false === MongoLanternValidate::isFieldIndexed($MongoLanternGLOBAL->availableIndexes, 'document.'.$field)) {
      die('Lantern Error: Specified field "'.$field.'" is not optimized for search. Please add it to field list and run optimizer.');
    }
  
    if(true === MongoLanternValidate::isInteger($start) && true === MongoLanternValidate::isInteger($end)) {      
      $this->queryObj['range'] = array(
        'field' =>  $field,
        'start' =>  (int) $start,
        'end'   =>  (int) $end
      );
    }
    else if(true === MongoLanternValidate::isFloat($start) && true === MongoLanternValidate::isFloat($end)) {
      $this->queryObj['range'] = array(
        'field' =>  $field,
        'start' =>  (float) $start,
        'end'   =>  (float) $end
      );
    }
    else if(false !== ($start = strtotime($start)) && false !== ($end = strtotime($end))) {
      $this->queryObj['range'] = array(
        'field' =>  $field,
        'start' =>  (int) $start,
        'end'   =>  (int) $end
      );
    }
    else {
      die('Lantern Error: Unhandled range parameters.');
    }
    
    return $this;
  }
  
}
