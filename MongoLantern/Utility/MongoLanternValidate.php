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

class MongoLanternValidate {

	public static function isFloat($content) {
		$validation = false;
		if (is_numeric($content) === TRUE) {
			if ((double)$content == $content) {
				$validation = true;
			}
		}
		return $validation;
	}

	public static function isInteger($content) {
		if (true === is_numeric($content) && (int)$content == $content) {
			return true;
		}

		return false;
	}

	/**
	 * Set Keyword to it's required form to the class instance
	 * @param array $indexList
	 * @param string $field
	 */
	public static function isFieldIndexed($indexList, $field) {
		if (true === in_array($field, $indexList)) {
			return true;
		}

		return false;
	}

	public static function isValidDictionary() {
		global $MongoLanternGLOBAL;

		$totalWords = $MongoLanternGLOBAL -> MongoDBH -> lanternDictionary -> count();
		$totalWordsVersion = $MongoLanternGLOBAL -> MongoDBH -> lanternDictionary -> find(array(
		  'version'   => (float) MONGOLANTERN_VERSION, 
		)) -> count();
		if ($totalWords > 0 && $totalWords == $totalWordsVersion) {
			return true;
		}

		return false;
	}

}