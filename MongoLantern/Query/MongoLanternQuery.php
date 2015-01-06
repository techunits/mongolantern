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
 
//  Include query parser engine
require_once('MongoLanternQueryParser.php');

class MongoLanternQuery extends MongoLanternBase implements MongoLanternQueryInterface {

	/**
	 * 	MongoLanternQuery class constructor
	 * 	It determines the MongoExtension availability and few other checks if required
	 *
	 */
	public function __construct() {
		/*  Check for required PHP extensions */
		$this -> _checkRequiredExtensions();

		$this -> benchmarkStats = new MongoLanternBenchmark();

		/*  Setup Default Parameters  */
		$this -> matchMode = 'BESTMATCH';
		//  Search Result Match Mode

		$this -> sortMode = 'CREATED';
		//  Search Result Sorting Mode

		$this -> fieldqueryObj = false;
		//  Set FieldQuery Object

		$this -> intelligentQueryMode = false;
		//  Set Intelligent Query Flag (Enable/Disable)

		$this -> _skip = 0;
		//  Set default skip param for pagination

		$this -> _limit = 25;
		//  Set default limit param for pagination
	}

	/**
	 * Set Keyword to it's required form to the class instance
	 * @param string $keyword
	 */
	public function setQuery($queryObj) {
		//  use MongoLanternQueryParser Object as input to setQuery
		if (true === is_object($queryObj) && 'MongoLanternQueryParser' == get_class($queryObj)) {
			$this -> queryObj = $queryObj -> queryObj;
		} else if (false === is_object($queryObj)) {
			$this -> queryObj = $this -> _tokenize($queryObj);
			$this -> queryObj['keyword'] = strtolower($queryObj);
		} else {
			die('Lantern Error: Invalid Keyword/Object supplied.');
		}

		return $this;
	}

	/**
	 * Set Keyword to it's required form to the class instance
	 * @param string $keyword
	 */
	public function setRangeQuery($field, $start, $end) {
		if (true === MongoLanternValidate::isInteger($start) && true === MongoLanternValidate::isInteger($end)) {
			$this -> queryObj['range'] = array('field' => $field, 'start' => (int)$start, 'end' => (int)$end);
		} else if (true === MongoLanternValidate::isFloat($start) && true === MongoLanternValidate::isFloat($end)) {
			$this -> queryObj['range'] = array('field' => $field, 'start' => (float)$start, 'end' => (float)$end);
		} else if (false !== ($start = strtotime($start)) && false !== ($end = strtotime($end))) {
			$this -> queryObj['range'] = array('field' => $field, 'start' => (int)$start, 'end' => (int)$end);
		} else {
			die('Lantern Error: Unhandled range parameters.');
		}

		return $this;
	}

	/**
	 * Set subquery w.r.t fields
	 * @param string $queryParam
	 */
	public function setFieldQuery($queryParam) {
		//  TODO:
		//  We must rethink the algorithm for FieldQuery Set
		/*	Field Query must not work alone	*/
		if (empty($this -> queryObj['keyword'])) {
			die("Lantern Error:  MongoLanternQuery::setFieldQuery() can't work without valid MongoLanternQuery::setQuery().\n");
		}

		foreach ($queryParam as $field => $value) {
			$this -> fieldqueryObj['document.' . $field] = $value;
		}

		return $this;
	}

	/**
	 * Setup Search mode for the query
	 * Default: ANY
	 * Search modes as follows:
	 * 	ANY:	 which indicates it will search for all possible fields for the keyword tokens
	 * 	BESTMATCH:	 Search for whole keyword in query fields specified
	 *	SUGGESTED:	 Search for whole keyword in tokens
	 * @param string $mode
	 */
	public function setMatchMode($mode = 'BESTMATCH') {
		if ('ANY' == $mode) {
			$this -> matchMode = 'ANY';
		} else if ('BESTMATCH' == $mode) {
			$this -> matchMode = 'BESTMATCH';
		} else if ('SUGGESTED' == $mode) {
			$this -> matchMode = 'SUGGESTED';
		} else {
			die("Lantern Error:  Invalid Match Mode.\n");
		}

		return $this;
	}

