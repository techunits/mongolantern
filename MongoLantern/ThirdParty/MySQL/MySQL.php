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

class TechMVCDatabase {

  public function __construct($username, $password, $host = 'localhost', $port = 3306) {
    global $var;
    try {
      $this->dbh = mysql_pconnect($host , $username, $password);
    }
    catch (Exception $e) {
      print('Error: '.$e->getMessage());
      return false;
    }
  }

  /**
    * Select Another Database
    */
  public function selectDatabase($db = 'test') {
    global $var;
    try {
      mysql_select_db($db, $this->dbh) or die('ERROR : '.mysql_error());
    }
    catch(Exception $e) {
      print('Error: '.$e->getMessage());
      return false;
    }
  }

  /**
    * SQL Insert
   */
  public function Insert($param) {
    global $var;
    
    if(!isset($param['#table'])) {
      exit('Invalid Table Name.  [ #table ]');
    }
    if(!isset($param['#data'])) {
      exit('Invalid Dataset Name.   [ #data ]');
    }
    
    $table = $param['#table'];
    $data = $param['#data'];
    $sql = 'INSERT INTO `'.$table.'` SET ';
    $t = '';
    foreach($data as $field =>  $value) {
      if(is_numeric($value)) {
        $t .= '`'.$field.'`='.$value.',';
      } 
      else {
        $t .= '`'.$field.'`=\''.mysql_real_escape_string($value).'\',';
      }
    }
    $t = substr($t,0,strlen($t)-1);
    $sql = $sql.$t;
    if(true === $var->debug)
      print 'Query: '.$sql."\n";
    $ret['insert'] = false;
    if(false !== mysql_query($sql, $this->dbh)) {
      $ret['id'] = mysql_insert_id();
      $ret['insert'] = true;
    }
    return $ret;
  }

  /**
    * SQL Update
   */
  public function Update($param) {
    global $var;
    
    if(!isset($param['#table'])) {
      exit('Invalid Table Name.');
    }
    if(!isset($param['#data'])) {
      exit('Invalid Dataset Name.');
    }
    if(!isset($param['#where'])) {
      exit('Invalid Where Clause.');
    }
    
    $table = $param['#table'];
    $data = $param['#data'];
    $where = $param['#where'];
    
    $sql = 'UPDATE `'.$table.'` SET ';
    $t = '';
    
    foreach($data as $field =>  $value) {
      if(is_numeric($value)) {
        $t .= '`'.$field.'`='.$value.',';
      } 
      else {
        $t .= '`'.$field.'`=\''.mysql_real_escape_string($value).'\',';
      }
    }
    $t = substr($t,0,strlen($t)-1);
    $sql = $sql.$t;
    $t = '';
    foreach($where as $field =>  $value) {
      if(is_numeric($value)) {
        $t .= '`'.$field.'`='.$value.' AND ';
      } 
      else {
        $t .= '`'.$field.'`=\''.mysql_real_escape_string($value).'\' AND ';
      }
    }
    $t = substr($t,0,strlen($t)-4);
    $sql = $sql.' WHERE '.$t;
    $ret = false;
    if(true === $var->debug)
      print 'Query: '.$sql."\n";
    if(mysql_query($sql)) {
      $info = mysql_info($this->dbh);    
      $t = explode(' ' , $info);
      if(1 == $t[5])
        $ret['update'] = true;
      else {
        $ret['update'] = false;
        if(1 == $t[2])
          $ret['reason' ] = 'Data already updated';
        if(0 == $t[2])
          $ret['reason' ] = 'Specified Key not found';
      }

    } 

    return $ret;

  }

  public function Delete($param) {
    global $var;
    
    if(!isset($param['#table'])) {
      exit('Invalid Table Name.');
    }
    if(!isset($param['#where'])) {
      exit('Invalid Where Clause.');
    }
    $tables = array();
    if(is_array($param['#table']))
      $tables = $param['#table'];
    else
      $tables = array($param['#table']);
    $ret = false;
    foreach($tables as $table){
      $where = $param['#where'];
      $sql = 'DELETE FROM `'.$table.'` ';
      $t = '';
      foreach($where as $field =>  $value) {
        if(is_numeric($value)) {
          $t .= '`'.$field.'`='.$value.' AND ';
        } 
        else {
          $t .= '`'.$field.'`=\''.mysql_real_escape_string($value).'\' AND ';
        }
      }
      $t = substr($t,0,strlen($t)-4);
      $sql = $sql.' WHERE '.$t;
      if(true === $var->debug)
        print 'Query: '.$sql."\n";
      
      if(mysql_query($sql)) {
        if(is_array($param['#table']))
          $ret[$table]['delete'] = 'true';
        else
          $ret['delete'] = 'true';
      }
    }
    return $ret;
  }

