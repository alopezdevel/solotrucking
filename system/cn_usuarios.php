<?php
  
  #PRODUCCION:
  $mysql_host = "sv25.byethost25.org";
  $mysql_database = "laredone_solotrucking";
  $mysql_username = "laredone_wcenter";
  $mysql_password = "05100248abc"; 
  
  #PRUEBAS:
  /*$mysql_host = "31.22.4.142";
  $mysql_database = "websolu2_stdev";
  $mysql_username = "websolu2_celina";
  $mysql_password = "w3bs0lut10n5"; */
  
  $conexion = new mysqli($mysql_host, $mysql_username, $mysql_password, $mysql_database);     
  if(mysqli_connect_error()){
      $mensaje_de_error =  "error de conexion";
  }                   
  date_default_timezone_set('America/Mexico_City');
  
?>
