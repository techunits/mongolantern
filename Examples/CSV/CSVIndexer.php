<?php

require_once('../../MongoLantern/MongoLantern.php');

MongoLanternUtility::setErrorReporting(E_ALL | E_STRICT);

$indexer = new MongoLanternIndexCSV('Product');

//  Enable or disable dictonary: true / false.  default: true;
$indexer->dictionaryEnabled(false);

$csvPath = 'CSVData/test.csv';
//  this accepts both valid CSV string & CSV file
$indexer->setCSV($csvPath);

$indexer->verbose = true;

//  set field for documentID. It must be unique, otherwise it will overwrite data in index 
$indexer->setDocumentIDField('sku');

// this fields will be used as mongodb index to make the queries faster and increase search time performance with subqueries & range
$indexer->setFields(array(
  'name',
  'price',
  'stock',
  'supplier'
));

//  fields & type specified here will be used as index parameter to increase result quality
$indexer->setFieldsType(array(
  'sku'       =>  'KEYWORD',
  'name'      =>  'TEXT',
  'price'     =>  'UNINDEXED',
  'stock'     =>  'UNINDEXED',
  'supplier'  =>  'UNINDEXED',
));

$indexer->Commit();
