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

class MongoLanternBenchmark {

	/**
	 * Start Benchmarking Process
	 */
	public function Start($identifier = false) {
		if (false === $identifier) {
			exit('Invalid Identifier');
		}
		$dataset[$identifier]['start']['time'] = microtime(false);
		$dataset[$identifier]['start']['memory'] = memory_get_usage(true);
		$this -> BenchmarkData = $dataset;
		return true;
	}

	/**
	 * End Benchmarking Process
	 */
	public function End($identifier = false) {
		if (false === $identifier) {
			exit('Invalid Identifier');
		}
		$dataset = $this -> BenchmarkData;
		$dataset[$identifier]['end']['time'] = microtime(false);
		$dataset[$identifier]['end']['memory'] = memory_get_usage(true);
		$this -> BenchmarkData = $dataset;
		return true;
	}

	/**
	 * Get Benchmarking Result
	 */
	public function Report($identifier = false) {
		if (false === $identifier) {
			exit('Invalid Identifier');
		}
		return array('identifier' => $identifier, 'executionTime' => ((double)$this -> BenchmarkData[$identifier]['end']['time'] - (double)$this -> BenchmarkData[$identifier]['start']['time']), 'memoryUsage' => ($this -> BenchmarkData[$identifier]['end']['memory'] - $this -> BenchmarkData[$identifier]['start']['memory']) / 1024, );
	}

	/**
	 * Class Instance Destructor
	 */
	public function __destruct() {
		foreach ($this as $key => $value) {
			unset($this -> $key);
		}
	}

}