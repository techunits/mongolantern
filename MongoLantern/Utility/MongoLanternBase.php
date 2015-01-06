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

class MongoLanternBase {
	public $debug = false;
	public $host = 'localhost';
	public $port = 27017;
	public $indexName = '';
	public $tokenSymbols = ' -,&:#;.*%()\'';

	protected $defaultCharacterEncoding = 'utf-8';
	protected $collectionPrefix = 'lanternRawIndex';
	protected $collection = '';
	protected $finalqueryObj = array();
	protected $lanternVersion = MONGOLANTERN_VERSION;
	protected $benchmarkStats;
	protected $queryStats;
	protected $resultsCount = 0;
	protected $indexKeys = array('documentID', 'document.tokenChunks', 'created');
	protected $availableIndexes = array();

	private $database = 'MongoLantern';
	private $requiredPHPExtensions = array('mongo', 'mbstring', 'pspell');

	/**
	 * Connect to MongoDB or use existing MongoConnection Handler
	 * @param connection handler $mongoDBH
	 */
	public function Connect($mongoDBH = false) {
		global $MongoLanternGLOBAL;

		//  Check whether Index name specified
		if (empty($this -> indexName)) {
			die('Lantern Error: Index name must be specified.');
		}
		
		$this -> collection = $this -> collectionPrefix . '_' . $this -> indexName;
		$MongoLanternGLOBAL->dictionaryName = $this -> collection;  //  custom dictionary name

		if (false !== $mongoDBH) {
			$this -> MongoDBH = $mongoDBH;
		} else {
			/*	Initialize	MongoDB Connection	*/
			try {
				$MongoDB_Conn = new MongoClient($this -> host . ':' . $this -> port);
				$db_name = $this -> database;
				$this -> MongoDBH = $MongoDB_Conn -> $db_name;
			} catch (MongoConnectionException $e) {
				die('Lantern Error: ' . $e -> getMessage());
			} catch (MongoException $e) {
				die('Lantern Error: ' . $e -> getMessage());
			}
		}

		//  assign global mongodbh
		$MongoLanternGLOBAL -> MongoDBH = $this -> MongoDBH;

		//  get list of indexes available
		$this -> availableIndexes = $this -> getIndexes($this -> collectionPrefix . '_' . $this -> indexName);
		$MongoLanternGLOBAL -> availableIndexes = $this -> availableIndexes;

		//  check whether the connect is called from query class. It will be required to check the obvious indexes
		if ('MongoLanternQuery' == get_class($this)) {
			//  Check for compatible pre-built index
			$this -> isCompatibleIndex();

			//  check for obvious indexes
			foreach ($this->indexKeys as $field) {
				//  check for valid index availability
				if (false === MongoLanternValidate::isFieldIndexed($this -> availableIndexes, $field)) {
					die('Lantern Error: Obvious index field "' . $field . '" not found.');
				}
			}
		} else if ('MongoLanternIndexer' == get_class($this) && true === $MongoLanternGLOBAL -> dictionaryEnabled) {
			//  Check for valid Dictionary
			if (false === MongoLanternValidate::isValidDictionary()) {
				print('Invalid Lantern Dictionary version. Required version: ' . MONGOLANTERN_VERSION . "\nUpdating Dictionary...\n");
				MongoLanternUtility::importLatestDictionary();
			}
		}

		return $this -> MongoDBH;
	}

	public static function setMongoQueryIndex($collection_list) {
		foreach ($collection_list as $collection => $keyList) {
			foreach ($keyList as $key => $index) {
				if (is_array($index)) {
					foreach ($index as $i) {
						print "Index: " . $collection . '::' . $key . ' =>  ' . $i . "\n";
						$MongoDBObject -> $collection -> ensureIndex(array($key => $i, ));
					}
				} else {
					print "Index: " . $collection . '::' . $index . "\n";
					$MongoDBObject -> $collection -> ensureIndex(array($index => 1, ));
				}
			}
		}
	}

	/**
	 * Set Default encoding for system
	 * @encoding default: utf-8
	 */
	public function setDefaultEncoding($encoding = 'utf-8') {
		setlocale(LC_CTYPE, $encoding);
		$this -> defaultCharacterEncoding = strtolower($encoding);

		return $this;
	}

	/**
	 * Enable or Disable dictionary support
	 * @enable default: false
	 */
	public function dictionaryEnabled($enable = false) {
		global $MongoLanternGLOBAL;

		if (true === $enable) {
			$MongoLanternGLOBAL -> dictionaryEnabled = true;
		} else {
			$MongoLanternGLOBAL -> dictionaryEnabled = false;
		}

		return $this;
	}
	
