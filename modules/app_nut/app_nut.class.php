<?
/**
* NUT
*
*
* @package project
* @author Ivan Z. <ivan@jad.ru>
* @copyright http://www.smartliving.ru/ (c)
* Ver: 0.2
*
*/

include_once("nut.class.php");

class app_nut extends module {
/**
* blank
*
* Module class constructor
*
* @access private
*/
function app_nut() {
  $this->name="app_nut";
  $this->title="NUT";
  $this->module_category="<#LANG_SECTION_DEVICES#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams() {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  if ($this->single_rec) {
   $out['SINGLE_REC']=1;
  }
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}

/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {	
	
	if ($this->view_mode == ''){
		$this->listups($out);
	}
	
	if ($this->view_mode == 'edit'){
		$this->editups($out, $this->id);
	}
	
	if ($this->view_mode == 'delete') {
      $this->deleteups($this->id);
      $this->redirect("?");
    } 
	
	if ($this->view_mode == 'propertyes'){
	  $this->propertyes($out, $this->id);
	}
	
	/*
	switch ($this->view_mode){					
            case 'help':
                $this->view_mode = "help";
                break;						
			case '':
				$this->listups($out);
				break;
	}
	*/
}
/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
 $this->admin($out);
}

/**
* List of ups
*
* Module frontend
*
* @access private
*/
function listups(&$out) {
	require(DIR_MODULES.$this->name.'/listups.inc.php');
}

/**
* addconsole
*
* Module frontend
*
* @access private
*/
function editups(&$out, $id){
  require(DIR_MODULES.$this->name.'/edit.inc.php'); 	
}

/**
* deleteups
*
* Module frontend
*
* @access private
*/
function deleteups($id) {
  $rec=SQLSelectOne("SELECT * FROM app_nut_devices WHERE ID='$id'");
  // some action for related tables
  SQLExec("DELETE FROM app_nut_params WHERE DEVICEID='".$rec['ID']."'");
  SQLExec("DELETE FROM app_nut_devices WHERE ID='".$rec['ID']."'");  
} 

/**
* propertyes
*
* Module frontend
*
* @access private
*/
function propertyes(&$out, $id){
  require(DIR_MODULES.$this->name.'/propertyes.inc.php'); 	
}

/**
* Title
*
* Description
*
* @access public
*/
function checkUPS() { 
  
  // Get checked ups
  $upslist=SQLSelect("SELECT * FROM app_nut_devices WHERE CHECK_NEXT<=NOW()");
     
  $total=count($upslist);
  for($i=0;$i<$total;$i++) {
    $ups=$upslist[$i]; 
   
    $interval=$ups['INTERVAL'];
    if (!$interval) {
      $interval=60;
    }
    $ups['CHECK_NEXT']=date('Y-m-d H:i:s', time()+$interval);
    SQLUpdate('app_nut_devices', $ups); 
	
    // Create
	$nut = new nut_client($ups['UPS'], $ups['HOST'], $ups['PORT']);
	
	// Connect
	if ($nut->connect()){
	  // Login
	  if ($ups['USERNAME']) {
		  if (!$nut->login($ups['USERNAME'], $ups['PASSWORD'])){
			  DebMes("Error in nut connection: Error login"); 
		  }
	  }
	  
	  // Get values
	  $change = false;
	  
	  $params = $nut->listvar();
	  if ($params){
		  foreach ($params as $pname => $pval){
			$prec=SQLSelectOne("SELECT * FROM app_nut_params WHERE DEVICEID='".$ups['ID']."' AND TITLE='".DBSafe($pname)."'"); 
			if ($prec['ID']) {
              if ($pval != $prec['VALUE']){
				  $prec['VALUE'] = $pval;
				  SQLUpdate('app_nut_params', $prec);
				  
				  if ($prec['LINKED_OBJECT'] && $prec['LINKED_PROPERTY']) {
					setGlobal($prec['LINKED_OBJECT'].'.'.$prec['LINKED_PROPERTY'], $prec['VALUE'], array($this->name=>'0'));
					
					$change = true;
				  } 
			  }
			}
		  }
	  }
	  
	  $ups['CHECK_LATEST']=date('Y-m-d H:i:s');
      $ups['CHECK_NEXT']=date('Y-m-d H:i:s', time()+$interval);
	  SQLUpdate('app_nut_devices', $ups); 
	  
	  if ($change){		
	    $params=array('VALUE'=>$params);
		// do some status change actions
		$run_script_id=0;
		$run_code='';
		 // got online
		if ($ups['SCRIPT_ID']) {
		  $run_script_id=$ups['SCRIPT_ID'];
		} elseif ($ups['CODE']) {
		  $run_code=$ups['CODE'];
		}

		if ($run_script_id) {		  
		  //run script
		  runScript($run_script_id, $params);
		} elseif ($run_code) {
			//run code
				  try {
					   $code=$run_code;
					   $success=eval($code);
					   if ($success===false) {
						DebMes("Error in NUT code: ".$code);
					   }
				  } catch(Exception $e){
				   DebMes('Error: exception '.get_class($e).', '.$e->getMessage().'.');
				  }

		} 
	  }
	} else {
	  DebMes("Error in nut connection: ".$ups['HOST'].":".$ups['PORT']); 
	}
	
	$nut = NULL;
  }  
}

