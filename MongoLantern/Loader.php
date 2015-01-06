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

global $MongoLanternGLOBAL;

/*
//  Load varriable class
require_once(MONGOLANTERN_BASEPATH.'Utility'.DIRECTORY_SEPARATOR.'Vars.php');

//  Load base class
require_once(MONGOLANTERN_BASEPATH.'Utility'.DIRECTORY_SEPARATOR.'Base.php');

//  Load all available Interfaces
require_once(MONGOLANTERN_BASEPATH.'Interfaces'.DIRECTORY_SEPARATOR.'Interfaces.php');

//  Load All Utilities
require_once(MONGOLANTERN_BASEPATH.'Utility'.DIRECTORY_SEPARATOR.'Utility.php');
require_once(MONGOLANTERN_BASEPATH.'Utility'.DIRECTORY_SEPARATOR.'Validate.php');
require_once(MONGOLANTERN_BASEPATH.'Utility'.DIRECTORY_SEPARATOR.'Paginate.php');
require_once(MONGOLANTERN_BASEPATH.'Utility'.DIRECTORY_SEPARATOR.'Stem.php');
require_once(MONGOLANTERN_BASEPATH.'Utility'.DIRECTORY_SEPARATOR.'Stats.php');

//  Load interface resources
require_once(MONGOLANTERN_BASEPATH.'Response'.DIRECTORY_SEPARATOR.'Response.php');
require_once(MONGOLANTERN_BASEPATH.'Benchmark'.DIRECTORY_SEPARATOR.'Benchmark.php');
require_once(MONGOLANTERN_BASEPATH.'Document'.DIRECTORY_SEPARATOR.'Document.php');
require_once(MONGOLANTERN_BASEPATH.'Field'.DIRECTORY_SEPARATOR.'Field.php');
require_once(MONGOLANTERN_BASEPATH.'Indexer'.DIRECTORY_SEPARATOR.'Indexer.php');
require_once(MONGOLANTERN_BASEPATH.'Query'.DIRECTORY_SEPARATOR.'Query.php');
require_once(MONGOLANTERN_BASEPATH.'Query'.DIRECTORY_SEPARATOR.'QueryParser.php');
*/

//  Load 3rd party Plugins
require_once(MONGOLANTERN_BASEPATH.'ThirdParty'.DIRECTORY_SEPARATOR.'CSV'.DIRECTORY_SEPARATOR.'MongoLanternIndexCSVInterface.php');
require_once(MONGOLANTERN_BASEPATH.'ThirdParty'.DIRECTORY_SEPARATOR.'CSV'.DIRECTORY_SEPARATOR.'MongoLanternIndexCSV.php');

/*
require_once(MONGOLANTERN_BASEPATH.'ThirdParty'.DIRECTORY_SEPARATOR.'MySQL'.DIRECTORY_SEPARATOR.'MongoLanternIndexMySQLInterface.php');
require_once(MONGOLANTERN_BASEPATH.'ThirdParty'.DIRECTORY_SEPARATOR.'MySQL'.DIRECTORY_SEPARATOR.'MongoLanternIndexMySQL.php');
*/

/**
  * Custom Autoload functions
 */ 
function __autoload($class_name) {  
  $baseDir = str_replace('MongoLantern', "", $class_name);
  
  $path = MONGOLANTERN_BASEPATH.$baseDir.DIRECTORY_SEPARATOR.$class_name.'.php';
  if(true === is_file($path)) {
    require_once $path;
  }
  else if(false !== stristr($class_name, 'interface')) {
    $path = MONGOLANTERN_BASEPATH.'Interfaces'.DIRECTORY_SEPARATOR.$class_name.'.php';
    require_once $path;    
  }
  else {
    $path = MONGOLANTERN_BASEPATH.'Utility'.DIRECTORY_SEPARATOR.$class_name.'.php';
    require_once $path;
  }  
}


$MongoLanternGLOBAL = new MongoLanternVariableHandler();
require_once(MONGOLANTERN_BASEPATH.'Utility'.DIRECTORY_SEPARATOR.'MongoLanternDefaults.php');
