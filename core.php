<?php
/**
 * Blackjack Byte
 *
 * A paid blackjack script
 *
 * @package		Blackjack Byte
 * @author		David Heward
 * @copyright	Copyright (c) 2008 - 2011, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */
 
 // =========== 
 // ! Base Db connect function.  
 // =========== 

 if(!function_exists('connect_db')){
 
 	function connect_db($connection){
 		
 		 if(mysql_connect($connection['host'],$connection['user'],$connection['password'])){
 		 	
 		 	if(!$db = mysql_select_db($connection['database'])){
 		 		die("Could not select your database");
 		 	}
 		 	
 		 }else{
 		 	die("Could not connect to your database ".mysql_error());
 		 }
 		 	
 	}
 
 }
 
 // =========== 
 // ! Small Database class for personal use.  
 // =========== 
 
 class Db{
 
 	function connect(){
 		
 	}
 
	function query($query){
		$this->current_query = mysql_query($query);
		if(mysql_errno()){
			# Log this error.
			echo mysql_error();
			//$this->FURY->logging->mysql_log($query,mysql_error());
		}
		
		return $this;
	}
	
	function num_rows(){
		return mysql_num_rows($this->current_query);
	}
	
	function row(){
		return mysql_fetch_assoc($this->current_query);
	}
	
	function rows(){
		$array = array();
		while($r = mysql_fetch_assoc($this->current_query)){
			$array[] = $r;
		}
		return $array;
	}
	
	
	# Returns the mysql_object as an object
	function as_object(){
		return mysql_fetch_object($this->current_query);
	}
	
	# Returns the mysql_object as an associative array
	function as_assoc(){
		return mysql_fetch_assoc($this->current_query);
	}
	
	function _mes($value){
	
		// Stripslashes
		if (get_magic_quotes_gpc()){
		  $value = stripslashes($value);
		}
		// Quote if not a number
		if (!is_numeric($value)){
		  $value = "'" . mysql_real_escape_string($value) . "'";
		}
		
		return $value;
	
	}	

	// =========== 
	// ! Insert a record to the database   
	// =========== 
	 
	function insert($table, $assoc_arr, $ret = false){
	    foreach($assoc_arr as $k=>$v)
		    $assoc_arr[$k] = $this->_mes($v);
		
		    $insertstr="INSERT INTO `".$table."`";
		    
		    $insertstr.=" (`". implode("`,`", array_keys($assoc_arr)) ."`) VALUES" ;
		    $insertstr.=" (". implode(",", array_values($assoc_arr)) .");" ;
		    
		    $q = $this->query($insertstr);
	   	
	   	if($ret && $q){
	   		return mysql_insert_id();
	   	}
	        
	}
	

}