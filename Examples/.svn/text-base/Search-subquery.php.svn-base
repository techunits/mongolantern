<?php

require_once('../MongoLantern/MongoLantern.php');

$keyword = 'su techunits.com';
$limit = 10;
$skip = 0;

$search = new MongoLanternQuery();
$search->indexName = 'People';
$search->Connect();

//  Create MongoLanternQueryParserObject
$parser = new MongoLanternQueryParser();
$parser->setQueryTerm($keyword);
$parser->setSubqueryTerm('email', 'techunits.com', true, false);
$parser->setRange('height', 5.0, 6.0);


//  Optional (if range is specified)
//  Set parser query object for search
$search->setQuery($parser);

//  Optional
//  There is 3 types of Query Mode: BESTMATCH, SUGGESTED, ANY
$search->setMatchMode('BESTMATCH');

//  Optional
//  There is 2 types of Query Mode: CREATED, DOCUMENT
$search->setSortMode('RANK');

//  Optional
//  Paginate the result
$search->setLimit($limit)->setSkip($skip);

//  Optional
//  Resolve parital query typo as mentioned in release notes.
//  Note: Use of this kind of query is discouraged for larger databases. This is still in development.
$search->setIntelligentQueryMode(true);

//  Set Debug mode on/off
//  $search->debug = true;

//  Try search result with limit
$resultList = $search->Execute($limit);

print_r($resultList);
print_r($search->getStats());

//  Clear instance data
$search->Clear();

exit();

?>
