<?php
  
  if($_SERVER["HTTP_HOST"]=="stdev.websolutionsac.com"){
        #DESARROLLO:
        $mysql_host     = "sv55.ifastnet4.org";
        $mysql_database = "websolu2_stdev";
        $mysql_username = "websolu2_celina";
        $mysql_password = "w3bs0lut10n5"; 
        
    }else if($_SERVER["HTTP_HOST"] == "solotrucking.laredo2.net" || $_SERVER["HTTP_HOST"] == "st.websolutionsac.com"){
        #PRODUCCION:
        /*$mysql_host = "sv25.byethost25.org";
        $mysql_database = "laredone_solotrucking";
        $mysql_username = "laredone_wcenter";
        $mysql_password = "05100248abc";*/
        $mysql_host     = "sv55.ifastnet4.org";
        $mysql_database = "websolu2_st";
        $mysql_username = "websolu2_st";
        $mysql_password = "sL1906TrcK";
    } 
  
  $conexion = new mysqli($mysql_host, $mysql_username, $mysql_password, $mysql_database);     
  if(mysqli_connect_error()){
      $mensaje_de_error =  "error de conexion";
  }                   
  date_default_timezone_set('America/Mexico_City');
  
?>