	/**
	 * Setup Sort mode for the query
	 * Default: CREATED
	 * Search modes as follows:
	 * 	CREATED:	 Results are sorted in order they are added to LanternIndex
	 * 	DOCUMENT:	 Results are sorted in order of the documentID
	 * 	ALPHABET:	 Results are sorted in order of the documentID
	 *  @param string $mode
	 */
	public function setSortMode($mode = 'CREATED') {
		if ('CREATED' == $mode) {
			$this -> sortMode = 'CREATED';
		} else if ('DOCUMENT' == $mode) {
			$this -> sortMode = 'DOCUMENT';
		} else if ('RANK' == $mode) {
			$this -> sortMode = 'RANK';
		} else {
			die("Lantern Error:  Invalid Sorting Mode.\n");
		}

		return $this;
	}

	/**
	 *  Set Intelligent Query Flkag. Required to optimize search results. If enabled PERFORMANCE is deacreased. Use of the true FLAG is strongly discouraged on large database.
	 *  Default: false
	 *  @param boolean $flag
	 */
	public function setIntelligentQueryMode($enable = false) {
		//  Note: Use of MongoLanternQuery::setQueryFields() & MongoLanternQuery::setIntelligentQueryMode() together is not allowed.
		if (!empty($this -> queryORFields) && count($this -> queryORFields) > 0) {
			die("Lantern Error:  Use of MongoLanternQuery::setQueryFields() & MongoLanternQuery::setIntelligentQueryMode() together is not allowed. \n");
		}
		$this -> intelligentQueryMode = (isset($enable) && true === $enable) ? true : false;

		return $this;
	}

	/**
	 * Set query fields for BESTMATCH mode / ANY mode
	 * If set then the system will try search for bestmatch for the keyword in fields specified
	 * @param array $fields
	 */
	public function setQueryFields($fields) {
		die('Lantern Error: Please don\'t use MongoLanternQuery::setQueryFields() anymore. Due to performance issue this method has been removed. Use MongoLanternQueryParser::setSubQueryTerm() instead.');
	}

	/**
	 * Set Limit for Search Results
	 * Default: 25
	 *  @param int $limit
	 */
	public function setLimit($limit = 25) {
		$this -> _limit = (!empty($limit)) ? intval($limit) : 25;

		return $this;
	}

	/**
	 * Set Skip for Search Results. Required for Pagination.
	 * Default: 0
	 *  @param int $skip
	 */
	public function setSkip($skip) {
		$this -> _skip = (!empty($skip)) ? intval($skip) : 0;

		return $this;
	}

	/**
	 *  Execute final query to Databse Index and retrieve data.
	 *  Default: 2
	 *  @param int $limit
	 *  Note: Assign Limit to Global for Backward compatibility. Will have to be removed after version 1.0
	 */
	public function Execute($limit = 25) {

		//  Assign Limit to Global for Backward compatibility. Will have to be removed after version 1.0.
		if (!empty($limit)) {
			$this -> _limit = intval($limit);
		}

		//  Prepare Query from passed parameters
		$this -> _prepareQueryClause();

		//  Log query for furthee use
		MongoLanternStats::setKeyword($this -> queryObj['keyword']);

		//	Print RAW data if debug enabled
		if (true === $this -> debug) {
			echo "<pre>";
			//	Search Query to MongoDB
			$collection = $this -> collection;
			$this -> queryExplain = $this -> MongoDBH -> $collection -> find($this -> finalqueryObj) -> explain();
			print_r($this);
			exit('!!! end debug !!!');
		}

		//	Search Query to MongoDB
		$this -> benchmarkStats -> Start('query');
		$collection = $this -> collection;

		$documentList = $this -> MongoDBH -> $collection -> find($this -> finalqueryObj) -> limit(100);
		$this -> resultsCount = $documentList -> count();
		$this -> benchmarkStats -> End('query');

		//  Process benchmark data for the query
		$bd = $this -> benchmarkStats -> Report('query');
		$this -> queryStats['executionTime']['query'] = $bd['executionTime'];

		//		Sort Results
		$this -> benchmarkStats -> Start('sort');
		$documentList = $this -> _sortResults($documentList);
		$this -> benchmarkStats -> End('sort');

		//  Process benchmark data for the sorting
		$bd = $this -> benchmarkStats -> Report('sort');
		$this -> queryStats['executionTime']['sort'] = $bd['executionTime'];

		//  Paginate Results
		$documentList = $this -> _paginateResults($documentList);

		return $documentList;
	}

