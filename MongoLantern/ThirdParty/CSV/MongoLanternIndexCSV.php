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

class MongoLanternIndexCSV implements MongoLanternIndexCSVInterface {
  public $verbose = false;
  
	public function __construct($indexName = false, $mongoDBH = false) {
		$this -> indexerObj = new MongoLanternIndexer();
		if (!empty($indexName)) {
			$this -> indexerObj -> indexName = $indexName;
		}
		
    $this->mongoDBH = $mongoDBH;
    
		//  default values
		$this -> documentIDField = false;
	}
	
	public function dictionaryEnabled($enabled = false) {
    $this -> indexerObj -> dictionaryEnabled($enabled);
    return $this;
  }
  
	public function setCSV($fileInfo) {
	  //  Connect to MongoDB index
	  $this -> Connect();
	  
		if (true === is_file($fileInfo)) {
			$dataset = MongoLanternUtility::parseCSV(file_get_contents($fileInfo));
		} else {
			$dataset = MongoLanternUtility::parseCSV($fileInfo);
		}

		$this -> CSVDataset = MongoLanternUtility::processArrayMapIndex($dataset);
		return $this;
	}

	public function setFields($fields) {
		$this -> indexerObj -> setFields($fields);
		$this -> indexFields = $fields;
		return $this;
	}

	public function setDocumentIDField($field = false) {
		$this -> documentIDField = $field;
		return $this;
	}

	public function setFieldsType($fieldsType) {
		$this -> fieldsType = $fieldsType;
		return $this;

	}

	public function Commit() {
		//  check for valid docuemntID field
		if(empty($this -> documentIDField)) {
		  die('Latern CSV Error: The DocumentID field not spcified. Use MongoLanternIndexCSV::setDocumentIDField to specify.'."\n");
		}
		
		if(!empty($this -> CSVDataset)) {
		  $this -> availableFields = array_keys($this -> CSVDataset[1]);
		  if (false === in_array($this -> documentIDField, $this -> availableFields)) {
			  die('Latern CSV Error: The DocumentID field mentioned is not found in CSV'."\n");
		  }
		  else if(count($intersectedFields = array_intersect($this -> availableFields, $this -> indexFields)) != count($this -> indexFields)) {
		    $missingFields = array_diff($this -> indexFields, $intersectedFields);
		    die('Latern CSV Error: All index fields must exists in CSV. Fields missing in CSV: '.implode(', ', $missingFields)."\n");
		  }
		}
		else {
		  die('Latern CSV Error: Invalid CSV data supplied.'."\n");
		}

		//  Create MongoLantern advanced document object
		$docObj = new MongoLanternDocument();

		foreach ($this->CSVDataset as $itemArr) {
			foreach ($itemArr as $field => $value) {
				$type = $this -> fieldsType[$field];
				switch($type) {
					case 'KEYWORD' :
					case 'keyword' :
					case 'Keyword' :
						$docObj -> setField(MongoLanternField::Keyword($field, $value));
				  break;
				  
				  case 'TEXT' :
					case 'text' :
					case 'Text' :
						$docObj -> setField(MongoLanternField::Text($field, $value));
				  break;
				  
				  case 'UNSTORED' :
				  case 'UnStored' :					
					case 'unstored' :
						$docObj -> setField(MongoLanternField::UnStored($field, $value));
				  break;
				  
				  case 'UNINDEXED' :
				  case 'UnIndexed' :					
					case 'unindexed' :
						$docObj -> setField(MongoLanternField::UnIndexed($field, $value));
				  break;
				  
				  default:
				    die('Latern CSV Error: Invalid field type specified.'."\n");
				  break;
				}
			}

      $docIndex = (!empty($itemArr[$this -> documentIDField]))?$itemArr[$this -> documentIDField]:die('Latern CSV Error: DocumentIDField('.$this -> documentIDField.') value can\'t be null.'."\n");
      if(true === $this -> verbose) {
        print "Index Document: ".$docIndex."\n";
      }
      
			//  $index must have to be unique and constant for any document, it wil require to update the document later.
			$this -> indexerObj -> setDocument($docIndex, $docObj);

			/*		Commit Document to Index	*/
			$this -> indexerObj -> Commit();
			
			//  Optimize Index
			$this -> indexerObj -> Optimize();
		}
	}

  private function Connect() {
    $this -> indexerObj -> Connect($this->mongoDBH);
    return $this;
  }
}
