<?php

/**
*
* Network UPS tools client class
*
* @author Ivan Z <ivan@jad.ru>
* @copyright Ivan Z
* Start: 		22.01.2015	
* LastChange:	29.01.2015
* Ver 2
*
* API - http://www.networkupstools.org/docs/developer-guide.chunked/ar01s09.html
*
**/
 
class nut_client {
	private $socket = NULL;
	
	public $upsname;
	public $host;
	public $port;
	private $username;
	private $password;
	public $lasterrordesc;
	
	function __construct($upsname, $host = 'localhost', $port = 3493){
		$this->upsname = $upsname;
		$this->host = $host;
        $this->port = $port;                
	}
	
	function __destruct() {
		$this->close();
	}
	 
    function connect(){        
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);		
        if (!$this->socket ) {
          error_log("fsockopen() $errno, $errstr \n");
          return false;
        }
		
		socket_set_option($this->socket,SOL_SOCKET, SO_RCVTIMEO, array("sec"=>2, "usec"=>0));
		socket_set_option($this->socket,SOL_SOCKET, SO_SNDTIMEO, array("sec"=>2, "usec"=>0));
		
		$host = gethostbyname($this->host);
		$result = socket_connect($this->socket, $host, $this->port);
		if ($result === false) {
		  error_log("socket_connect() failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket)) . "\n");
          return false;
		}
		
        return true;
    }

	function close(){
	   if ($this->socket != NULL){
         socket_close($this->socket);
	   }
    }
	
	function write($in){
		socket_write($this->socket, $in, strlen($in));
	}
       
    function read($in = 2048 ){
	  return socket_read($this->socket, $in, PHP_NORMAL_READ); 
    } 
	
	function getline(){
		$msg = $this->read();
		
		// Test error
		$arr = explode(" ", $msg);
		if ($arr[0] == "ERR"){
			$this->lasterrordesc = $msg;
			return false;
		}
		
		return $msg;
	}
	
	function readlist(){		
	    $arr = NULL;
		
		// first line
		$msg = $this->getline();
		if (!$msg) return false;
		
		while (true){
		  $msg = $this->getline();
		  if (!$msg) break;
		  
		  $line = explode(" ", $msg);
		  if ($line[0] == "END") break;
			  
		  $arr[] = $msg;
		}
		
		return $arr;
	}	
	
	function login($username = NULL, $password = NULL){		
		if($username) $this->username = $username;
        if($password) $this->password = $password;
		
		// USERNAME
		$this->write( "USERNAME $username\n");
		$msg = $this->getline();
		if (!$msg) return false;		
		
		// PASSWORD
		$this->write( "PASSWORD $password\n");
		$msg = $this->getline();
		if (!$msg) return false;		
		
		// LOGIN
		$this->write( "LOGIN ".$this->upsname."\n");
		$msg = $this->getline();
		if (!$msg) return false;		
		
		return true;
	}
	
	function logout(){
		$this->write( "LOGOUT\n");
		
		$msg = $this->getline();
		if (!$msg) return false;
		
		return ture;
	}
	
	function upsdesc(){
		$this->write( "GET UPSDESC ".$this->upsname."\n");
		
		$msg = $this->getline();
		
		if (!$msg) return false;
		
		if (preg_match('~"([^"]*)"~u' , $msg , $m)) {			
		  return $m[1];
		} else {
		  return false;
		}
	}
	
	function getvar($varname){
		$this->write( "GET VAR ".$this->upsname." $varname\n");
		
		$msg = $this->getline();
		if (!$msg) return false;
		
		if (preg_match('~"([^"]*)"~u' , $msg , $m)) {
		  return $m[1];
		} else {
		  return false;
		}
	}
	
	function vartype($varname){
		$this->write( "GET TYPE ".$this->upsname." $varname\n");
		
		$msg = $this->getline();
		if (!$msg) return false;
		
		$arr = explode(" ", $msg);
		
		return $arr[3];		
	}
	
	function vardesc($varname){
		$this->write( "GET DESC ".$this->upsname." $varname\n");
		
		$msg = $this->getline();
		if (!$msg) return false;
		
		if (preg_match('~"([^"]*)"~u' , $msg , $m)) {
		  return $m[1];
		} else {
		  return false;
		}	
	}
	