	/**
	 * Enable or Disable stem support
	 * @enable default: false
	 */
	public function stemEnabled($enable = false) {
		global $MongoLanternGLOBAL;

		if (true === $enable) {
			$MongoLanternGLOBAL -> stemEnabled = true;
		} else {
			$MongoLanternGLOBAL -> stemEnabled = false;
		}

		return $this;
	}

	/**
	 *  Check for php extension required.
	 *  On missing of extension die with fatal error
	 */
	protected function _checkRequiredExtensions() {
		foreach ($this->requiredPHPExtensions as $extension) {
			if (!extension_loaded($extension)) {
				die("Lantern Error:  \"" . $extension . "\" PHP Extension not installed.\n");
			}
		}

		return true;
	}

	/**
	 * StemString
	 * @str
	 */
	protected function _stemString($string) {
	  return $string;
		return MongoLanternStem::stemString($string);
	}

  /**
	  * make valid tokens out of a string removing invalid characters
	  * @string
	 */
	protected function _tokenify($string, $omitSymbol = array(), $acceptMixedNumeric = true) {
    $removableSymbols = array(',', '#', '.', ';', '*', '%', '(', ')', '\'');
    $removableSymbols = array_diff($removableSymbols, $omitSymbol);
    foreach($removableSymbols as $symbol) {
      $string = str_replace($symbol, '', $string);
    }
    
    //  enable or disable use of mixed numeric varriable
    if(false === $acceptMixedNumeric && 1 == preg_match('/-?[0-9]+/i', (string)$string)) {
      return false;
    }
    
    return (strlen($string) >= MONGOLANTERN_MIN_STRING_LENGETH)?$string:false;
  }

	/**
	 * Tokenize the string as required w.r.t specified tokenSymbols
	 * @str
	 */
	protected function _tokenize($str, $token_reset = true) {
		//  normalize string to get perfected results/index
		$str = $this -> _normalizeString($str);
    $chunks = array();
    
		$word = strtok($str, $this -> tokenSymbols);
		if ('' == $word) {
			return array();
		}
		if(false !== ($word = $this->_tokenify($this -> _stemString($word), array('#')))) {
		  $chunks[] = (string) $word;
		}
		while (false !== $word) {
			$word = strtok($this -> tokenSymbols);

			if (!empty($word)) {
				//  apply stemming on the word
				$word = $this -> _stemString($word);

				//  if token string length is greater than 2 consider it as a valid token
				if (false !== ($word = $this->_tokenify($word, array('#')))) {
					$chunks[] = (string) $word;
				}
			}
		}
		if ($token_reset) {
			strtok('', '');
		}

		return $chunks;
	}

	/**
	 * Set Default encoding for system
	 * @string default: utf-8
	 */
	protected function _normalizeString($content) {
		//  step 1: trim string & decode urlencoded characters if any
		$content = trim(urldecode($content));

		if (true === MongoLanternValidate::isInteger($content)) {
			return (int)$content;
		} else if (true === MongoLanternValidate::isFloat($content)) {
			return (float)$content;
		} else {
			//  step 2: change encoding to default OR user-specified encoding
			$content = preg_replace('/[^(\x20-\x7F)]*/', '', $content);

			//  step 3: change encoding to default OR user-specified encoding
			$oldEncoding = strtolower(mb_detect_encoding($content));
			if ($oldEncoding != $this -> defaultCharacterEncoding) {
				$content = mb_convert_encoding($content, $this -> defaultCharacterEncoding, $oldEncoding);
			}

			//  step 4: change string to lowercase
			$content = strtolower($content);

			return $content;
		}
	}

	/**
	 * Get Indexes
	 * @param: $collection
	 */
	private function getIndexes($collection) {
		$indexInfo = $this -> MongoDBH -> selectCollection($collection) -> getIndexInfo();
		$indexList = array();
		foreach ($indexInfo as $index) {
			$indexList = array_merge($indexList, $index['key']);
		}

		return array_keys($indexList);
	}

	private function isCompatibleIndex() {
		$currentVersionInfo = $this -> MongoDBH -> lanternRawIndex_settings -> findOne(array(
		  'field' => 'version', 
	  ));

		if (!empty($currentVersionInfo['_id']) && $currentVersionInfo['value'] == $this -> lanternVersion) {
			return true;
		} else {
			die('Lantern Error: Lantern index is not compatible with current software version. Please upgrade via running indexer once.');
		}
	}

}