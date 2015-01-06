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

class MongoLanternField extends MongoLanternBase implements MongoLanternFieldInterface {


  /**
    * Set TEXT data to store to index
    * Note: KEYWORD content must not be tokenized BUT whole word should be used as tokens. Also keep the whole content into index for field level filter.
    * @param string $field
    * @param string $value
   */ 
  public static function Keyword($field, $value) {    
    return (object) array(
      'tokenize'    =>  false,      
      'isToken'     =>  true,
      'stem'        =>  false,
      'keepContent' =>  true,
      'type'        =>  'KEYWORD',
      'field'       =>  $field,
      'value'       =>  $value,
    );
  }
  
  
  /**
    * Set text data to store to index
    * Note: TEXT content must be tokenized OR used as tokenized data as tokens. Also keep the whole content into index for field level filter.
    * @param string $field
    * @param string $value
   */
  public static function Text($field, $value) {
    return (object) array(
      'tokenize'    =>  true,
      'isToken'     =>  true,
      'stem'        =>  true,
      'keepContent' =>  true,
      'type'        =>  'TEXT',
      'field'       =>  $field,
      'value'       =>  $value,
    );
  }
  
  public static function UnStored($field, $value) {
    return (object) array(
      'tokenize'    =>  true,      
      'isToken'     =>  true,
      'stem'        =>  true,
      'keepContent' =>  false,
      'type'        =>  'UNSTORED',
      'field'       =>  $field,
      'value'       =>  $value,
    );
  }
  
  /**
    * Set text data to store to index
    * Note: UnIndexed content must not be tokenized OR used as tokens. Just keep the content into index. This can be used for sorting or subquery.
    * @param string $field
    * @param string $value
   */
  public static function UnIndexed($field, $value) {
    return (object) array(
      'tokenize'    =>  false,      
      'isToken'     =>  false,
      'stem'        =>  false,
      'keepContent' =>  true,
      'type'        =>  'UNINDEXED',
      'field'       =>  $field,
      'value'       =>  $value,
    );
  }
  
  
  /**
    * Set binary data to store to index
    * Note: BINARY content must not be tokenized OR used as tokens. Just keep the content into index.
    * @param string $field
    * @param string $value
   */
  public static function Binary($field, $value) {
    die('Lantern Error: Not implemented.');
    return (object) array(
      'tokenize'    =>  false,
      'isToken'     =>  false,
      'stem'        =>  false,
      'keepContent' =>  true,
      'type'        =>  'BINARY',
      'field'       =>  $field,
      'value'       =>  $value,
    );
  }
}
