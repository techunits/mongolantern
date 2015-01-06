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

class MongoLanternSpell {
  public function __construct($lang = 'en') {
    if (false === extension_loaded('pspell')) {
      die('Lantern Error: php-pspell module is not loaded. Please check install guided.');
    }
    
    $this->pspellLink = pspell_new($lang);
  }
  
  public function checkSpell($word) {
    if (false === pspell_check($this->pspellLink, $word)) {
      return false;
    }
    
    return true;   
  }
  
  public function getSuggestions($word, $bestmatch = true) {
    global $MongoLanternGLOBAL;
    if(!empty($word)) {
      if(false === $this->checkSpell($word)) {
        $suggestionList = pspell_suggest($this->pspellLink, $word);
        
        //  apply custom algorithm for bestmatch algorithm
        if(true === $bestmatch) {
          $finalSuggetionList = array();
          foreach($suggestionList as $suggestion) {
            if(false === $this->_isSpecialCharFound($suggestion)) {
              $finalSuggetionList[] = array(
                'word'      =>  $suggestion,
                'distance'  =>  levenshtein($word, $suggestion),
              );
            }
          }
          print_r($finalSuggetionList);
          exit();
        }
        
        //  if bestmatch not enabled use output pspell
        else {
          return $suggestionList;
        }
      }
      else {
        return false;
      }
    }    
    return false;
  }
  
  private function _isSpecialCharFound($word) {
    $tokenSymbols = array(' ', '-', ',', '&', ':', '#', ';', '.', '*', '%', '(', ')', '\'');
    foreach($tokenSymbols as $symbol) {
      if(false !== stristr($word, $symbol)) {
        return true;
      }
    }
    
    return false;
  }
}
