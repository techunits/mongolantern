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

class MongoLanternStats extends MongoLanternBase {
	public static function setKeyword($keyword) {
		global $MongoLanternGLOBAL;
		if (false !== $keyword) {
			$keyword_info = $MongoLanternGLOBAL -> MongoDBH -> lanternKeywordStats -> findOne(array('keyword' => $keyword, ));
			if (!empty($keyword_info['_id'])) {
				$keyword_info['rank'] = $keyword_info['rank'] + 1;
				$keyword_info['modified'] = (int) time();
			} else {
				$keyword_info['keyword'] = $keyword;
				$keyword_info['rank'] = 1;
				$keyword_info['created'] = (int) time();
				$keyword_info['modified'] = (int) time();
			}
			$MongoLanternGLOBAL -> MongoDBH -> lanternKeywordStats -> save($keyword_info);
		}

		//  optimize keyword index
		$MongoLanternGLOBAL -> MongoDBH -> lanternKeywordStats -> ensureIndex(array('keyword' => 1, ));

		return true;
	}

}
