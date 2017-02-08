<?php
  session_start();
  // Generic functions lib 
  include("functiones_genericas.php"); 
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId 
  
  function get_policies(){
        include("cn_usuarios.php");
        $company = $_SESSION['company'];
        $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
        $transaccion_exitosa = true;
        $registros_por_pagina = $_POST["registros_por_pagina"];
        $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
        $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
            
        //Filtros de informacion //
        $filtroQuery = " WHERE A.iConsecutivoCompania = '".$company."' AND iDeleted = '0'";
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
        $query_rows = "SELECT COUNT(A.iConsecutivo) AS total FROM ct_polizas A ".
                      "LEFT JOIN ct_brokers C ON A.iConsecutivoBrokers = C.iConsecutivo ".
                      "LEFT JOIN ct_tipo_poliza D ON A.iTipoPoliza = D.iConsecutivo ".$filtroQuery;
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
          $sql = "SELECT A.iConsecutivo AS clave, sNumeroPoliza, sName, sDescripcion, DATE_FORMAT(dFechaInicio,'%m/%d/%Y') AS dFechaInicio, DATE_FORMAT(dFechaCaducidad,'%m/%d/%Y') AS dFechaCaducidad, iConsecutivoArchivo, A.iTipoPoliza ".
                 "FROM ct_polizas A ".
                 "LEFT JOIN ct_brokers C ON A.iConsecutivoBrokers = C.iConsecutivo ".
                 "LEFT JOIN ct_tipo_poliza D ON A.iTipoPoliza = D.iConsecutivo  ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
          $result = $conexion->query($sql);
          $rows = $result->num_rows; 
             
            if ($rows > 0) {    
                while ($items = $result->fetch_assoc()) { 
                   if($items["sNumeroPoliza"] != ""){

                         $items['iConsecutivoArchivo'] == '' ? $btn_pdf = "" : $btn_pdf = "<div class=\"btn_view_pdf btn-icon pdf btn-left\" title=\"view Policy File\" onclick=\"window.open('open_pdf.php?idfile=".$items['iConsecutivoArchivo']."&type=company');\"><i class=\"fa fa-file-pdf-o\"></i> <span></span></div>" ;  
                         if($items['dFechaCaducidad'] != ''){
                            
                            $fechaCaducidad = format_date($items['dFechaCaducidad']);
                            $fechaHoy = date("Y-m-d"); 
                            $EstatusPoliza = "";
                            $dias = (strtotime($fechaCaducidad)-strtotime($fechaHoy))/86400; 
                            $dias = floor($dias); 
                            //echo 'vence el'.$items['dFechaCaducidad'].'le quedan:'.$dias;
                            //echo '    ';      
                            if($dias > 0 ){
                               if($dias <= 15){
                                  $EstatusPoliza = "class=\"yellow\"";
                               }else if($dias > 15){
                                  $EstatusPoliza = "class=\"green\""; 
                               } 
                            }else{
                                $EstatusPoliza = "class=\"red\"";
                            }
                            
                             
                         }
                         //if($items['iTipoPoliza'] == 3){
                         if($dias > 0){
                            $btn_drivers  = "<div class=\"btn-icon view btn-left\" title=\"View list of Drivers\" onclick=\"fn_mypolicies.get_list_description('$iConsecutivoPoliza','D');\"><i class=\"fa fa-users\"></i> <span></span></div>"; 
                            $btn_drivers .= "<div class=\"btn-icon view btn-left\" title=\"View list of Units\" onclick=\"fn_mypolicies.get_list_description('$iConsecutivoPoliza','U');\"><i class=\"fa fa-truck\"></i> <span></span></div>";
                         }else{$btn_drivers = "";}
                         $htmlTabla .= "<tr $EstatusPoliza >
                                            <td>".$items['clave']."</td>".
                                           "<td>".$items['sNumeroPoliza']."</td>".
                                           "<td>".$items['sName']."</td>". 
                                           "<td>".$items['sDescripcion']."</td>".  
                                           "<td>".$items['dFechaInicio']."</td>".
                                           "<td>".$items['dFechaCaducidad']."</td>".                                                                                                                                                                                                                     
                                           "<td>
                                                $btn_pdf
                                                $btn_drivers
                                           </td></tr>";  
                                           
                     }else{                                                                                                                                                                                                        
                        
                         $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>"   ;
                     }    
                }
                $conexion->rollback();
                $conexion->close();                                                                                                                                                                       
            } else { 
                
                $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>"   ;    
                
            }
          }
          $response = array("total"=>"$paginas_total","pagina"=>"$pagina_actual","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
          echo json_encode($response); 
  }
  function get_drivers_active(){
      
      $iConsecutivoPoliza = $_POST['iConsecutivoPoliza'];
      $iConsecutivoCompania = $_SESSION['company'];
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
    
      $registros_por_pagina = $_POST["registros_por_pagina"];
      $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
      $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
        
      //Filtros de informacion //
      $filtroQuery = " WHERE siConsecutivosPolizas != '' AND inPoliza = '1' AND iConsecutivoCompania = '$iConsecutivoCompania' AND siConsecutivosPolizas LIKE '%$iConsecutivoPoliza%' ";
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
    $query_rows = "SELECT COUNT(iConsecutivo) AS total FROM ct_operadores ".$filtroQuery;
    $Result = $conexion->query($query_rows);
    $itemssNombre = $Result->fetch_assoc();
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
        $sql = "SELECT iConsecutivo, sNombre, DATE_FORMAT(dFechaNacimiento,'%m/%d/%Y') AS dFechaNacimiento, DATE_FORMAT(dFechaExpiracionLicencia,'%m/%d/%Y') AS dFechaExpiracionLicencia, iExperienciaYear, iNumLicencia, (CASE eTipoLicencia WHEN  1 THEN 'Federal / B1' WHEN  2 THEN 'Commercial / CDL - A' END) AS TipoLicencia ".
               "FROM ct_operadores ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior; 
        $result = $conexion->query($sql);
        $rows = $result->num_rows;   
        if ($rows > 0) {    
                while ($items = $result->fetch_assoc()){ 
                   if($items["sNombre"] != ""){
                                   
                         $htmlTabla .= "<tr id=\"id-".$items['iConsecutivo']."\" >".
                                           "<td>".$items['sNombre']."</td>".
                                           "<td>".$items['dFechaNacimiento']."</td>".
                                           "<td>".$items['iNumLicencia']."</td>". 
                                           "<td>".$items['TipoLicencia']."</td>".
                                           "<td>".$items['dFechaExpiracionLicencia']."</td>".  
                                           "<td>".$items['iExperienciaYear']."</td>".                                                                                                                                                                                                                    
                                           "<td></td></tr>";  
                     }else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";}    
                }
                $conexion->rollback();
                $conexion->close();                                                                                                                                                                       
        }else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>"   ;    }  
    }
     $htmlTabla = utf8_decode($htmlTabla);
     $response = array("total"=>"$paginas_total","pagina"=>"$pagina_actual","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
     echo json_encode($response);
      
  }

  #UNITS:
  function get_units_active(){
      
      $iConsecutivoPoliza = $_POST['iConsecutivoPoliza'];
      $iConsecutivoCompania = $_SESSION['company'];
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
    
      $registros_por_pagina = $_POST["registros_por_pagina"];
      $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
      $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
        
      //Filtros de informacion //
      $filtroQuery = " WHERE siConsecutivosPolizas != '' AND inPoliza = '1' AND iConsecutivoCompania = '$iConsecutivoCompania' AND siConsecutivosPolizas LIKE '%$iConsecutivoPoliza%' ";
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
    $query_rows = "SELECT COUNT(A.iConsecutivo) AS total FROM ct_unidades A ".
                  "LEFT JOIN ct_unidad_radio B ON A.iConsecutivoRadio = B.iConsecutivo ".
                  "LEFT JOIN ct_unidad_modelo C ON A.iModelo = C.iConsecutivo ".$filtroQuery;
    $Result = $conexion->query($query_rows);
    $itemssNombre = $Result->fetch_assoc();
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
        $sql = "SELECT A.iConsecutivo, CONCAT('(',C.sAlias,')',' ',C.sDescripcion ) AS Make, B.sDescripcion AS Radio, iYear, sVIN, sPeso, sTipo, sModelo  ".
               "FROM ct_unidades A ".
               "LEFT JOIN ct_unidad_radio B ON A.iConsecutivoRadio = B.iConsecutivo ".
               "LEFT JOIN ct_unidad_modelo C ON A.iModelo = C.iConsecutivo ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior; 
        $result = $conexion->query($sql);
        $rows = $result->num_rows;   
        if ($rows > 0) {    
                while ($items = $result->fetch_assoc()){ 
                   //if($items["sVIN"] != ""){
                                   
                         $htmlTabla .= "<tr id=\"id-".$items['iConsecutivo']."\" >".
                                           "<td>".$items['sVIN']."</td>".
                                           "<td>".$items['Radio']."</td>".
                                           "<td>".$items['iYear']."</td>". 
                                           "<td>".$items['Make']."</td>".
                                           "<td>".$items['sTipo']."</td>".  
                                           "<td>".$items['sPeso']."</td>".                                                                                                                                                                                                                    
                                           "<td></td></tr>";  
                     //}else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";}    
                }
                $conexion->rollback();
                $conexion->close();                                                                                                                                                                       
        }else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>"   ;    }  
    }
     $htmlTabla = utf8_decode($htmlTabla); 
     $response = array("total"=>"$paginas_total","pagina"=>"$pagina_actual","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
     echo json_encode($response);
  }
  
  
?>
