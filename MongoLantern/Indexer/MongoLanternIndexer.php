<?php

/*
******************************************************************************************   

  Package            : MongoLantern
  Version            : 1.0
      
  Lead Architect     : Sougata Pal. [ skall.paul@techunits.com ]     
  Year               : 2011 - 2014

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

class MongoLanternIndexer extends MongoLanternBase implements MongoLanternIndexerInterface {

	public $minTokenlengeth = 3;
	public $maxTokenlengeth = 10;

	public function __construct() {
		global $MongoLanternGLOBAL;

		/*  Check for required PHP extensions */
		$this -> _checkRequiredExtensions();
	}

	public function setFields($fields) {
		foreach ($fields as $field) {
			$this -> documentFields[] = $field;
			$this -> indexKeys[] = 'document.' . $field;
		}
		if (empty($this -> documentFields) || count($this -> documentFields) <= 0) {
			die("Lantern Error: No valid fields set for searching. Please use field names to have best search results.\n");
		}

		return $this;
	}

	public function setDocument($uniqueDocID, $document) {
		if (empty($uniqueDocID)) {
			die("Lantern Error: Unique DocumentID is missing. It helps indexer to update and remove document in future.\n");
		}

		if (empty($this -> documentFields) || count($this -> documentFields) <= 0) {
			die("Lantern Error: No valid fields set for searching. Please use field names to have best search results.\n");
		}

		$documentInfo = array();
		$documentString = '';

		//  If the DOCUMENT is in array format(BACKWARD COMPATIBILITY)
		if (true === is_array($document)) {
			foreach ($document as $field => $value) {
				print "Latern Notice: Document array has been deprecated due to optimization purpose. Use MongoLanternDocument instance instead.\n";
				if (false === is_numeric($field)) {
					if (false === is_array($value)) {
						$documentInfo[$field] = $this -> _normalizeString($value);
						$documentString .= $documentInfo[$field] . '&';
					} else {
						die("Lantern Error: Document value can't contain array\n");
					}
				}
			}
			$documentString = substr($documentString, 0, -1);
			$documentInfo['tokenChunks'] = $this -> _tokenize($documentString);
		}

		//  If the DOCUMENT is instance of MongoLanternDocument class. (v 0.5+)
		else if (true === is_object($document) && 'MongoLanternDocument' == MongoLanternUtility::getClassName(get_class($document))) {

			//  Prepare Token List
			$documentInfo['tokenChunks'] = array();
			foreach ($document->fieldsObj as $field => $valueObj) {
				if (true === $valueObj -> keepContent) {
					$documentInfo[$field] = $this -> _normalizeString($valueObj -> value);
				}

				if (!empty($valueObj -> tokens)) {
					$documentInfo['tokenChunks'] = array_merge($documentInfo['tokenChunks'], $valueObj -> tokens);
				}
			}
			//  Collect Unique string Tokens
			$documentInfo['tokenChunks'] = array_values(array_unique($documentInfo['tokenChunks']));
			//foreach($documentInfo['tokenChunks'] as &$chunk) {
			//  $chunk = (string) $chunk;
			//}
		}

		//  Invalid DOCUMENT Object
		else {
			die('Lantern Error: Document Object must be instance of MongoLanternDocument' . "\n");
		}

		$this -> documentList[$uniqueDocID] = $documentInfo;

		return $this;
	}

	public function Commit() {
		$collection = $this -> collection;

		foreach ($this->documentList as $uniqueDocID => $document) {
			//  Index Document
			$docInfo = $this -> MongoDBH -> $collection -> findOne(array('documentID' => (string)$uniqueDocID, ));
			$docInfo['documentID'] = (string)$uniqueDocID;
			$docInfo['document'] = $document;
			$docInfo['created'] = (int) time();
			$this -> MongoDBH -> $collection -> save($docInfo);

			//  Store Tokens for Spelling Detection
			foreach ($document['tokenChunks'] as $token) {
				$this->setToken($token);
			}
		}

		//  clean up memory
		unset($this -> documentList);

		//  Set settings: Version
		$this -> _setSettings();

		return MongoLanternResponse::SUCCESS;
	}
  
	public function Optimize() {
		$this -> MongoDBH -> lanternDictionary -> ensureIndex(array('word' => 1, 'metaphone' => 1, 'soundex' => 1, ));

		$collection = $this -> collection;
		$this -> MongoDBH -> $collection -> ensureIndex(array('created' => 1, 'document.tokenChunks' => 1, ));
		foreach ($this->indexKeys as $key) {
			$this -> MongoDBH -> $collection -> ensureIndex(array($key => 1, ));
		}

		//  Optimize Token Collection
		$this -> indexKeys = array('word', 'soundex', 'metaphone', 'occurrence');
		$collection = $this -> collectionPrefix . '_tokenChunks';
		foreach ($this->indexKeys as $key) {
			$this -> MongoDBH -> $collection -> ensureIndex(array($key => 1, ));
		}
		return MongoLanternResponse::SUCCESS;
	}

	public function totalDocs() {
		$collection = $this -> collection;
		return $this -> MongoDBH -> $collection -> count();
	}

	public function validateDocumentID($docID) {
		$collection = $this -> collection;
		if (empty($docID)) {
			die("Lantern Error: Invalid DocumentID supplied.\n");
		}
		$docInfo = $this -> MongoDBH -> $collection -> findOne(array('documentID' => (string)$docID, ));
		if (!empty($docInfo['_id'])) {
			return true;
		}

		return false;
	}

	public function dropFieldIndex($field) {
		$collection = $this -> collection;
		$this -> MongoDBH -> selectCollection($collection) -> deleteIndex($field);
		return true;
	}

	/**
	 * Removes the document from the collection based upon on the assigned
	 * id of the document.
	 *
	 * @param string $document_id The id of the documentID to be removed from the collection
	 * @param array $options An array of options that can be used to customize the removel process
	 *
	 * @return mixed $removed Returns either a boolean or an array based upon past options
	 * @access public
	 * @todo Figure out how to remove tokenChunks, if neccesary
	 */
	public function removeDocument($document_id, $options = array()) {
		$collection = $this -> collection;
		$conditions = array('documentID' => $document_id);
		return $this -> MongoDBH -> $collection -> remove($conditions, $options);
	}

	private function _setSettings() {
		$collection = $this -> collectionPrefix . '_settings';
		$settings = array('version' => $this -> lanternVersion, );
		$lanternInfo = $this -> MongoDBH -> $collection -> findOne(array(
		  'field'   =>  'version',
		));
		$lanternInfo['field'] = 'version';
		$lanternInfo['value'] = (float)$this -> lanternVersion;
		$this -> MongoDBH -> $collection -> save($lanternInfo);
	}
	
  /**
	  * save tokens to token collection for future spell detection
	 */
  private function setToken($token = false) {
    //  save token as dictionary word
    MongoLanternAspell::addtoCustomDictionary($token);
    
    //  save token to mongodb for further use
    $tokenCollection = $this -> collectionPrefix . '_tokenChunks';
    if(!empty($token) && false === is_numeric($token)) {
      if(false !== ($token = $this->_tokenify($token, array(), false))) {
        $tokenInfo = $this -> MongoDBH -> $tokenCollection -> findOne(array(
			    'word' => $token,
			  ));
			  $tokenInfo['word'] = $token;
			  $tokenInfo['soundex'] = soundex($token);
			  $tokenInfo['metaphone'] = metaphone($token);
			  $tokenInfo['occurrence'] = (!empty($tokenInfo['occurrence'])) ? (int)($tokenInfo['occurrence'] + 1) : (int)1;
			  $tokenInfo['created'] = (!empty($tokenInfo['created'])) ? (int)$tokenInfo['created'] : (int) time();
			  $this -> MongoDBH -> $tokenCollection -> save($tokenInfo);
        return true;
      }
    }
    return false;
  }

}
