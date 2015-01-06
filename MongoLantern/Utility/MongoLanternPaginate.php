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

class MongoLanternPaginate {
	public function Generate($array, $perPage = 10, $page = 1, $skip = true) {
		// Assign the items per page/skip variable
		if (!empty($perPage))
			$this -> perPage = $perPage;

		// Assign the page/skip variable
		$this -> page = $page;

		if (false === $skip) {
			// Take the length of the array
			$this -> length = count($array);

			// Get the number of pages
			$this -> pages = ceil($this -> length / $this -> perPage);

			// Calculate the starting point
			$this -> start = ceil(($this -> page - 1) * $this -> perPage);
			// Return the part of the array we have requested
			return array_slice($array, $this -> start, $this -> perPage);
		} else {
			// Return the part of the array we have requested
			return array_slice($array, $page, $this -> perPage);
		}
	}

}