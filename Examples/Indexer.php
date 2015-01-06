<?php

require_once('../MongoLantern/MongoLantern.php');

MongoLanternUtility::setErrorReporting(E_ALL | E_STRICT);

$dataset = array(
  1 =>  array(
    'firstname' =>  'Sougata',
    'lastname'  =>  'Pal',
    'email'     =>  'skall.paul@techunits.com',
    'age'       =>  24,
    'height'    =>  5.67,    
    'joined_on' =>  'April 30, 1988',
  ),
  2 =>  array(
    'firstname' =>  'Techunits',
    'lastname'  =>  'Firms',
    'email'     =>  'contact@techunits.com',
    'age'       =>  3,
    'height'    =>  3.5,
    'joined_on' =>  'February 27, 2009',
  ),
  3 =>  array(
    'firstname' =>  'MongoDB',
    'lastname'  =>  'Lantern',
    'email'     =>  'mongolantern@googlegroups.com',
    'age'       =>  1,
    'height'    =>  2.1,
    'joined_on' =>  'December 27, 2011',
  ),
  4 =>  array(
    'firstname' =>  'Joseph Joseph Arena Dish Drainer - White',
    'lastname'  =>  'Arena Dish lastname',
    'email'     =>  'dish@googlegroups.com',
    'age'       =>  3,
    'height'    =>  3.0,    
    'joined_on' =>  'August 27, 2010',
  ),
  5 =>  array(
    'firstname' =>  'Anny',
    'lastname'  =>  'Dawn',
    'email'     =>  'annydawn@techunits.com',
    'age'       =>  23,
    'height'    =>  5.1,    
    'joined_on' =>  '12 August, 1989',
  ),
  6 =>  array(
    'firstname' =>  'Subrata',
    'lastname'  =>  'Bhadury',
    'email'     =>  'subrata@techunits.com',
    'age'       =>  25,
    'height'    =>  5.6,
    'joined_on' =>  '16 July, 1985',
  ),
  7 =>  array(
    'firstname' =>  'Subhajit',
    'lastname'  =>  'Mjee',
    'email'     =>  'subhajit@techunits.com',
    'age'       =>  25,
    'height'    =>  6.1,
    'joined_on' =>  '23 September, 1985',
  ),
  8 =>  array(
    'firstname' =>  'Jarno',
    'lastname'  =>  'Väyrynen',
    'email'     =>  'jarno@techunits.com',
    'age'       =>  27,
    'height'    =>  6.5,
    'joined_on' =>  '15 October, 1983',
  ),
);

$indexer = new MongoLanternIndexer();

//  Set Index name on MongoDB
$indexer->indexName = 'People';

//  Enable or disable dictonary: true / false.  default: true;
$indexer->dictionaryEnabled(false);

//  Connect to MongoInstance
$indexer->Connect();

//  Set fields to be searched later. On this field list the index will be optimized */
$indexer->setFields(array(
  'firstname',
  'lastname',
  'age',
  'email',
  'height',
));

foreach($dataset as $index  =>  $doc) {
  print "Index Document: $index \n";  
  
  //  Create MongoLantern advanced document object
  $docObj = new MongoLanternDocument();
  
  $docObj->setField(MongoLanternField::Keyword('email', $doc['email'])) 
         ->setField(MongoLanternField::Text('firstname', $doc['firstname']))
         ->setField(MongoLanternField::UnStored('lastname', $doc['lastname']))                 
         ->setField(MongoLanternField::UnIndexed('age', $doc['age']))
         ->setField(MongoLanternField::UnIndexed('height', $doc['height']))
         ->setField(MongoLanternField::UnIndexed('joined_on', $doc['joined_on']));
  
  //  $index must have to be unique and constant for any document, it wil require to update the document later.
  $indexer->setDocument($index, $docObj);
  
  /*		Commit Document to Index	*/
  $indexer->Commit();
}


//	Optimize Index
print "Optimizing Index...\n";
$indexer->Optimize();

print "\nTotal Docs: ".$indexer->totalDocs()."\n\n";

$docID = 5;
print "Checking for document: ".$docID."\n";
if(true === $indexer->validateDocumentID($docID)) {
  print  "DocID ".$docID." exists\n";
}
else {
 print "DocID ".$docID." doesn't exist\n";
}

exit();
