<?php
      session_start();
      // Generic functions lib 
      $mysql_host = "sv25.byethost25.org";
      $mysql_database = "laredone_solotrucking";
      $mysql_username = "laredone_wcenter";
      $mysql_password = "05100248abc";
      $conexion = new mysqli($mysql_host, $mysql_username, $mysql_password, $mysql_database);     
      if(mysqli_connect_error()){
          $mensaje_de_error =  "error de conexion";
      }  
      
      
      
      $query = "SELECT sOrigen FROM rep_cotizaciones_general_motors WHERE sOrigen != '' AND sOrigen != 'NA' GROUP BY sOrigen ORDER BY sOrigen ASC";
      $result = $conexion->query($query);
      $rows = $result->num_rows; 
      
      if($rows > 0){
          while ($origenes = $result->fetch_assoc()){ 
                
              echo "SELECT sOrigen AS 'ORIGEN', sDestino AS 'DESTINO', sTipoEquipo AS 'TIPO DE EQUIPO', iVenta AS 'VENTA' ".
                   "FROM rep_cotizaciones_general_motors  ".
                   "WHERE sOrigen = '".$origenes['sOrigen']."' AND sTipoEquipo != '' ORDER BY sTipoEquipo ASC; \n";
          }
      }
?>
