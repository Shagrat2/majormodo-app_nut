<?php
/**
* Russian language file for NUT module
*
*/

$dictionary=array(

/* general */
'HELP'=>'Помощь',
'NAMEOFUPS'=>'Имя UPS. Как в ',
'NEED_FOR_CONTROL'=>'Нужен только для упраления. По умолчанию не нужен'

/* end module names */

);

foreach ($dictionary as $k=>$v) {
 if (!defined('LANG_'.$k)) {
  define('LANG_'.$k, $v);
 }
}

?>