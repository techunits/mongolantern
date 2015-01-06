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
class MongoLanternUtility {
  /**
	 * Set the error reporting to a desired life. If left empty, will default too
	 * E_ALL | E_STRICT
	 * 
	 * @param string $level The level to set the error reporting at
	 * 
	 * @return void
	 * @access public
	 */
	public static function setErrorReporting($level = false) {
		if(false === $level)
			$level = E_ALL | E_STRICT;
		
		error_reporting($level);
	}
	
	/*  parse csv file into array  */
	public static function parseCSV($file, $comma = ',', $quote = '"', $newline = "\n") {
		global $var;

		$db_quote = $quote . $quote;

		// Clean up file
		$file = trim($file);
		$file = str_replace("\r\n", $newline, $file);

		$file = str_replace($db_quote, '&quot;', $file);
		// replace double quotes with &quot; HTML entities
		$file = str_replace(',&quot;,', ',,', $file);
		// handle ,"", empty cells correctly

		$file .= $comma;
		// Put a comma on the end, so we parse last cell

		$inquotes = false;
		$start_point = 0;
		$row = 0;

		for ($i = 0; $i < strlen($file); $i++) {
			$char = $file[$i];
			if ($char == $quote) {
				if ($inquotes) {
					$inquotes = false;
				} else {
					$inquotes = true;
				}
			}

			if (($char == $comma or $char == $newline) and !$inquotes) {
				$cell = substr($file, $start_point, $i - $start_point);
				$cell = str_replace($quote, '', $cell);
				// Remove delimiter quotes
				$cell = str_replace('&quot;', $quote, $cell);
				// Add in data quotes
				$data[$row][] = $cell;
				$start_point = $i + 1;
				if ($char == $newline) {
					$row++;
				}
			}
		}

		return $data;
	}

	public static function processArrayMapIndex($dataArray) {
		$countIndex = 0;
		$dataList = array();
		$fields = $dataArray[0];
		foreach ($dataArray as $row) {
			foreach ($row as $index => $fieldData) {
				if (!empty($fields[$index]) && $fieldData != $fields[$index]) {
					$fieldName = strtolower($fields[$index]);
					$dataList[$countIndex][$fieldName] = $fieldData;
				}
			}
			$countIndex++;
		}

		return $dataList;
	}

	/* to sort an array with key value */
	public static function arraySort2D($array, $on, $order = SORT_ASC) {
		$new_array = array();
		$sortable_array = array();

		if (count($array) > 0) {
			foreach ($array as $k => $v) {
				if (is_array($v)) {
					foreach ($v as $k2 => $v2) {
						if ($k2 == $on) {
							$sortable_array[$k] = $v2;
						}
					}
				} else {
					$sortable_array[$k] = $v;
				}
			}

			switch ($order) {
				case SORT_ASC :
					asort($sortable_array);
					break;

				case SORT_DESC :
					arsort($sortable_array);
					break;
			}

			foreach ($sortable_array as $k => $v) {
				$new_array[$k] = $array[$k];
			}
		}

		return array_values($new_array);
	}

	public static function importLatestDictionary() {
		global $MongoLanternGLOBAL;

		if (false === $MongoLanternGLOBAL -> dictionaryEnabled) {
			print "Dictionary: Disabled\n";
			return false;
		}

		$path = MONGOLANTERN_BASEPATH . 'Resources' . DIRECTORY_SEPARATOR;
		$fileList = glob($path . "*.dic");

		//  If dictionary files are missing don't let users continue to further indexing. It seems to be an corrupted download
		if (count($fileList) == 0) {
			die("Lantern Error: Dictionary files missing. You must have downloaded the corrupted files. Please download again and continue. \n");
		}

		foreach ($fileList as $filename) {
			print "#";
			$fileData = file($filename);
			foreach ($fileData as $row) {
				$wordInfoJSON = json_decode($row);

				//  Check for word existance
				$wordInfo = $MongoLanternGLOBAL -> MongoDBH -> lanternDictionary -> findOne(array('word' => $wordInfoJSON -> word, ));
				$asciisum = 0;
				$word = $wordInfoJSON -> word;
				for($i=0; $i < strlen($wordInfoJSON -> word); $i++) {	
				  $asciisum += ord($word[$i]);
				}
				if (!empty($wordInfo['_id'])) {
					$wordInfo['version'] = (float) MONGOLANTERN_VERSION;
					$wordInfo['asciisum'] = (int) $asciisum;
					$wordInfo['updated'] = (int) time();
				} else {
					$wordInfo['word'] = $wordInfoJSON -> word;
					$wordInfo['metaphone'] = $wordInfoJSON -> metaphone;
					$wordInfo['soundex'] = $wordInfoJSON -> soundex;
					$wordInfo['version'] = (float)MONGOLANTERN_VERSION;
					$wordInfo['asciisum'] = (int) $asciisum;
					$wordInfo['created'] = (int) time();
					$wordInfo['updated'] = (int) time();
				}
				$MongoLanternGLOBAL -> MongoDBH -> lanternDictionary -> save($wordInfo);
			}
		}

		//  Create Index on Version
		$MongoLanternGLOBAL -> MongoDBH -> lanternDictionary -> ensureIndex(array('version' => 1, ));

		//  Create Index on Word
		$MongoLanternGLOBAL -> MongoDBH -> lanternDictionary -> ensureIndex(array('word' => 1, ));

		return true;
	}

	/**
	 * Returns the class name without the namespaced addition. Should be used in environments that
	 * namespace their classes.
	 *
	 * @param string $class. The string should already be the name of the classed retrieved using get_class($object)
	 *
	 * @return string $class_name Returns the name of the class without a namepsace
	 * @access public
	 */
	public static function getClassName($class) {

		if (is_object($class))
			$class = get_class($class);

		$class = explode('\\', $class);

		return end($class);
	}

}