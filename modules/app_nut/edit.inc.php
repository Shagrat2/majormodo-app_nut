<?
/*
* @version 0.1 (wizard)
*/

  include_once("nut.class.php");
  
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  } 
  
  $table_name='app_nut_devices';
  $rec=SQLSelectOne("SELECT * FROM $table_name WHERE ID='$id'");
  
  if ($this->mode=='update') {
	$ok=1;
    
	  // Host
	  global $nut_host;
	  if ($nut_host=='') {
		  $nut_host='localhost';
	  }
	  $rec['HOST'] = $nut_host;
	  
	  // Port
	  global $nut_port;
	  if ($nut_port=='') {
		  $nut_port=3493;
	  }
	  $rec['PORT'] = $nut_port;
		
	  // Ups
	  global $nut_ups;
	  if ($nut_ups=='') {
		$out['ERR_UPS']=1;
		$ok=0;
	  }   
	  $rec['UPS'] = $nut_ups;
		
	  // UserName
	  global $nut_username;  
	  $rec['USERNAME'] = $nut_username;
	  
	  // Password
	  global $nut_password;
	  $rec['PASSWORD'] = $nut_password;
	  
	  // Interval
	  global $nut_interval;
	  $rec['INTERVAL'] = $nut_interval;
	  
	  // Code	  
	  global $code;
      $rec['CODE']=$code; 
	  
	  // Script
	  global $run_type;

      if ($run_type=='script') {
        global $script_id;
        $rec['SCRIPT_ID']=$script_id;
      } else {
        $rec['SCRIPT_ID']=0;
      } 
	 
	  $ups = new nut_client( $nut_ups, $nut_host, $nut_port );
	  if (!$ups->connect()){
		  $out['ERR_HOST']=1;
		  $ok=0;
	  } else {
		 // Title
		 $title = $ups->upsdesc($nut_ups);
		 if (!$title){
		   $out['ERR_UPS']=1;
		   $ok=0;
		 } else {
		   $rec['TITLE'] = $title;
		 }
		 
		 // Command descript
		 $cmds = $ups->listcmd();
		 if ($cmds){
			$lines = "";
			// Parse description
			foreach ($cmds as $cmd){
			  $note = $ups->cmddesc( $cmd );
			  $lines .= $cmd." - ".$note."\n";
			}
			$rec['CMDNOTE'] = $lines;
		 }
	  }	 
					
	  if ($ok) { 
		if ($rec['ID']) {  
		  SQLUpdate($table_name, $rec); // update
		} else {
		  $new_rec=1;		
		  $rec['ID'] = SQLInsert($table_name, $rec);
		  
		  // Params
		  $params = $ups->listvar();
		  if ($params){
			  foreach ($params as $par => $value){				  				  
				  $rec_par['DEVICEID'] = $rec['ID'];
				  $rec_par['TITLE'] = $par;
				  $rec_par['NOTE'] = $ups->vardesc( $par );
				  $rec_par['VALUE'] = $value;
				  
				  SQLInsert('app_nut_params', $rec_par);
			  }
		  }		  
		}	
		$out['OK']=1;	  	
	  } else {
		$out['ERR']=1;
	  }   
  }
  
  if (is_array($rec)) {
    foreach($rec as $k=>$v) {
      if (!is_array($v)) {
        $rec[$k]=htmlspecialchars($v);
      }
    }
  } 
  outHash($rec, $out);     
  
  $out['SCRIPTS']=SQLSelect("SELECT ID, TITLE FROM scripts ORDER BY TITLE");
 ?>