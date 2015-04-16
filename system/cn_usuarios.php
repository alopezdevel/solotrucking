<?php
  $mysql_host = "sv25.byethost25.org";
  $mysql_database = "laredone_solotrucking";
  $mysql_username = "laredone_wcenter";
  $mysql_password = "05100248abc";
  $conexion = new mysqli($mysql_host, $mysql_username, $mysql_password, $mysql_database);  
  if(mysqli_connect_error()){
      $mensaje_de_error =  "error de conexion";
  }                   
  
  
?>