	public function getSuggestion() {
		$chunkList = array();
		foreach ($this->queryObj as $key => $value) {
			if (true === is_numeric($key)) {
				$chunkList[$value] = array('metaphone' => metaphone($value), 'soundex' => soundex($value), );
			}
		}

		$finalwordOptionList = array();
		$tokenCollection = $this -> collectionPrefix . '_tokenChunks';
		foreach ($chunkList as $word => $chunk) {
			$wordOptionList = $this -> MongoDBH -> $tokenCollection -> find(array('metaphone' => $chunk['metaphone'],
			//  'soundex'   =>  $chunk['soundex'],
			)) -> sort(array('occurrence' => -1, ));

			foreach ($wordOptionList as $wordOption) {
				$finalwordOptionList[$word][] = $wordOption['word'];
			}
		}

		$keywordList = array();
		$originalKeyword = $this -> queryObj['keyword'];
		$index = 1;
		foreach ($finalwordOptionList as $key => $value) {
			if (1 == count($value)) {
				if (false !== stripos($this -> queryObj['keyword'], $key)) {
					$originalKeyword = str_ireplace($key, $value[0], $originalKeyword);
					if (count($finalwordOptionList) == $index) {
						$keywordList[] = $originalKeyword;
					}
				}
			} else {
				foreach ($value as $v) {
					if (false !== stripos($this -> queryObj['keyword'], $key)) {
						$originalKeywordtmp = str_ireplace($key, $v, $originalKeyword);
						$keywordList[] = $originalKeywordtmp;
					}
				}
			}
			$index++;
		}

		foreach ($keywordList as $keyword) {
			$finalKeywordList[] = array('keyword' => $keyword, );
		}

		return $finalKeywordList;
	}

	public function getStats() {
		$this -> queryStats['executionTime']['total'] = $this -> queryStats['executionTime']['query'] + $this -> queryStats['executionTime']['sort'];
		$this -> queryStats['resultsCount'] = $this -> resultsCount;

		return $this -> queryStats;
	}

	/**
	 * Clear Class Instance data
	 */
	public function Clear() {
		$this -> __destruct();
	}

	/**
	 * Class Instance Destructor
	 */
	public function __destruct() {
		foreach ($this as $key => $value) {
			unset($this -> $key);
		}
	}