/**
* Control
*
* Module installation routine
*
* @access private
*/
function control($alias, $cmd, $name = NULL, $val = NULL){
  $ups=SQLSelectOne("SELECT * FROM app_nut_devices WHERE UPS='$alias'");
 
  if ($ups['ID']) {
	  // Create
	$nut = new nut_client($ups['UPS'], $ups['HOST'], $ups['PORT']);
	
	// Connect
	if ($nut->connect()){
	  // Login
	  if ($ups['USERNAME']) {
		  if (!$nut->login($ups['USERNAME'], $ups['PASSWORD'])){
			  DebMes("Error in nut connection: Error login"); 
		  }
	  }
	  
	  switch ($cmd){		
		case 'upsdesc':
		  $res = $nut->upsdesc();
		  return $res;
		case 'getvar':
		  $res = $nut->getvar($name);
		  return $res;
		case 'vartype':
		  $res = $nut->vartype($name);
		  return $res;
		case 'vardesc':
		  $res = $nut->vardesc($name);
		  return $res;		  
		case 'varenum':
		  $res = $nut->varenum($name);
		  return $res;		  
		case 'varrange':
		  $res = $nut->varrange($name);
		  return $res;		  		  
		case 'cmddesc':
		  $res = $nut->cmddesc($name);
		  return $res;
		case 'listups':
		  $res = $nut->listups();
		  return $res;
		case 'listvar':
		  $res = $nut->listvar();
		  return $res;
		case 'listrw':
		  $res = $nut->listrw();
		  return $res;
		case 'listcmd':
		  $res = $nut->listcmd();
		  return $res;
		case 'instcmd':
		  $res = $nut->instcmd($name);
		  return $res;
		case 'setvar':
		  $res = $nut->setvar($name, $val);
		  return $res;
		case 'master':
		  $res = $nut->master();
		  return $res;
		case 'fsd':
		  $res = $nut->fsd();
		  return $res;
		case 'starttls':
		  $res = $nut->starttls();
		  return $res;
		case 'ver':
		  $res = $nut->ver();
		  return $res;
		case 'netver':
		  $res = $nut->netver();
		  return $res;
	  }
	}
	
	$nut = NULL;
  }
}

/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install() {
  parent::install();
 }
 
 /**
* dbInstall
*
* Database installation routine
*
* @access private
*/
 function dbInstall($data) {
/*
app_nut_devices - Devices
app_nut_properties - Properties
*/
  $data = <<<EOD
 app_nut_devices: ID int(10) unsigned NOT NULL auto_increment 
 app_nut_devices: HOST varchar(255) NOT NULL DEFAULT 'localhost' 
 app_nut_devices: PORT int(10) unsigned NOT NULL DEFAULT '3493'
 app_nut_devices: UPS varchar(255) NOT NULL DEFAULT ''
 app_nut_devices: USERNAME varchar(255) NOT NULL DEFAULT ''
 app_nut_devices: PASSWORD varchar(255) NOT NULL DEFAULT ''
 app_nut_devices: TITLE varchar(255) NOT NULL DEFAULT ''
 app_nut_devices: CMDNOTE text NOT NULL DEFAULT ''
 app_nut_devices: SCRIPT_ID int(10) NOT NULL DEFAULT '0'
 app_nut_devices: CODE text 
 app_nut_devices: CHECK_LATEST datetime
 app_nut_devices: CHECK_NEXT datetime
 app_nut_devices: INTERVAL int(10) NOT NULL DEFAULT '0'
 
 app_nut_params: ID int(10) unsigned NOT NULL auto_increment
 app_nut_params: DEVICEID int(10) NOT NULL DEFAULT '0'
 app_nut_params: TITLE varchar(255) NOT NULL DEFAULT ''
 app_nut_params: NOTE varchar(255) NOT NULL DEFAULT ''
 app_nut_params: VALUE varchar(255) NOT NULL DEFAULT ''
 app_nut_params: LINKED_OBJECT varchar(255) NOT NULL DEFAULT ''
 app_nut_params: LINKED_PROPERTY varchar(255) NOT NULL DEFAULT ''  
EOD;
  parent::dbInstall($data);
 }
// --------------------------------------------------------------------
}
?>