<?php
  session_start();
  // Generic functions lib 
  include("functiones_genericas.php"); 
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId 
    
  //Catalogo de compañias:
  function get_companies(){
     
    include("cn_usuarios.php");
    $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
    $transaccion_exitosa = true;
    
    $registros_por_pagina = $_POST["registros_por_pagina"];
    $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
    $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
        
    //Filtros de informacion //
    $filtroQuery = " WHERE iConsecutivo IS NOT NULL AND iDeleted='1'";
    $array_filtros = explode(",",$_POST["filtroInformacion"]);
    foreach($array_filtros as $key => $valor){
        if($array_filtros[$key] != ""){
            $campo_valor = explode("|",$array_filtros[$key]);
            $campo_valor[0] == 'iConsecutivo' ? $filtroQuery.= " AND  ".$campo_valor[0]."='".$campo_valor[1]."' " : $filtroQuery == "" ? $filtroQuery.= " AND  ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'": $filtroQuery.= " AND ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'";
        }
    }
    // ordenamiento//
    $ordenQuery = " ORDER BY ".$_POST["ordenInformacion"]." ".$_POST["sortInformacion"];
    
    //contando registros // 
    $query_rows = "SELECT COUNT(iConsecutivo) AS total FROM ct_companias ".$filtroQuery;
    $Result = $conexion->query($query_rows);
    $items = $Result->fetch_assoc();
    $registros = $items["total"];
    if($registros == "0"){$pagina_actual = 0;}
    $paginas_total = ceil($registros / $registros_por_pagina);
    
    if($registros == "0"){
        $limite_superior = 0;
        $limite_inferior = 0;
        $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";
    }else{
        $pagina_actual == "0" ? $pagina_actual = 1 : false;
        $limite_superior = $registros_por_pagina;
        $limite_inferior = ($pagina_actual*$registros_por_pagina)-$registros_por_pagina;
        $sql = "SELECT iConsecutivo as id, sNombreCompania, sNombreContacto, sDireccion AS direccion, CONCAT(sCiudad, ' ', sEstado ) AS estado, 
                sCodigoPostal AS zipcode, sTelefonoPrincipal AS tel,sTelefono2 AS tel_2,sTelefono3 AS tel_3, sUsdot AS usdot, iOnRedList
                FROM  ct_companias ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
        $result = $conexion->query($sql);
        $rows = $result->num_rows;    
        if ($rows > 0) {    
            while ($usuario = $result->fetch_assoc()) { 
               if($usuario["id"] != ""){
                     //telefonos:
                     $telefonos = $usuario['tel'];
                     if($usuario['tel_2'] != ""){  $telefonos .= " / ".$usuario['tel_2'];}
                     if($usuario['tel_3'] != ""){$telefonos .= " / ".$usuario['tel_3'];}
                     //Redlist:
                     if($usuario['iOnRedList'] == '1'){
                        $redlist_class = "class=\"row_red\"";
                        $redlist_icon = "<i class=\"fa fa-star\" style=\"color:#e8051b;margin-right:4px;\"></i>"; 
                     }else{
                        $redlist_icon = ""; 
                        $redlist_class = "";
                     }
                     
                     $btns = "<div class=\"btn_active btn-icon add btn-left\" title=\"Enable Company\"><i class=\"fa fa-check-circle\"></i></div>";
                     
                     $htmlTabla .= "<tr ".$redlist_class.">
                                        <td>".$usuario['id']."</td>".
                                       "<td>".$redlist_icon.$usuario['sNombreCompania']."</td>".
                                       "<td>".$usuario['usdot']."</td>". 
                                       "<td>".$usuario['direccion']."</td>".
                                       "<td>".$usuario['estado']."</td>".
                                       "<td>".$usuario['zipcode']."</td>".
                                       "<td>".$usuario['sNombreContacto']."</td>".
                                       "<td>".$telefonos."</td>".                                                                                                                                                                                                                        
                                       "<td>$btns</td></tr>";
                 }else{                                                                                                                                                                                                        
                    
                     $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>"   ;
                 }    
            }
        
            
            $conexion->rollback();
            $conexion->close();                                                                                                                                                                       
        } else { 
            
            $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";    
            
        } 
    }
     $response = array("total"=>"$paginas_total","pagina"=>"$pagina_actual","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
     echo json_encode($response); 
  } 
  function enable_company(){
      
      $error = '0';  
      $msj   = "";  
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      //DESACTIVAR COMPANY:
      $query = "UPDATE ct_companias SET iDeleted = '0' WHERE iConsecutivo = '".$_POST["clave"]."'";
      $conexion->query($query);
      $conexion->affected_rows < 1 ? $transaccion_exitosa = false : $transaccion_exitosa = true;
      if($transaccion_exitosa){$msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The company has been enable succesfully!</p>'; }
      else{$msj = "A general system error ocurred : internal error";$error = "1";}
      
      if($error == "0"){$conexion->commit();$conexion->close();}
      else{$conexion->rollback();$conexion->close();}
        
      $response = array("msj"=>"$msj","error"=>"$error");   
      echo json_encode($response);
  }
  
?>
