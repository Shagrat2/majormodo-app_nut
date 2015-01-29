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
'SUPPORTED_COMMANDS'=>'Supported commands',
'USE_IN_SCRIPT'=>'Use in Script/Mthods:',
'NUT_CONTROL'=>'Do $cmd for $vname with params $val',
'NUT_QUICK_BATTERY_TEST'=>'Start quick battery test'

/* end module names */

);

foreach ($dictionary as $k=>$v) {
 if (!defined('LANG_'.$k)) {
  define('LANG_'.$k, $v);
 }
}

?>