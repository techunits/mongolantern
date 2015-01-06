<?php

require_once('../MongoLantern/MongoLantern.php');

MongoLanternUtility::setErrorReporting(E_ALL | E_STRICT);

$keyword = 'su techunits.com';
$limit = 10;

$search = new MongoLanternQuery();
$search->indexName = 'People';
$search->Connect();

//  Optional (if range is specified)
//  Set keyword for search
$search->setQuery($keyword);

//  Optional
//  There are 3 types of Query Mode: BESTMATCH, SUGGESTED, ANY
$search->setMatchMode('BESTMATCH');

//  Optional
//  There are 3 types of Sorting Mode: RANK, CREATED, DOCUMENT
$search->setSortMode('RANK');

//  Optional
//  Paginate the result
$search->setLimit($limit)->setSkip(0);

//  Optional
//  Resolve parital query typo as mentioned in release notes.
$search->setIntelligentQueryMode(true);

//  Set Debug mode on/off
// $search->debug = true;

//  Try search result with limit
$resultList = $search->Execute($limit);

print_r($resultList);
print_r($search->getStats());

//  Clear instance data
$search->Clear();

exit();

?>