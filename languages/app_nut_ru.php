<?php
/**
* Russian language file for NUT module
*
*/

$dictionary=array(

/* general */
'HELP'=>'Помощь',
'NAMEOFUPS'=>'Имя UPS. Как в ',
'NEED_FOR_CONTROL'=>'Нужен только для упраления. По умолчанию не нужен',
'SUPPORTED_COMMANDS'=>'Поддерживаемые команды',
'USE_IN_SCRIPT'=>'Использование в сценариях/методах:',
'NUT_CONTROL'=>'Выполняет $cmd для $vname с параметром $val',
'NUT_QUICK_BATTERY_TEST'=>'Запуск быстрого теста батареи'

/* end module names */

);

foreach ($dictionary as $k=>$v) {
 if (!defined('LANG_'.$k)) {
  define('LANG_'.$k, $v);
 }
}

?>