<?php
  
  session_start();
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId 
  
  function get_drivers(){
        include("cn_usuarios.php");
        $company = $_SESSION['company'];
        $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
        $transaccion_exitosa = true;
        $registros_por_pagina = $_POST["registros_por_pagina"];
        $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
        $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
            
        //Filtros de informacion //
        $filtroQuery = " WHERE iConsecutivoCompania = '".$company."' ";
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
        $query_rows = "SELECT COUNT(iConsecutivo) AS total FROM ct_operadores $filtroQuery";
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
          $sql = "SELECT iConsecutivo, sNombre, IF (dFechaNacimiento != '' AND dFechaNacimiento != '0000-00-00' , DATE_FORMAT(dFechaNacimiento ,'%m/%d/%Y'), '') AS dFechaNacimiento, ".
                 "iNumLicencia, IF (dFechaExpiracionLicencia != '' AND dFechaExpiracionLicencia != '0000-00-00' , DATE_FORMAT(dFechaExpiracionLicencia ,'%m/%d/%Y'), '') AS dFechaExpiracionLicencia, ".
                 "eTipoLicencia,iExperienciaYear ".
                 "FROM ct_operadores ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
          $result = $conexion->query($sql);
          $rows = $result->num_rows; 
             
            if ($rows > 0) {    
                while($items = $result->fetch_assoc()) { 
                    
                    
                    $btn_viewpolicies = "<div class=\"btn-icon btn_view_policies add btn-left\" title=\"View policies in which it is active\"><i class=\"fa fa-info-circle\"></i><span></span></div>";
                    $htmlTabla .= "<tr><td id=\"".$items['iConsecutivo']."\">".strtoupper($items['sNombre'])."</td>".
                                   "<td>".$items['dFechaNacimiento']."</td>". 
                                   "<td>".$items['iNumLicencia']."</td>".  
                                   "<td>".$items['eTipoLicencia']."</td>".
                                   "<td>".$items['dFechaExpiracionLicencia']."</td>".   
                                   "<td>".$items['iExperienciaYear']."</td>".                                                                                                                                                                                                                     
                                   "<td> 
                                        <div class=\"btn_edit btn-icon edit btn-left\" title=\"Edit\"><i class=\"fa fa-pencil-square-o\"></i><span></span></div>
                                        $btn_viewpolicies
                                   </td></tr>";  
                }
                $conexion->rollback();
                $conexion->close();                                                                                                                                                                       
            } else { 
                
                $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>"   ;    
                
            }
          }
          $htmlTabla = utf8_decode($htmlTabla);
          $response = array("total"=>"$paginas_total","pagina"=>"$pagina_actual","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
          echo json_encode($response); 
  }
  function get_drivers_policies(){
       include("cn_usuarios.php");
        $company = $_SESSION['company'];
        $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
        $transaccion_exitosa = true;
        $iConsecutivo = trim($_POST['iConsecutivo']);
        $error = "0";
        $mensaje = "";
        if($iConsecutivo != ""){
            $query     = "SELECT siConsecutivosPolizas FROM ct_operadores WHERE iConsecutivo = '$iConsecutivo' AND iConsecutivoCompania = '$company'";
            $result    = $conexion->query($query);
            $items     = $result->fetch_assoc();
            $polizas   = $items["siConsecutivosPolizas"];
            $polizas   = explode(',',$polizas);
            $total     = count($polizas);
            $htmlTabla = "";
            if($total > 0){
                for($i = 0 ; $i < $total ; $i++){
                    $query = "SELECT sNumeroPoliza, sDescripcion, DATE_FORMAT(dFechaInicio,'%m/%d/%Y') AS dFechaInicio, DATE_FORMAT(dFechaCaducidad,'%m/%d/%Y') AS dFechaCaducidad, iConsecutivoArchivo, A.iTipoPoliza ".
                             "FROM ct_polizas A ".
                             "LEFT JOIN ct_tipo_poliza D ON A.iTipoPoliza = D.iConsecutivo ".
                             "WHERE A.iConsecutivo = '".$polizas[$i]."' AND iConsecutivoCompania = '$company' ".
                             "AND iDeleted = '0' AND dFechaCaducidad >= CURDATE()";
                    $result = $conexion->query($query);
                    $rows = $result->num_rows;
                    if($rows > 0){
                       $items = $result->fetch_assoc();
                       $EstatusPoliza = "";
                       $htmlTabla .= "<tr $EstatusPoliza ><td id=\"id_".$polizas[$i]."\">".$items['sNumeroPoliza']."</td>". 
                                     "<td>".$items['sDescripcion']."</td>".  
                                     "<td>".$items['dFechaInicio']."</td>".
                                     "<td>".$items['dFechaCaducidad']."</td></tr>";   
                    }  
                }    
            }else{$htmlTabla ="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";} 
            
        }else{$error = "1";$mensaje = "The driver data were not found, please try again.";}
        
        if($htmlTabla == ""){$htmlTabla ="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";}
        
        $response = array("mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
        echo json_encode($response);
  }
  function get_driver_data(){
      $error = '0';
      $mensaje = "";
      $fields = "";
      $clave = trim($_POST['clave']);
      $company = $_SESSION['company'];
      $domroot = $_POST['domroot'];
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);  
      
      $query = "SELECT iConsecutivo, sNombre,DATE_FORMAT(dFechaNacimiento,'%m/%d/%Y') AS dFechaNacimiento, DATE_FORMAT(dFechaExpiracionLicencia,'%m/%d/%Y') AS dFechaExpiracionLicencia, ".
               "iExperienciaYear,iNumLicencia,dFechaContratacion, iEntidad,eTipoLicencia FROM ct_operadores WHERE iConsecutivo = '$clave' AND iConsecutivoCompania = '$company'";
      $result = $conexion->query($query);
      $items  = $result->num_rows;
      if($items > 0){
            $data = $result->fetch_assoc();
            $llaves  = array_keys($data);
            $datos   = $data;
            foreach($datos as $i => $b){ $fields .= "\$('#$domroot :input[id=".$i."]').val('".$datos[$i]."');";}
                 
                 //Get files from driver:
                 /*$sql_driver_files = "SELECT iConsecutivo, sNombreArchivo, eArchivo FROM cb_operador_files 
                                      WHERE  iConsecutivoOperador = '".$data['iConsecutivoOperador']."'";
                 $result_files = $conexion->query($sql_driver_files);
                 while ($items_files = $result_files->fetch_assoc()) {
                         if($items_files["eArchivo"] == 'LICENSE'){
                            $fields .= "\$('#$domroot :input[id=txtsLicenciaPDF]').val('".$items_files['sNombreArchivo']."');";
                            $fields .= "\$('#$domroot :input[id=iConsecutivoLicenciaPDF]').val('".$items_files['iConsecutivo']."');";  
                         }
                         if($items_files["eArchivo"] == 'MVR'){
                            $fields .= "\$('#$domroot :input[id=txtsMVRPDF]').val('".$items_files['sNombreArchivo']."');";
                            $fields .= "\$('#$domroot :input[id=iConsecutivoMVRPDF]').val('".$items_files['iConsecutivo']."');"; 
                         }
                         if($items_files["eArchivo"] == 'LONGTERM'){
                            $fields .= "\$('#$domroot :input[id=txtsLTMPDF]').val('".$items_files['sNombreArchivo']."');";
                            $fields .= "\$('#$domroot :input[id=iConsecutivoLTMPDF]').val('".$items_files['iConsecutivo']."');"; 
                         }
                         if($items_files["eArchivo"] == 'PSP'){
                            $fields .= "\$('#$domroot :input[id=txtsPSPFile]').val('".$items_files['sNombreArchivo']."');";
                            $fields .= "\$('#$domroot :input[id=iConsecutivoPSPFile]').val('".$items_files['iConsecutivo']."');"; 
                         }
                 } */
      }
      $conexion->rollback();
      $conexion->close(); 
      $response = array("mensaje"=>"$mensaje","error"=>"$error","fields"=>"$fields",);   
      echo json_encode($response);                                                                                                               
  }
  
  
?>
