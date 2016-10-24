<?php
$mysql_host = "sv25.byethost25.org";
$mysql_database = "laredone_solotrucking";
$mysql_username = "laredone_wcenter";
$mysql_password = "05100248abc";
$dbconn = mysql_connect($mysql_host, $mysql_username, $mysql_password);
if (!$dbconn) {
    
    die();
}                  
$dbselect = mysql_select_db($mysql_database, $dbconn);
if (!$dbselect) {    
    die();
}
?>