	/**
	 * Build Query Where Clasuse Array from user defined paramters
	 */
	private function _prepareQueryClause() {
		//  No query
		if (empty($this -> queryObj)) {
			die('Lantern Error: No Keyword or Range specified.' . PHP_EOL);
		}

		if (!empty($this -> queryObj['keyword'])) {
			//	Prepare Chunks
			$rawChunks = $this -> queryObj;
			if (true === $this -> intelligentQueryMode) {
				//  Enable Intelligent Query Processing
				$chunks = array();
				foreach ($rawChunks as $key => $rc) {
					if (true === is_numeric($key)) {
					  /*
					  $asciisum = 0;
				    for($i=0; $i < strlen($rc); $i++) {	
				      $asciisum += ord($rc[$i]);
				    }

				    $wordListASCIISum = array();
				    //print ceil($asciisum + ($asciisum / strlen($rc)));exit;
				    foreach($this->MongoDBH->lanternDictionary->find(array(
				      'asciisum'  =>  array(                
                '$gt'  =>  (int) ceil($asciisum - ($asciisum / strlen($rc))),
                '$lt'  =>  (int) ceil($asciisum + ($asciisum / strlen($rc))),
              ),
              'word'    =>  new MongoRegex('/^'.substr($rc, 0, 3).'/'),
            )) as $word) {              
              $wordListASCIISum[] = $word['word'].'#'.$word['asciisum'];
				    }

            print_r($wordListASCIISum);exit();
            
					  //  to be remove: Start
					  $tokenCollection = $this -> collectionPrefix . '_tokenChunks';
					  $soundExList = array_values(iterator_to_array($this->MongoDBH->$tokenCollection->find(array(
              'soundex' =>  soundex($rc),
            ))->sort(array(
              'occurrence'  =>  -1,
            ))->limit(5)));
            //  list of rctified words
            $targetWordList = array();
            foreach($soundExList as $soundExToken) {
              $targetWordList[] = $soundExToken['word'];
            }
            unset($targetWordList[0]);
            //  to be remove: end
            
            //  algo:  use word from soundex list of 0th index iff the real word doesn't match other than the first position
            if(false !== ($indexkey = array_search($rc, $targetWordList))) {
						  $chunks[] = $targetWordList[$indexkey];
						}
						else if(!empty($soundExList[0]['word'])) {
              $chunks[] = $soundExList[0]['word'];
						}
						//  It should be removed asap. Highly un-optimized.
						else {
						  $chunks[] = new MongoRegex('/' . preg_quote($rc) . '/');
						}*/
						
						$chunks[] = new MongoRegex('/' . preg_quote($rc) . '/');
					}
				}
			} else {
				foreach ($rawChunks as $key => $rc) {
					if (true === is_numeric($key)) {
						$chunks[] = $rc;
					}
				}
			}

			//	Set field query Object if any
			if (!empty($this -> fieldqueryObj)) {
				$this -> finalqueryObj = array_merge($this -> finalqueryObj, $this -> fieldqueryObj);
			}

			/*	Where Clause for BestMatch	*/
			if ('BESTMATCH' == $this -> matchMode) {
				/*	Search for all token in TokenList(STRICT AND)	*/
				$this -> finalqueryObj = array_merge($this -> finalqueryObj, array('document.tokenChunks' => array('$all' => $chunks, ), ));
			}

			//  TODO: Tokenization Algorithm needs to be updated a lot
			//	Find supplied tokenized keywords into the tokenList in DB (OR)
			if ('SUGGESTED' == $this -> matchMode) {
				$this -> finalqueryObj = array_merge($this -> finalqueryObj, array("\$or" => array( array('document.tokenChunks' => array('$in' => $chunks, ), ), ), ));
			}

			//  TODO: Need to find someway to optimize it. Use of MatchMode ANY is highly discourraged
			//	Where Clause for MatchMode ANY
			if ('ANY' == $this -> matchMode) {
				//	Search for all keyword token in TokenList(STRICT AND + OR)
				$this -> finalqueryObj = array_merge($this -> finalqueryObj, array("\$or" => array( array('document.tokenChunks' => array('$all' => $chunks, ), ), array('document.tokenChunks' => array('$in' => $chunks, ), ), ), ));
			}
		}

		//  Setup sub query
		if (!empty($this -> queryObj['subquery'])) {
			foreach ($this->queryObj['subquery'] as $subquery) {
				$this -> finalqueryObj = array_merge($this -> finalqueryObj, $subquery);
			}
		}

		//  Setup range query
		if (!empty($this -> queryObj['range'])) {
			$this -> finalqueryObj = array_merge($this -> finalqueryObj, array('document.' . $this -> queryObj['range']['field'] => array('$gte' => $this -> queryObj['range']['start'], '$lte' => $this -> queryObj['range']['end'], )));
		}
	}

	/**
	 * Apply Sorting algorithm onto the resultOBJ
	 */
	private function _sortResults($mResultObj) {
		//  if keyword OR (keyword+range) search mode is activated
		if (!empty($this -> queryObj['keyword'])) {
			switch($this->sortMode) {
				case 'CREATED' :
					$mResultObj = $mResultObj -> sort(array('created' => -1));
					$finalList = array_values(iterator_to_array($mResultObj));
					break;

				case 'DOCUMENT' :
					$mResultObj = $mResultObj -> sort(array('documentID' => -1));
					$finalList = array_values(iterator_to_array($mResultObj));
					break;

				case 'RANK' :
					$finalList = array();
					foreach ($mResultObj as $dataset) {
						unset($dataset['document']['tokenChunks']);
						$tmpString = impLode(' ', $dataset['document']);
						$dataset['phraseDistanceRank'] = levenshtein($this -> queryObj['keyword'], $tmpString);
						$finalList[] = $dataset;
					}

					//  Sort Result w.r.t distance from keyword
					$finalList = MongoLanternUtility::arraySort2D($finalList, 'phraseDistanceRank', SORT_ASC);
					break;
			}
			return $finalList;
		}

		//  if only range search mode is activated
		else {
			$finalList = array_values(iterator_to_array($mResultObj));
			return $finalList;
		}
	}

	/**
	 * Apply pagination on the resultOBJ
	 */
	private function _paginateResults($mResultObj) {
		$p = new MongoLanternPaginate();
		return $p -> Generate($mResultObj, $this -> _limit, $this -> _skip);
	}

}