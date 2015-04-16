<?php
  $mysql_host = "sv25.byethost25.org";
  $mysql_database = "laredone_solotrucking";
  $mysql_username = "laredone_wcenter";
  $mysql_password = "05100248abc";
  
  $conn = mysqli_connect($mysql_host, $mysql_username, $mysql_password);
  if(mysqli_connect_error()){
      //error de conexion    mysqli_connect_error
  }                   
  if (!$dbconn) {
    //error de servidor
  }
  $dbselect = mysqli_select_db($mysql_database, $dbconn);
  if (!$dbselect) {
   //error base de datos
  }
?>
