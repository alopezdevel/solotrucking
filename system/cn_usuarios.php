<?php
  $mysql_host = "127.0.0.1";
  $mysql_database = "bd_nadtrafico_global";
  $mysql_username = "pruebas";
  $mysql_password = "gl0b@l";
  
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