	function varenum($varname){
		$this->write( "LIST ENUM ".$this->upsname." $varname\n");
		
		$lines = $this->readlist();
		
		$arr = NULL;
		if ($lines){
			foreach ($lines as $value) {
				preg_match('~"([^"]*)"~u' , $value , $m);
			
				$vals = explode(" ", $value);			
				$arr[ $vals[2] ] = $m[1];
			}
		}
		return $arr;
	}
	
	function varrange($varname){
		$this->write( "LIST RANGE ".$this->upsname." ".$varname."\n");
		
		$lines = $this->readlist();
		
		$arr = NULL;
		if ($lines){
			foreach ($lines as $value) {
				preg_match('~"([^"]*)"~u', $value, $m);
					
				$vals = explode(" ", $value);			
				$arr[ $vals[2] ] = array($m[1], $m[2]);
			}
		}
		return $arr;		
	}
	
	function cmddesc($cmdname){
		$this->write( "GET CMDDESC ".$this->upsname." ".$cmdname."\n");
		
		$msg = $this->getline();
		if (!$msg) return false;
		
		if (preg_match('~"([^"]*)"~u' , $msg , $m)) {
		  return $m[1];
		} else {
		  return false;
		}		
	}
	
	function listups(){
		$this->write( "LIST UPS\n");
		
		$lines = $this->readlist();
		
		$arr = NULL;
		if ($lines){
			foreach ($lines as $value) {
				preg_match('~"([^"]*)"~u' , $value , $m);					
			
				$vals = explode(" ", $value);			
				$arr[ $vals[1] ] = $m[1];
			}
		}
		return $arr;
	}
	
	function listvar(){
		$this->write( "LIST VAR ".$this->upsname."\n");
		
		$lines = $this->readlist();
		
		$arr = NULL;
		if ($lines){
			foreach ($lines as $value) {
				preg_match('~"([^"]*)"~u' , $value , $m);
			
				$vals = explode(" ", $value);			
				$arr[ $vals[2] ] = $m[1];
			}
		}
		return $arr;
	}
	
	function listrw(){
		$this->write( "LIST RW ".$this->upsname."\n");
		
		$lines = $this->readlist();
		
		$arr = NULL;
		if ($lines){
			foreach ($lines as $value) {
				preg_match('~"([^"]*)"~u' , $value , $m);
			
				$vals = explode(" ", $value);			
				$arr[ $vals[2] ] = $m[1];
			}
		}
		return $arr;
	}
	
	function listcmd(){
		$this->write( "LIST CMD ".$this->upsname."\n");
		
		$lines = $this->readlist();
		
		$arr = NULL;
		if ($lines){
			foreach ($lines as $value) {
				$vals = preg_split("/[\s,]+/", $value);			
				
				$arr[ ] = $vals[2];
			}
		}
		return $arr;
	}	
	
	function setvar($vname, $value){
		$this->write( "SET VAR ".$this->upsname.' '.$vname.' "'.$value.'"'."\n");
		
		$msg = $this->getline();
		if (!$msg) return false;
		
		return true;		
	}
	
	function master(){
		$this->write( "MASTER ".$this->upsname."\n");
		
		$msg = $this->getline();
		if (!$msg) return false;
		
		return true;		
	}
	
	function fsd(){
		$this->write( "FSD ".$this->upsname."\n");
		
		$msg = $this->getline();
		if (!$msg) return false;
		
		return true;		
	}	
	
	function starttls(){
		$this->write( "STARTTLS ".$this->upsname."\n");
		
		$msg = $this->getline();
		if (!$msg) return false;
		
		return true;		
	}		
	
	function ver(){
		$this->write( "VER ".$this->upsname."\n");
		
		$msg = $this->getline();
		if (!$msg) return false;
		
		return $msg;			
	}
	
	function netver(){
		$this->write( "NETVER ".$this->upsname."\n");
		
		$msg = $this->getline();
		if (!$msg) return false;
		
		return $msg;			
	}	
	
	function instcmd($cmdname){
		$this->write( "INSTCMD ".$this->upsname." ".$cmdname."\n");
		
		$msg = $this->getline();
		if (!$msg) return false;
		
		return true;
	}
}

?>