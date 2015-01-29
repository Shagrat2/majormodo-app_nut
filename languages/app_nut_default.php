<?php
/**
* Russian language file for NUT module
*
*/

$dictionary=array(

/* general */
'HELP'=>'Help',
'NAMEOFUPS'=>'UPS name. Like ',
'NEED_FOR_CONTROL'=>'Need only for control. Default not need.',
'SUPPORTED_COMMANDS'=>'Supported commands'

/* end module names */

);

foreach ($dictionary as $k=>$v) {
 if (!defined('LANG_'.$k)) {
  define('LANG_'.$k, $v);
 }
}

?>