  public function Select($param) {
    global $var;
    $sql = '';
    if(isset($param['#sql'])) {
      if(isset($param['#limit']) && '' != $param['#limit']) {
        $param['#sql'] = $param['#sql'].' LIMIT '.$param['#limit'];
      }
      return $this->_select_sql($param);
    }
    else {
      $table = $param['#table'];    
      $list = (isset($param['list']))?$param['list']:false;
      $sql .= 'SELECT * FROM `'.$table.'`';
      if(isset($param['#where']) && is_array($param['#where'])) {
        $where = $param['#where'];      
        $w = '';
        foreach($where as $key  =>  $value) {
          if(is_numeric($value)) {
            $w .= '`'.$key.'`='.$value;
          }
          else {
            $w .= '`'.$key.'`=\''.mysql_real_escape_string($value).'\'';
          }
          $w .=  ' AND ';
        }
        $w = substr($w,0,strlen($w)-4);
        $sql = $sql.' WHERE '.$w;
      }
      
      if(isset($param['#groupby']) && '' != $param['#groupby']) {
        $sql .= ' GROUP BY '.$param['#groupby'];
      }
      
      if(isset($param['#orderby']) && '' != $param['#orderby']) {
        $sql .= ' ORDER BY '.$param['#orderby'];
      }
      
      if(isset($param['#limit']) && '' != $param['#limit']) {
        $sql .= ' LIMIT '.$param['#limit'];
      }
     
      return $this->_select_sql(array(
        '#sql'  =>  $sql,
        'list'  =>  $list,
      ));
    }
  }

  private function _select_sql($param) {
    global $var;
    $sql = '';
    
    if(isset($param['#sql']) && '' != $param['#sql']) {
      $sql = $param['#sql'];
    }
    else {
      print "*** [ PART : lib_database ] ***\n";
      die('Invalid Query Supplied.');
    }
    
    $row_list = false;
    if(isset($param['list']) && true === $param['list']) {
      $row_list = true;
    }
    
    $ret = array();
    if(true === $var->debug)
      print 'Query: '.$sql."\n";
    $qr = @mysql_query($sql);
    $data = array();
    if($qr) {
      $ret['item_count'] = @mysql_num_rows($qr);
      // display($ret['item_count']); exit;
      $ret['db_select'] = true;
      if(true === $row_list) {
        while($row = @mysql_fetch_assoc($qr)) {
          $data[] = $row;
        }
      }
      else {
        $data = @mysql_fetch_assoc($qr);
      }
    }
    else {
      $ret['db_select'] = false;
      $ret['error'] = 'Query Error: '.mysql_error();
    }
    $ret['data'] = $data;
    return $ret;
  }

  public function InsertUnique($param) {
    global $var;
    
    // if skipupdate is true then data wont be updated. ( default SKIPUPDATE: => false )
    
    $table = $param['#table'];
    $data  = $param['#data'];
    $where = $param['#where'];
    $checkkey = $param['#checkkey'];
    $skipupdate = (isset($param['skipupdate']) && true === $param['skipupdate'])?true:false;
    $ret = array();
    
    $d = $this->Select(array(
      '#table'  =>  $table,
      '#where'  =>  $where,
      'list'    =>  false,
    ));
    
    if(isset($d['data'][$checkkey]) && '' != $d['data'][$checkkey]) {
      if(false === $skipupdate) {
        $du = $this->Update(array(
          '#table'  =>  $table,
          '#data'   =>  $data,
          '#where'  =>  $where,
        ));
        $ret['insert'] = false;
        $ret['update'] = true;
      }
      else {
        $ret['insert'] = false;
        $ret['update'] = false;
      }
    }
    else {
      $di = $this->Insert(array(
        '#table'  =>  $table,
        '#data'   =>  $data,
      ));
      $ret['insert'] = $di['insert'];
      $ret['id'] = (!empty($di['id']))?$di['id']:false;
      $ret['update'] = false;
    }
    
    return $ret;
  }
  
}
