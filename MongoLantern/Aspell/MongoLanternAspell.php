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

class MongoLanternAspell {
  
  public static function rectifyQuery($query) {
    global $MongoLanternGLOBAL;
    $spellchecked = '';
    
    $pspell_link = self::connectDictionary();   //  connect to dictionary
    $wordList = preg_split ("/\s+/", $query);
    foreach($wordList as $word) {
      if (pspell_check($pspell_link, $word)) {
        $spellchecked .= $word." ";
      }
      else {
        $suggestions = pspell_suggest($pspell_link, $word);
        $spellchecked .= $suggestions[0]." ";
      }
    }
    
    return $spellchecked;
  }
  
  //  add the word to custom dictionary
  public static function addtoCustomDictionary($word) {
    if(true === self::isValidWord($word)) {      
      $pspell_link = self::connectDictionary();   //  connect to dictionary
      pspell_add_to_personal($pspell_link, $word);
      pspell_save_wordlist($pspell_link);
      
      return true;
    }
    
    return false;
  }
  
  //  connect to custom dictionary path
  private static function connectDictionary() {
    global $MongoLanternGLOBAL;
    
    //  create dictionary path
    $dicPath = MONGOLANTERN_BASEPATH.'Dictionary'.DIRECTORY_SEPARATOR;
    @mkdir($dicPath, 0755, true);
    
    $pspell_config = pspell_config_create("en");
    pspell_config_personal($pspell_config, $dicPath.$MongoLanternGLOBAL->dictionaryName.'.pws');
    $pspell_link = pspell_new_config($pspell_config);
    
    return $pspell_link;
  }
  
  
  //  remove any unsupported character from word
  private static function isValidWord($word) {
    //  word length must be greater than 3
    //  it must not contain any numeric digit
    //  it must not contain any special characters including space
    if(strlen($word) > 3 && 0 == preg_match('/[0-9]/', $word) && 0 == preg_match('/[\'\/~`\!@#\$%\^&\*\(\)_\-\+=\{\}\[\]\|;:"\<\>,\.\?\\\\ ]/', $word)) {      
      return true;
    }
    
    return false;
  }
}
