<?php
  if ($this->mode=='setvalue') {
   global $prop_id;
   global $new_value;
   global $id;
   $this->setProperty($prop_id, $new_value);
   $this->redirect("?id=".$id."&view_mode=".$this->view_mode."&edit_mode=".$this->edit_mode);
  } 

  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $table_name='app_nut_devices';
  $rec=SQLSelectOne("SELECT * FROM $table_name WHERE ID='$id'");
  
  if ($this->mode=='update') {
   $ok=1;

   //UPDATING RECORD
   if ($ok) {
	   
	//???   

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
  
  if ($rec['ID']) {
   $properties=SQLSelect("SELECT * FROM app_nut_params WHERE DEVICEID='".$rec['ID']."' ORDER BY ID");
   if ($this->mode=='update') {
    $total=count($properties);
    for($i=0;$i<$total;$i++) {
     global ${'linked_object'.$properties[$i]['ID']};
     global ${'linked_property'.$properties[$i]['ID']};

     $old_linked_object=$properties[$i]['LINKED_OBJECT'];
     $old_linked_property=$properties[$i]['LINKED_PROPERTY'];

     if (${'linked_object'.$properties[$i]['ID']} && ${'linked_property'.$properties[$i]['ID']}) {
      $properties[$i]['LINKED_OBJECT']=${'linked_object'.$properties[$i]['ID']};
      $properties[$i]['LINKED_PROPERTY']=${'linked_property'.$properties[$i]['ID']};
      SQLUpdate('app_nut_params', $properties[$i]);
     } elseif ($properties[$i]['LINKED_OBJECT'] || $properties[$i]['LINKED_PROPERTY']) {
      $properties[$i]['LINKED_OBJECT']='';
      $properties[$i]['LINKED_PROPERTY']='';
      SQLUpdate('app_nut_params', $properties[$i]);
     }

     if ($properties[$i]['LINKED_OBJECT'] && $properties[$i]['LINKED_PROPERTY']) {
      addLinkedProperty($properties[$i]['LINKED_OBJECT'], $properties[$i]['LINKED_PROPERTY'], $this->name);
     }

     if ($old_linked_object && $old_linked_object!=$properties[$i]['LINKED_OBJECT'] && $old_linked_property && $old_linked_property!=$properties[$i]['LINKED_PROPERTY']) {
      removeLinkedProperty($old_linked_object, $old_linked_property, $this->name);
     }
    }
   }
   $out['PROPERTIES']=$properties;
  }

  $out['CMDS_NOTE'] = str_replace("\n", "<br/>\n", $rec['CMDNOTE']);
  
  $out['SCRIPTS']=SQLSelect("SELECT ID, TITLE FROM scripts ORDER BY TITLE"); 
?>