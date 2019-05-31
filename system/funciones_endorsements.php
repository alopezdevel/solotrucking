<?php
  session_start();
  header('content-type: text/html; charset: UTF-8');  
  
  // Generic functions lib 
  include("functiones_genericas.php"); 
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId 
  
  //funcion para solo-trucking users:
  function definir_compania(){
       $mensaje = "";
       $error   = "0";
       if($_POST['iConsecutivoCompania'] != ""){
          $_SESSION["company"] = $_POST['iConsecutivoCompania'];  
       }else{
          $error = "1";
          $_SESSION["company"] = ""; 
       }
       $response = array("mensaje"=>"$mensaje","error"=>"$error");   
       echo json_encode($response);
  }
  
  //Funciones Generales.
  function get_endorsements(){
        include("cn_usuarios.php");
        $company = $_SESSION['company'];
        $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
        $transaccion_exitosa = true;
        $registros_por_pagina = $_POST["registros_por_pagina"];
        $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
        $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
            
        //Filtros de informacion //
        $filtroQuery = " WHERE A.iConsecutivoCompania = '".$company."'";
        $array_filtros = explode(",",$_POST["filtroInformacion"]);
        foreach($array_filtros as $key => $valor){
            if($array_filtros[$key] != ""){
                $campo_valor = explode("|",$array_filtros[$key]);
                $campo_valor[0] == 'iConsecutivo' ? $filtroQuery.= " AND  ".$campo_valor[0]."='".$campo_valor[1]."' " : $filtroQuery == "" ? $filtroQuery.= " AND  ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'": $filtroQuery.= " AND ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'";
            }
        }
        // ordenamiento//
        $ordenQuery = " ORDER BY dFechaAplicacion = '', ".$_POST["ordenInformacion"]." ".$_POST["sortInformacion"];

        //contando registros // 
        $query_rows = "SELECT COUNT(A.iConsecutivo) AS total FROM  cb_endoso A ". 
                      "LEFT JOIN ct_operadores C ON A.iConsecutivoOperador = C.iConsecutivo ".
                      "LEFT JOIN ct_unidades D ON A.iConsecutivoUnidad = D.iConsecutivo ".$filtroQuery;
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
          $sql = "SELECT A.iConsecutivo,iConsecutivoTipoEndoso AS Tipo,iConsecutivoOperador,iConsecutivoUnidad, DATE_FORMAT(A.dFechaAplicacion,'%m/%d/%Y') AS dFechaAplicacion, A.iConsecutivoTipoEndoso AS categoria, A.eStatus, eAccion, sNombre, sVIN, sNombreOperador, sVINUnidad, iEndosoMultiple ". 
                 "FROM cb_endoso A ".
                 "LEFT JOIN ct_operadores  C ON A.iConsecutivoOperador = C.iConsecutivo ".
                 "LEFT JOIN ct_unidades    D ON A.iConsecutivoUnidad = D.iConsecutivo ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
          $result = $conexion->query($sql);
          $rows   = $result->num_rows; 
             
            if ($rows > 0) {    
                while ($items = $result->fetch_assoc()) { 
                    
                     $btn_confirm = "";
                     $class       = "";
                     $descripcion = ""; 
                     $estado      = ''; 
                     $action      = "";
                     $detalle     = "";
                     $titleEstatus= "";
                     $policies    = "";
                     
                     #DRIVERS
                     if($items['categoria'] == '2'){  
                         
                         $tipo = "DRIVER";
                         
                         //Revisar si el endoso es multiple:
                         if($items['iEndosoMultiple'] == 1){ 
                             #CONSULTAR DETALLE DEL ENDOSO:
                             $query = "SELECT A.sNombre, (CASE 
                                        WHEN A.eAccion = 'ADDSWAP'    THEN 'ADD SWAP'
                                        WHEN A.eAccion = 'DELETESWAP' THEN 'DELETE SWAP'
                                        ELSE A.eAccion
                                        END) AS eAccion FROM cb_endoso_operador AS A WHERE A.iConsecutivoEndoso = '".$items['iConsecutivo']."' ORDER BY sNombre ASC";
                             $r     = $conexion->query($query);
                             $descripcion  = "<table style=\"width:100%;padding:0!important;margin:0!important;\">";
                             while($item = $r->fetch_assoc()){
                                $detalle .= "<tr style='background: none;'>".
                                "<td style='border: 0;width:110px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['eAccion']."</td>".
                                "<td style='border: 0;width:100px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$tipo."</td>".
                                "<td style='border: 0;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['sNombre']."</td>".
                                "</tr>"; 
                             } 
                             $descripcion .= $detalle."</table>";  
                         }
                         else{
                            
                            $items["eAccion"] == "A" ?  $action = 'ADD' : $action = 'DELETE';
                            $items['iConsecutivoOperador'] != "" ? $sNombreOperador = utf8_decode(trim($items['sNombre'])) : $sNombreOperador = utf8_decode(trim($items['sNombreOperador']));  
                            
                            $descripcion  = "<table style=\"width:100%;padding:0!important;margin:0!important;border-collapse: collapse;\">";
                            $descripcion .= "<tr style='background: none;'>";
                            $descripcion .= "<td style='border: 0;width:110px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$action."</td>";
                            $descripcion .= "<td style='border: 0;width:100px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$tipo."</td>";
                            $descripcion .= "<td style='border: 0;padding: 0!important;min-height: auto!important;height:auto!important;'>".$sNombreOperador."</td>";
                            $descripcion .= "</tr></table>"; 
                            
                         }
                         $class_unit  = "DRIVER";
                     }
                     #VEHICLES
                     else if($items['categoria'] == '1'){
                         
                         $tipo = "VEHICLE";
                         
                         //Revisar si el endoso es multiple:
                         if($items['iEndosoMultiple'] == 1){
                             #CONSULTAR DETALLE DEL ENDOSO:
                             $query = "SELECT A.sVIN, (CASE 
                                        WHEN A.eAccion = 'ADDSWAP'    THEN 'ADD SWAP'
                                        WHEN A.eAccion = 'DELETESWAP' THEN 'DELETE SWAP'
                                        ELSE A.eAccion
                                        END) AS eAccion FROM cb_endoso_unidad AS A WHERE A.iConsecutivoEndoso = '".$items['iConsecutivo']."' ORDER BY sVIN ASC";
                             $r     = $conexion->query($query);
                             $descripcion  = "<table style=\"width:100%;padding:0!important;margin:0!important;\">";
                             
                             while($item = $r->fetch_assoc()){
                                 //$detalle == "" ? $detalle = $item['eAccion'].' '.$descripcion.'  VIN# '.$item['sVIN'] : $detalle.= "<br>".$item['eAccion'].' '.$descripcion.'  VIN# '.$item['sVIN'];
                             
                                 $detalle .= "<tr style='background: none;'>".
                                 "<td style='border: 0;width:110px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['eAccion']."</td>".
                                 "<td style='border: 0;width:100px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$tipo."</td>".
                                 "<td style='border: 0;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['sVIN']."</td>".
                                 "</tr>"; 
                             }
          
                             $descripcion .= $detalle."</table>";
                             
                         }
                         else{
                            $items["eAccion"] == "A" ?  $action = 'ADD' : $action = 'DELETE'; 
                            $items['iConsecutivoUnidad'] != "" ? $sVINUnidad = trim($items['sVIN']) : $sVINUnidad = trim($items['sVINUnidad']);
                            $descripcion  = "<table style=\"width:100%;padding:0!important;margin:0!important;border-collapse: collapse;\">";
                            $descripcion .= "<tr style='background: none;'>";
                            $descripcion .= "<td style='border: 0;width:110px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$action."</td>";
                            $descripcion .= "<td style='border: 0;width:100px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$tipo."</td>";
                            $descripcion .= "<td style='border: 0;padding: 0!important;min-height: auto!important;height:auto!important;'>".$sVINUnidad."</td>";
                            $descripcion .= "</tr></table>"; 
                               
                         }
          
                         $class_unit = 'UNIT';
                     }
                     
                     switch($items["eStatus"]){
                         case 'E': 

                            $estado      = '<i class="fa fa-circle-o icon-estatus " aria-hidden=\"true\"></i><span style="font-size: 10px;">UNSENT</span>';
                            $titleEstatus= "The data can be edited only by you.";
                            $btn_confirm = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"Edit Endorsement\"><i class=\"fa fa-pencil-square-o\"></i> <span></span>".
                                           "</div><div class=\"btn_send_email_brokers btn-icon send-email btn-left\" title=\"Send endorsement to Solo-Trucking Insurance\"><i class=\"fa fa-envelope\"></i><span></span></div>".
                                           "<div class=\"btn_delete btn-icon trash btn-left\" title=\"Delete Endorsement\"><i class=\"fa fa-trash\"></i> <span></span></div>";  
                         break;
                         case 'S': 
                            $estado      = '<i class="fa fa-circle-o icon-estatus " aria-hidden=\"true\"></i><span style="font-size: 10px;">SENT</span>';
                            $titleEstatus= "The data can be edited only by the employees of Solo-Trucking Insurance.";
                            $class       = "class = \"blue\""; 
                            //$btn_confirm = "<div class=\"btn_view btn-icon view btn-left\" title=\"View Endorsement Sent\"><i class=\"fa fa-eye\"></i> <span></span></div>".
                            $btn_confirm = "<div class=\"btn_delete btn-icon trash btn-left\" title=\"Delete Endorsement\"><i class=\"fa fa-trash\"></i><span></span></div>";  
                         break;
                         case 'SB': 
                            $estado      = '<i class="fa fa-share-square-o status-process icon-estatus " aria-hidden=\"true\"></i><span style="font-size: 10px;">SENT TO BROKERS</span>';
                            $titleEstatus= "Your endorsement has been sent to the brokers by Solo-Trucking Insurance.";
                            $class  = "class = \"yellow\""; 
                            //$btn_confirm ="<div class=\"btn_view btn-icon view btn-left\" title=\"View Endorsement Sent\"><i class=\"fa fa-eye\"></i> <span></span></div>"; 
                         break;
                         case 'A': 
                            $estado      = '<i class="fa fa-check-circle status-success icon-estatus " aria-hidden=\"true\"></i><span style="font-size: 10px;">APPROVED</span>';
                            $titleEstatus= "Your endorsement has been approved successfully."; 
                            $class       = "class = \"green\"";
                            //$btn_confirm ="<div class=\"btn_view btn-icon view btn-left\" title=\"View Endorsement Sent\"><i class=\"fa fa-eye\"></i> <span></span></div>"; 
                            break;
                         case 'D': 
                            $estado      = '<i class="fa fa-times status-error icon-estatus " aria-hidden=\"true\"></i><span style="font-size: 10px;">CANCELED</span>';
                            $titleEstatus= "Your endorsement has been canceled, please see the reasons on the comments."; 
                            $class       = "class = \"red\"";
                            $btn_confirm = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"Edit Endorsement\"><i class=\"fa fa-pencil-square-o\"></i> <span></span>".
                                           "</div><div class=\"btn_resend_email btn-icon send-email btn-left\" title=\"Resend endorsement to Solo-Trucking Insurance\"><i class=\"fa fa-envelope\"></i><span></span></div>";    
                         break;
                         case 'P': 
                            $estado      = '<i class="fa fa-refresh status-process icon-estatus " aria-hidden=\"true\"></i><span style="font-size: 10px;">IN PROCESS</span>';
                            $titleEstatus= "Your endorsement is being in process by the brokers.";
                            $class       = "class = \"orange\"";
                            //$btn_confirm ="<div class=\"btn_view btn-icon view btn-left\" title=\"View Endorsement Sent\"><i class=\"fa fa-eye\"></i> <span></span></div>";     
                         break;
                     }
                     
                     #POLIZAS:
                     $query = "SELECT A.iConsecutivoPoliza,P.sNumeroPoliza, T.sDescripcion AS sTipoPoliza, B.sName AS sBrokerName ,A.eStatus, A.sNumeroEndosoBroker, A.rImporteEndosoBroker 
                                FROM cb_endoso_estatus AS A 
                                INNER JOIN ct_polizas  AS P ON A.iConsecutivoPoliza = P.iConsecutivo
                                LEFT  JOIN ct_tipo_poliza AS T ON P.iTipoPoliza = T.iConsecutivo
                                LEFT  JOIN     ct_brokers    AS B ON P.iConsecutivoBrokers = B.iConsecutivo
                                WHERE A.iConsecutivoEndoso = '".$items['iConsecutivo']."' 
                                ORDER BY iConsecutivoPoliza DESC";
                     $r     = $conexion->query($query);
                     
                     if($r->num_rows > 0){
                         $policies = "<table style=\"width:100%;padding:0!important;margin:0!important;border-collapse: collapse;border-spacing: 0;\">";
                         while($item = $r->fetch_assoc()){
                             
                            $item['sNumeroEndosoBroker']  != "" ? $item['sNumeroEndosoBroker'] = "END# ".$item['sNumeroEndosoBroker'] : ""; 
                            $item['rImporteEndosoBroker'] != "" && $item['rImporteEndosoBroker'] != 0 ? $item['rImporteEndosoBroker'] = "\$ ".number_format($item['rImporteEndosoBroker'],2,'.',',') : $item['rImporteEndosoBroker'] = "";
                            
                            $policies .= "<tr style='background: none;' title='".$item['sTipoPoliza']."/ ".$item['sBrokerName']."'>";
                            $policies .= "<td style='border: 0;width:40%;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['sNumeroPoliza']."</td>"; 
                            $policies .= "<td style='border: 0;width:30%;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['sNumeroEndosoBroker']."</td>";
                            $policies .= "</tr>"; 
                         }
                         $policies.="</table>";
                     }
                     

                     $htmlTabla .= "<tr $class>".
                                   "<td id=\"Cve_".$items['iConsecutivo']."\" class=\"$class_unit\">".$descripcion."</td>".
                                   "<td>".$policies."</td>".
                                   "<td class=\"txt-c\">".$items['dFechaAplicacion']."</td>". 
                                   "<td title='$titleEstatus'>".$estado."</td>".                                                                                                                                                                                                                       
                                   "<td> $btn_confirm</td>".
                                   "</tr>";
                        
                }
                $conexion->rollback();
                $conexion->close();                                                                                                                                                                       
            } 
            else { $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";}
          }
          $response = array("total"=>"$paginas_total","pagina"=>"$pagina_actual","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
          echo json_encode($response); 
  }
  function validate_policies(){
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE); 
      $company             = $_SESSION['company'];                                                                                                                                                                                                                                     
      $transaccion_exitosa = true;
      $error               = '0';
      $Valida_PD           = 0;
      //Consultar Datos de las polizas:
      $sql = "SELECT sNumeroPoliza, C.sName AS BrokerName, E.sName AS InsuranceName, sDescripcion, D.iConsecutivo AS TipoPoliza, D.sAlias AS sAliasPoliza ".
             "FROM ct_polizas A ".
             "LEFT JOIN ct_brokers     C ON A.iConsecutivoBrokers = C.iConsecutivo ".
             "LEFT JOIN ct_tipo_poliza D ON A.iTipoPoliza = D.iConsecutivo ".
             "LEFT JOIN ct_aseguranzas E ON A.iConsecutivoAseguranza = E.iConsecutivo ".
             "WHERE iConsecutivoCompania = '".$company."' ".
             "AND  A.iDeleted = '0' AND dFechaCaducidad >= CURDATE() AND (D.iConsecutivo = '1' OR D.iConsecutivo = '3' OR D.iConsecutivo = '5' OR D.iConsecutivo = '2') ".
             "ORDER BY sNumeroPoliza ASC";  
      $result = $conexion->query($sql);
      $rows = $result->num_rows;
      
      if($rows > 0) {   
            while ($items = $result->fetch_assoc()) { 
               
               $htmlTabla .= "<tr><td style=\"border: 1px solid #dedede;\">".$items['sNumeroPoliza']."</td>".
                             "<td style=\"border: 1px solid #dedede;\">".$items['BrokerName']."</td>". 
                             "<td style=\"border: 1px solid #dedede;\">".$items['InsuranceName']."</td>". 
                             "<td style=\"border: 1px solid #dedede;\">".$items['sDescripcion']."</td></tr>";
               
               if($items['sAliasPoliza'] == 'PD'){$Valida_PD = 1;}
                    
            }                                                                                                                                                                       
      }
      else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";}
      
      $conexion->rollback();
      $conexion->close();
      $response = array("mensaje"=>"$mensaje","error"=>"$error","policies_information"=>"$htmlTabla","valida_pd"=>"$Valida_PD");   
      echo json_encode($response);
      
  }
  /*function save_endorsement(){
      $error = '0';  
      $msj = "";  
      //Conexion:
      include("cn_usuarios.php"); 
      $company = $_SESSION['company']; 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      //arrays para campos de endoso:
      $valores_endoso = array();
      $campos_endoso  = array();
      
      //DRIVERS -- Formatear Fechas:
      if($_POST['iConsecutivoTipoEndoso_endoso'] == '2'){ 
          $_POST['dFechaNacimiento_operador'] = format_date($_POST['dFechaNacimiento_operador']); 
          $_POST['dFechaExpiracionLicencia_operador'] = format_date($_POST['dFechaExpiracionLicencia_operador']);
          $_POST['sNombre_operador'] != '' ? $_POST['sNombre_operador'] = strtoupper($_POST['sNombre_operador']) : '';
           
      } 
      
      
      if($_POST["edit_mode"] == 'true'){
      ##UPDATE DATA
           #DATOS GENERALES ENDOSO:
           foreach($_POST as $campo => $valor){
                if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivo_endoso" and $campo != "txtsLicenciaPDF" and $campo != "iConsecutivoLicenciaPDF" and $campo != "txtsMVRPDF" and $campo != "iConsecutivoMVRPDF" and $campo != "txtsLTMPDF" and $campo != "iConsecutivoLTMPDF"){ //Estos campos no se insertan a la tabla
                    if(strpos($campo, "_endoso")){
                        array_push($valores_endoso,str_replace("_endoso","",$campo)."='".trim($valor)."'"); 
                    }
                }
           }
           
           #ENDOSOS PARA DRIVERS:
           if($_POST['iConsecutivoTipoEndoso_endoso'] == '2'){
              $valores_operador = array(); 
              //Traer operador ID:
              $sql = "SELECT iConsecutivoOperador FROM cb_endoso WHERE iConsecutivo = '".$_POST['iConsecutivo_endoso']."'";
              $result = $conexion->query($sql);
              $rows = $result->num_rows;
              
              if($rows > 0){
                 $driver = $result->fetch_assoc(); 
                 foreach($_POST as $campo => $valor){
                    if(strpos($campo,"_operador") && $valor != ''){
                        array_push($valores_operador,str_replace("_operador","",$campo)."='".trim($valor)."'");
                    }
                  }
                  //actualiza operador:
                  array_push($valores_operador ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
                  array_push($valores_operador ,"sIP='".$_SERVER['REMOTE_ADDR']."'");
                  array_push($valores_operador ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'"); 
                  $sql_driver = "UPDATE ct_operadores SET ".implode(",",$valores_operador)." WHERE iConsecutivo = '".$driver['iConsecutivoOperador']."'";
                  $conexion->query($sql_driver);
                  
                  if($conexion->affected_rows < 1){
                       $transaccion_exitosa = false;
                       $msj = "The data of driver was not saved properly, please try again.";
                       $error = '1';
                  }
                  #VERIFICAR ARCHIVOS:
                  if($_POST['iConsecutivoLicenciaPDF'] != ''){
                       $sql_license = "UPDATE cb_operador_files SET iConsecutivoOperador ='".trim($driver['iConsecutivoOperador'])."' WHERE iConsecutivo = '".$_POST['iConsecutivoLicenciaPDF']."'";
                       $conexion->query($sql_license);
                  }
                  if($_POST['iConsecutivoMVRPDF'] != ''){
                       $sql_mvr = "UPDATE cb_operador_files SET iConsecutivoOperador ='".trim($driver['iConsecutivoOperador'])."' WHERE iConsecutivo = '".$_POST['iConsecutivoMVRPDF']."'";
                       $conexion->query($sql_mvr);
                  }
                  if($_POST['iConsecutivoLTMPDF'] != ''){
                       $sql_mvr = "UPDATE cb_operador_files SET iConsecutivoOperador ='".trim($driver['iConsecutivoOperador'])."' WHERE iConsecutivo = '".$_POST['iConsecutivoLTMPDF']."'";
                       $conexion->query($sql_mvr);
                  }
                  if($_POST['iConsecutivoPSPFile'] != ''){
                       $sql_mvr = "UPDATE cb_operador_files SET iConsecutivoOperador ='".trim($driver['iConsecutivoOperador'])."' WHERE iConsecutivo = '".$_POST['iConsecutivoPSPFile']."'";
                       $conexion->query($sql_mvr);
                  }
                     
              }else{
                  $transaccion_exitosa = false;
                  $msj = "The data of driver was not be found properly, please try again.";
                  $error = '1';
              }
           }
           
           #ENDOSOS PARA UNIDADES:
           if($_POST['iConsecutivoTipoEndoso_endoso'] == '1'){
              $valores_unit = array(); 
              //Get Unidad ID:
              $sql = "SELECT iConsecutivoUnidad FROM cb_endoso WHERE iConsecutivo = '".$_POST['iConsecutivo_endoso']."'";
              $result = $conexion->query($sql);
              $rows = $result->num_rows;
              
              if($rows > 0){
                 $unidad = $result->fetch_assoc(); 
                 foreach($_POST as $campo => $valor){  
                    if(strpos($campo,"_unit") && $valor != ''){ //Solo inserta los campos con _unit
                        array_push($valores_unit,str_replace("_unit","",$campo)."='".trim($valor)."'");
                    }      
                  }
                  //actualiza operador:
                  array_push($valores_unit ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
                  array_push($valores_unit ,"sIP='".$_SERVER['REMOTE_ADDR']."'");
                  array_push($valores_unit ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'"); 
                  $sql_unidad = "UPDATE ct_unidades SET ".implode(",",$valores_unit)." WHERE iConsecutivo = '".$unidad['iConsecutivoUnidad']."'";
                  $conexion->query($sql_unidad);
                  
                  if($conexion->affected_rows < 1){
                       $transaccion_exitosa = false;
                       $msj = "The unit data was not saved properly, please try again.";
                       $error = '1';
                  }
                  
                  #VERIFICAR SI SE AGREGARON ARCHIVOS NUEVOS:
                  if($_POST['iConsecutivoTituloPDF'] != ''){ //Titulo
                       $sql_license = "UPDATE cb_unidad_files SET iConsecutivoUnidad ='".trim($unidad['iConsecutivoUnidad'])."' WHERE iConsecutivo = '".$_POST['iConsecutivoTituloPDF']."'";
                       $conexion->query($sql_license);
                  }
                  if($_POST['iConsecutivoDAPDF'] != ''){ //Delease Agreement
                       $sql_mvr = "UPDATE cb_unidad_files SET iConsecutivoUnidad ='".trim($unidad['iConsecutivoUnidad'])."' WHERE iConsecutivo = '".$_POST['iConsecutivoDAPDF']."'";
                       $conexion->query($sql_mvr);
                  }   
                  
                     
              }else{
                  $transaccion_exitosa = false;
                  $msj = "The unit data was not be found properly, please try again.";
                  $error = '1';
              }
           }
           
           if($error =='0'){
              
              #ACTUALIZA ENDOSO DATOS: 
               array_push($valores_endoso ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
               array_push($valores_endoso ,"sIP='".$_SERVER['REMOTE_ADDR']."'");
               array_push($valores_endoso ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'"); 
               $sql_endoso = "UPDATE cb_endoso SET ".implode(",",$valores_endoso)." WHERE iConsecutivo = '".$_POST['iConsecutivo_endoso']."'"; 
               $conexion->query($sql_endoso);
               if($conexion->affected_rows < 1){
                    $transaccion_exitosa = false;
                    $msj = "The endorsement data was not updated properly, please try again.";
                    $error = '1';
               }else{
                   $msj = "The data was updated successfully."; 
               } 
           }
            
      }else{ ##NEW DATA
         #DRIVERS
         if($_POST['iConsecutivoTipoEndoso_endoso'] == '2'){ 
            #PARA ENDOSOS - DRIVER:
            //1- Formar arrays para endoso y driver
            $campos_operador = array();
            $valores_operador = array();
            
            foreach($_POST as $campo => $valor){
                if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivo_endoso" and $campo != "txtsLicenciaPDF" and $campo != "iConsecutivoLicenciaPDF" and $campo != "txtsMVRPDF" and $campo != "iConsecutivoMVRPDF" and $campo != "txtsLTMPDF" and $campo != "iConsecutivoLTMPDF"){ //Estos campos no se insertan a la tabla
                    if(strpos($campo, "_endoso")){
                        array_push($campos_endoso ,str_replace("_endoso","",$campo)); 
                        array_push($valores_endoso, strtoupper(trim($valor)));
                        
                    }else if(strpos($campo,"_operador") && $valor != ''){
                        array_push($campos_operador ,str_replace("_operador","",$campo)); 
                        array_push($valores_operador, strtoupper(trim($valor)));
                    }
                }
            }
           //insertar operador:
           array_push($campos_operador ,"iConsecutivoCompania");
           array_push($valores_operador ,$company);
           array_push($campos_operador ,"dFechaIngreso");
           array_push($valores_operador ,date("Y-m-d H:i:s"));
           array_push($campos_operador ,"sIP");
           array_push($valores_operador ,$_SERVER['REMOTE_ADDR']);
           array_push($campos_operador ,"sUsuarioIngreso");
           array_push($valores_operador ,$_SESSION['usuario_actual']);
           
           $sql_driver = "INSERT INTO ct_operadores (".implode(",",$campos_operador).") VALUES ('".implode("','",$valores_operador)."')"; 
           $conexion->query($sql_driver);
           if($conexion->affected_rows < 1){
               $transaccion_exitosa = false;
               $msj = "The driver data was not saved properly, please try again.";
               $error = '1';
           }else{
               
               $driver_id = $conexion->insert_id;
               if($driver_id != ''){
                 //actualizar tabla de archivos:  
                 if($_POST['iConsecutivoLicenciaPDF'] != ''){
                   $sql_license = "UPDATE cb_operador_files SET iConsecutivoOperador ='$driver_id' WHERE iConsecutivo = '".$_POST['iConsecutivoLicenciaPDF']."'";
                   $conexion->query($sql_license);
                 }
                 if($_POST['iConsecutivoMVRPDF'] != ''){
                       $sql_mvr = "UPDATE cb_operador_files SET iConsecutivoOperador ='$driver_id' WHERE iConsecutivo = '".$_POST['iConsecutivoMVRPDF']."'";
                       $conexion->query($sql_mvr);
                 }
                 if($_POST['iConsecutivoLTMPDF'] != ''){
                       $sql_mvr = "UPDATE cb_operador_files SET iConsecutivoOperador ='$driver_id' WHERE iConsecutivo = '".$_POST['iConsecutivoLTMPDF']."'";
                       $conexion->query($sql_mvr);
                 }
                  
               }
               
               //Insertar driver al endoso:
               array_push($campos_endoso ,"iConsecutivoOperador");
               array_push($valores_endoso ,$driver_id);
               
           } 
            
         } 
         #UNITS
         if($_POST['iConsecutivoTipoEndoso_endoso'] == '1'){
            $campos_unit = array();
            $valores_unit = array(); 
            foreach($_POST as $campo => $valor){
                if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivo_endoso" and $campo != "txtsTituloPDF" and $campo != "iConsecutivoTituloPDF" and $campo != "txtsDAPDF" and $campo != "iConsecutivoDAPDF"){ //Estos campos no se insertan a la tabla
                    if(strpos($campo, "_endoso")){
                        array_push($campos_endoso ,str_replace("_endoso","",$campo)); 
                        array_push($valores_endoso, strtoupper(trim($valor)));
                        
                    }else if(strpos($campo,"_unit") && $valor != ''){
                        array_push($campos_unit ,str_replace("_unit","",$campo)); 
                        array_push($valores_unit, strtoupper(trim($valor)));
                    }
                }
            }
            
            //insertar operador:
            array_push($campos_unit ,"iConsecutivoCompania");
            array_push($valores_unit ,$company);
            array_push($campos_unit ,"dFechaIngreso");
            array_push($valores_unit ,date("Y-m-d H:i:s"));
            array_push($campos_unit ,"sIP");
            array_push($valores_unit ,$_SERVER['REMOTE_ADDR']);
            array_push($campos_unit ,"sUsuarioIngreso");
            array_push($valores_unit ,$_SESSION['usuario_actual']);
            
            $sql_unit = "INSERT INTO ct_unidades (".implode(",",$campos_unit).") VALUES ('".implode("','",$valores_unit)."')";
            $conexion->query($sql_unit);
            
            if($conexion->affected_rows < 1){ $transaccion_exitosa = false; $msj = "The unit data was not saved properly, please try again.";  $error = '1';
            }else{
                $unit_id = $conexion->insert_id; //<--- UNIT ID   
                if($unit_id != ''){
                     //actualizar tabla de archivos:  
                     if($_POST['iConsecutivoTituloPDF'] != ''){ //Titulo
                       $sql_license = "UPDATE cb_unidad_files SET iConsecutivoUnidad ='$unit_id' WHERE iConsecutivo = '".$_POST['iConsecutivoTituloPDF']."'";
                       $conexion->query($sql_license);
                     }
                     if($_POST['iConsecutivoDAPDF'] != ''){ //Delease Agreement
                           $sql_mvr = "UPDATE cb_unidad_files SET iConsecutivoUnidad ='$unit_id' WHERE iConsecutivo = '".$_POST['iConsecutivoDAPDF']."'";
                           $conexion->query($sql_mvr);
                     }   
               } 
               //Insertar unidad al endoso:
               array_push($campos_endoso ,"iConsecutivoUnidad");
               array_push($valores_endoso ,$unit_id);     
            }
            
            
         }
         
         #INTERTAR DATOS GENERALES ENDOSO:
         if($error == '0'){
             array_push($campos_endoso ,"iConsecutivoCompania");
             array_push($valores_endoso ,$company);
             array_push($campos_endoso ,"eStatus");
             array_push($valores_endoso ,'E');
             array_push($campos_endoso ,"dFechaIngreso");
             array_push($valores_endoso ,date("Y-m-d H:i:s"));
             array_push($campos_endoso ,"sIP");
             array_push($valores_endoso ,$_SERVER['REMOTE_ADDR']);
             array_push($campos_endoso ,"sUsuarioIngreso");
             array_push($valores_endoso ,$_SESSION['usuario_actual']);
                    
             $sql_endoso = "INSERT INTO cb_endoso (".implode(",",$campos_endoso).") VALUES ('".implode("','",$valores_endoso)."')"; 
             $conexion->query($sql_endoso); 
             if($conexion->affected_rows < 1){
                $transaccion_exitosa = false;
                $msj = "The data of endorsement was not saved properly, please try again."; 
             }else{
                $msj = "The data was saved successfully."; 
             }
         }
      
      }
      
      if($transaccion_exitosa){
            $conexion->commit();
            $conexion->close();
      }else{
            $conexion->rollback();
            $conexion->close();
            $error = "1";
      }
      $response = array("error"=>"$error","msj"=>"$msj");
      echo json_encode($response);
      
      
  }*/
  function guarda_pdf_driver(){
      $error = "0";
      include("cn_usuarios.php");
      $company = $_SESSION['company'];
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $oFichero = fopen($_FILES['userfile']["tmp_name"], 'r'); 
      $sContenido = fread($oFichero, filesize($_FILES['userfile']["tmp_name"]));  
      $sContenido =  $conexion->real_escape_string($sContenido);
      $eArchivo = $_POST['eArchivo'];
       
      //Revisamos el tamaño del archivo:
      if($_FILES['userfile']["size"] <= 1000000){
          
          $FileExt = explode('.',$_FILES['userfile']["name"]); 
          $countArray = count($FileExt);
          if($countArray == 2){
             $name_file = strtolower($_POST['eArchivo']).'.'.$FileExt[1]; 
          
             if($_POST['iConsecutivo'] != ''){
                 $sql = "UPDATE cb_operador_files SET sNombreArchivo ='".$name_file."', sTipoArchivo ='".$_FILES['userfile']["type"]."', iTamanioArchivo ='".$_FILES['userfile']["size"]."', hContenidoDocumentoDigitalizado='$sContenido', eArchivo='$eArchivo', ".
                        "dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."', sUsuarioActualizacion='".$_SESSION['usuario_actual']."'".
                        "WHERE iConsecutivo ='".$_POST['iConsecutivo']."'"; 
             }else{
                 $sql = "INSERT INTO cb_operador_files (sNombreArchivo, sTipoArchivo, iTamanioArchivo, hContenidoDocumentoDigitalizado, eArchivo, dFechaIngreso, sIP, sUsuarioIngreso) ".
                        "VALUES('".$name_file."','".$_FILES['userfile']["type"]."','".$_FILES['userfile']["size"]."','$sContenido','$eArchivo','".date("Y-m-d H:i:s")."', '".$_SERVER['REMOTE_ADDR']."', '".$_SESSION['usuario_actual']."')"; 
             }
      
              if($conexion->query($sql)){
                    $id_file = $conexion->insert_id; 
                    $conexion->commit();
                    $conexion->close();
                    $mensaje = "The file was uploaded successfully.";  
              }else{
                    $conexion->rollback();
                    $conexion->close();
                    $mensaje = "A general system error ocurred : internal error";
                    $error = "1";
              }     
          }else{
              $mensaje = "Error: Please verify that the file name does not contain any special characters..";
              $error = "1";
          }
          
      }else{             
         $mensaje = "Error: The file you are trying to upload exceeds the maximum size (1MB) allowed by the system, please check it and try again.";
         $error = "1"; 
      }
      
      $response = array("mensaje"=>"$mensaje","error"=>"$error", "id_file"=>"$id_file", "name_file" => "$name_file"); 
      echo json_encode($response);             
      
  }
  function guarda_pdf_unit(){
      $error = "0";
      include("cn_usuarios.php");
      $company = $_SESSION['company'];
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $oFichero = fopen($_FILES['userfile']["tmp_name"], 'r'); 
      $sContenido = fread($oFichero, filesize($_FILES['userfile']["tmp_name"]));  
      $sContenido =  $conexion->real_escape_string($sContenido);
      $eArchivo = $_POST['eArchivo'];
      
      //Revisamos el tamaño del archivo:
      if($_FILES['userfile']["size"] <= 1000000){
          $FileExt = explode('.',$_FILES['userfile']["name"]);  
          $name_file = strtolower($_POST['eArchivo']).'.'.$FileExt[1];
          
          if($_POST['iConsecutivo'] != ''){
             $sql = "UPDATE cb_unidad_files SET sNombreArchivo ='".$name_file."', sTipoArchivo ='".$_FILES['userfile']["type"]."', iTamanioArchivo ='".$_FILES['userfile']["size"]."', hContenidoDocumentoDigitalizado='$sContenido', eArchivo='$eArchivo', ".
                    "dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."', sUsuarioActualizacion='".$_SESSION['usuario_actual']."'".
                    "WHERE iConsecutivo ='".$_POST['iConsecutivo']."'"; 
          }else{
             $sql = "INSERT INTO cb_unidad_files (sNombreArchivo, sTipoArchivo, iTamanioArchivo, hContenidoDocumentoDigitalizado, eArchivo, dFechaIngreso, sIP, sUsuarioIngreso) ".
                    "VALUES('".$name_file."','".$_FILES['userfile']["type"]."','".$_FILES['userfile']["size"]."','$sContenido','$eArchivo','".date("Y-m-d H:i:s")."', '".$_SERVER['REMOTE_ADDR']."', '".$_SESSION['usuario_actual']."')"; 
          }
          
          if($conexion->query($sql)){
                $id_file = $conexion->insert_id; 
                $conexion->commit();
                $conexion->close();
                $mensaje = "The file was uploaded successfully.";  
          }else{
                $conexion->rollback();
                $conexion->close();
                $mensaje = "A general system error ocurred : internal error";
                $error = "1";
          }     
      }else{
          $mensaje = "Error: The file you are trying to upload exceeds the maximum size (1MB) allowed by the system, please check it and try again.";
          $error = "1"; 
      }
     
      $response = array("mensaje"=>"$mensaje","error"=>"$error", "id_file"=>"$id_file","name_file"=>"$name_file"); 
      echo json_encode($response);             
      
  }
  function delete_files(){
      
      $iConsecutivo = $_POST['iConsecutivoFile'];
      $error = '0';   
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      #borar archivos de drivers sin un id de driver asignado:
      $query_licen = "DELETE FROM cb_operador_files WHERE iConsecutivoOperador IS NULL OR iConsecutivoOperador = ''";
      $conexion->query($query_licen);
      //if($conexion->query($query_licen)){$transaccion_exitosa = false;}else{$transaccion_exitosa = true; } 
      
      if($transaccion_exitosa){
         #borrar archivos de unidades si un id de unidad asignado
         $query_mvr = "DELETE FROM cb_unidad_files WHERE iConsecutivoUnidad IS NULL OR iConsecutivoUnidad = ''"; 
         $conexion->query($query_mvr);
         //if($conexion->query($query_mvr)){$transaccion_exitosa = false;}else{$transaccion_exitosa = true;}    
      } 
      
      
      if($transaccion_exitosa){
        $conexion->commit();
        $conexion->close();
        $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>
                The files has been deleted succesfully!</p>';
      }else{
        $conexion->rollback();
        $conexion->close();
        $msj = "A general system error ocurred : internal error";
        $error = "1";
      }
        
      $response = array("msj"=>"$msj","error"=>"$error");   
      echo json_encode($response);
  }
  /*function get_endorsement(){  
      $error   = '0';
      $msj     = "";
      $fields  = "";
      $clave   = trim($_POST['clave']);
      $company = $_SESSION['company'];
      $domroot = $_POST['domroot'];
      
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                 
      $sql    = "SELECT iConsecutivo, iConsecutivoTipoEndoso, eStatus, iReeferYear,iTrailerExchange, iPDAmount, iPDApply, iConsecutivoOperador, iConsecutivoUnidad, eAccion, sComentarios, sNumPolizas ".  
                "FROM cb_endoso ". 
                "WHERE iConsecutivo = '".$clave."'";
      $result = $conexion->query($sql);
      $items  = $result->num_rows;
       
      if ($items > 0) {     
        $data = $result->fetch_assoc(); 
        $llaves  = array_keys($data);
        $datos   = $data;
        foreach($datos as $i => $b){ 
            if($i != 'eStatus' && $i != 'sComentarios' && $i != 'sNumPolizas'){
            $fields .= "\$('#$domroot :input[id=".$i."_endoso]').val('".$datos[$i]."');";
            }else if($i == 'sNumPolizas'){
               $PolizasEndoso = explode('|',$datos[$i]); 
               $tablaPolizas = "<table class=\"popup-datagrid\"><thead>".
                               "<tr id=\"grid-head2\">".
                               "<td class=\"etiqueta_grid\">Policy Number</td>".
                               "<td class=\"etiqueta_grid\">Policy Type</td>".
                               "</tr></thead><tbody>";
                
               for ($i = 0; $i < count($PolizasEndoso); $i++) {
                    $poliza = explode('/',$PolizasEndoso[$i]);
                    $filtro = "AND sNumeroPoliza = '".$poliza[0]."' AND iTipoPoliza = '".$poliza[1]."' "; 
                       $fields .= "\$('#policies_endorsement  :input[value=\"".$PolizasEndoso[$i]."\"]').prop(\"checked\",\"true\");";
                       $policy_query = "SELECT sNumeroPoliza, D.sDescripcion ".
                                       "FROM ct_polizas A LEFT JOIN ct_tipo_poliza D ON A.iTipoPoliza = D.iConsecutivo ".
                                       "WHERE iConsecutivoCompania = '".$company."' ".
                                       "$filtro AND A.iDeleted ='0'"; 

                       $result_policy = $conexion->query($policy_query);
                       $rows_policy = $result_policy->num_rows; 
                       if($rows_policy > 0){
                           while($policies = $result_policy->fetch_assoc()){
                              $tablaPolizas .= "<tr><td>".$policies['sNumeroPoliza']."</td>".
                                               "<td>".$policies['sDescripcion']."</td></tr>"; 
                           }
                           
                       }    
               }
               
               $tablaPolizas .= "</tbody></table>";
            } 
        }
        
        if($data['iConsecutivoOperador']!= '' && $data['iConsecutivoTipoEndoso']== '2'){
            $sql_driver = "SELECT iConsecutivo, sNombre,DATE_FORMAT(dFechaNacimiento,'%m/%d/%Y') AS dFechaNacimiento, DATE_FORMAT(dFechaExpiracionLicencia,'%m/%d/%Y') AS dFechaExpiracionLicencia,iExperienciaYear,iNumLicencia,dFechaContratacion,
                           iEntidad,eTipoLicencia FROM ct_operadores 
                           WHERE iConsecutivo = '".$data['iConsecutivoOperador']."'";
            $result = $conexion->query($sql_driver);
            $items_driver = $result->num_rows;
            if ($items_driver > 0) {
                 $data_driver = $result->fetch_assoc();
                 $llaves_driver  = array_keys($data_driver);
                 $datos_driver   = $data_driver;
                 foreach($datos_driver as $i => $b){ 
                    $fields .= "\$('#$domroot :input[id=".$i."_operador]').val('".$datos_driver[$i]."');"; 
                 }
                 
                 //Get files from driver:
                 $sql_driver_files = "SELECT iConsecutivo, sNombreArchivo, eArchivo FROM cb_operador_files 
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
                 } 
            }
        }  
        else if($data['iConsecutivoUnidad']!= '' && $data['iConsecutivoTipoEndoso']== '1'){ 
            $sql2 = "SELECT iConsecutivo, iConsecutivoRadio, iYear, iModelo, sVIN, sModelo, sTipo ".    
                    "FROM ct_unidades ".
                    "WHERE iConsecutivo = '".$data['iConsecutivoUnidad']."'";
            $result = $conexion->query($sql2);
            $items2 = $result->num_rows;
            if ($items2 > 0) {
                 $data2 = $result->fetch_assoc();
                 $llaves2  = array_keys($data2);
                 $datos2   = $data2;
                 foreach($datos2 as $i => $b){ 
                    $fields .= "\$('#$domroot :input[id=".$i."_unit]').val('".$datos2[$i]."');"; 
                 }
                 
                 //Get files from driver:
                 $sql_files = "SELECT iConsecutivo, sNombreArchivo, eArchivo FROM cb_unidad_files ". 
                              "WHERE  iConsecutivoUnidad = '".$data['iConsecutivoUnidad']."'";
                 $result_files = $conexion->query($sql_files);
                 while ($items_files = $result_files->fetch_assoc()){
                     if($items_files["eArchivo"] == 'TITLE'){
                        $fields .= "\$('#$domroot :input[id=txtsTituloPDF]').val('".$items_files['sNombreArchivo']."');";
                        $fields .= "\$('#$domroot :input[id=iConsecutivoTituloPDF]').val('".$items_files['iConsecutivo']."');";  
                     }
                     if($items_files["eArchivo"] != 'TITLE'){
                        $fields .= "\$('#$domroot :input[id=DeleteCause]').val('".$items_files['eArchivo']."');";
                        $fields .= "\$('#$domroot :input[id=txtsDAPDF]').val('".$items_files['sNombreArchivo']."');";
                        $fields .= "\$('#$domroot :input[id=iConsecutivoDAPDF]').val('".$items_files['iConsecutivo']."');"; 
                     }
                 } 
            }
        }
        
        //Verificar si el endoso a sido Denegado:
        $endorsement_denied = "";
        if($data['eStatus'] == 'D' && $data['sComentarios'] != ''){
           $endorsement_denied = "<label>Denied Reason(s):</label></br>".
                                 "<p>".utf8_decode($data['sComentarios'])."</p>";    
        }  
        
          
      }
      $conexion->rollback();
      $conexion->close(); 
      $response = array("msj"=>"$msj","error"=>"$error","fields"=>"$fields","denied" => "$endorsement_denied","policies_table"=>"$tablaPolizas");   
      echo json_encode($response);  
      
  } */ 
  function delete_endorsement_co(){
      $clave = $_POST['clave'];
      $error = '0';  
      $msj = "";  
      //Conexion:
      include("cn_usuarios.php"); 
      $company = $_SESSION['company'];
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      //Revisar que tipo de endoso es para verrificar si hay que borrar otros datos antes:
      $query_endoso = "SELECT iConsecutivoTipoEndoso, iReeferYear, iTrailerExchange, iConsecutivoOperador, iConsecutivoUnidad 
                       FROM cb_endoso WHERE iConsecutivo = '$clave' AND iConsecutivoCompania = '$company'"; 
      $result = $conexion->query($query_endoso); 
      $rows = $result->num_rows;
      if($rows > 0 ){$endoso = $result->fetch_assoc();}else{$error = '1';}
      
      //BORRAMOS ENDOSO.
      if($error == '0'){
          $query = "DELETE FROM cb_endoso WHERE iConsecutivo = '$clave' AND iConsecutivoCompania = '$company'"; 
          $conexion->query($query);
          $conexion->affected_rows ? $transaccion_exitosa = true : $transaccion_exitosa = false;
      }
      else{$msj = "Error: Descriptions in Endorsement are not found.";$transaccion_exitosa = false;} 
      
      if($transaccion_exitosa){
        $conexion->commit();
        $conexion->close();
        $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Data has been deleted succesfully!</p>';
      }else{
        $conexion->rollback();
        $conexion->close();
        $msj = "A general system error ocurred : internal error";
        $error = "1";
      }
        
      $response = array("msj"=>"$msj","error"=>"$error");   
      echo json_encode($response);
      
  }
  
  //FUNCIONES PARA ENVIO...
  function send_endorsement_data(){
      include("cn_usuarios.php");
      $error = '0';
      //variables:
      $id= $_POST['clave']; 
      $fechaAplicacion = date("Y-m-d H:i:s");
      
      $conexion->autocommit(FALSE);
      $transaccion_exitosa = true;
      #1- First Step: Consult the general information from the Endorsement with the id.
      $endorsement_query = "SELECT A.iConsecutivo,iConsecutivoCompania, sNombreCompania,iConsecutivoTipoEndoso, sDescripcion AS TipoEndoso, eStatus, iReeferYear, sNumPolizas, ".
                           "iTrailerExchange, iPDAmount, iPDApply, iConsecutivoOperador, iConsecutivoUnidad, eAccion, sComentarios,  DATE_FORMAT(A.dFechaIngreso,  '%m/%d/%Y %H:%i') AS dFechaIngreso ".
                           "FROM cb_endoso A ".
                           "LEFT JOIN ct_companias B ON A.iConsecutivoCompania = B.iConsecutivo  ".
                           "LEFT JOIN ct_tipo_endoso C ON A.iConsecutivoTipoEndoso = C.iConsecutivo ".
                           "WHERE A.iConsecutivo = '$id'";
      $result = $conexion->query($endorsement_query);
      $rows = $result->num_rows; 
      $rows > 0 ? $endorsement = $result->fetch_assoc() : $endorsement = "";
      if($endorsement['iConsecutivo'] != ""){    
          #2- Second step: Check the endorsement type.
          //$email_destinatario = "celina@globalpc.com"; // <---- pruebasss.. 
          $email_destinatario = "customerservice@solo-trucking.com";
          //DRIVERS
          if($endorsement['iConsecutivoTipoEndoso'] == '2' && $endorsement['iConsecutivoOperador'] != ''){ 
             #Driver: Consult the driver information:
             $driver_query = "SELECT iConsecutivo, sNombre,DATE_FORMAT(dFechaNacimiento,'%m/%d/%Y') AS dFechaNacimiento, ". 
                             "DATE_FORMAT(dFechaExpiracionLicencia,'%m/%d/%Y') AS dFechaExpiracionLicencia,iExperienciaYear, ".
                             "iNumLicencia,dFechaContratacion,eTipoLicencia FROM ct_operadores ". 
                             "WHERE iConsecutivo = '".$endorsement['iConsecutivoOperador']."'";
             $result_d = $conexion->query($driver_query);
             $rows_d = $result_d->num_rows; 
             $rows_d > 0 ? $driver = $result_d->fetch_assoc() : $driver = "";
             
             if($driver['sNombre'] != ''){

                 #Calculate the emails to send:
                 $htmlEmail = "";
                 #TRAER DATOS DE LA POLIZA QUE SELECCIONO LA COMPANIA:
                 $PolizasEndoso = explode('|',$endorsement['sNumPolizas']);
                 $filtro = "";
                 $subject_policy = "";
                 
                 for ($i = 0; $i < count($PolizasEndoso); $i++) {
                       $poliza = explode('/',$PolizasEndoso[$i]);
                       $filtro = "AND sNumeroPoliza = '".$poliza[0]."' AND iTipoPoliza = '".$poliza[1]."' "; 
                       
                       $policy_query = "SELECT CONCAT(D.sDescripcion,' - ',sNumeroPoliza) AS policy ".
                                       "FROM ct_polizas A LEFT JOIN ct_tipo_poliza D ON A.iTipoPoliza = D.iConsecutivo ".
                                       "WHERE iConsecutivoCompania = '".$endorsement['iConsecutivoCompania']."' ".
                                       "$filtro AND A.iDeleted ='0'";   
                       $result_policy = $conexion->query($policy_query);
                       $rows_policy = $result_policy->num_rows; 
                       if($rows_policy > 0){
                           $policies = $result_policy->fetch_assoc();
                           $subject_policy == '' ? $subject_policy = $policies['policy'] : $subject_policy .= '//'.$policies['policy'];
                       }    
                 }

                       
                 #action to define the subject:
                 switch($endorsement["eStatus"]){ 
                        case 'E': //Si es un nuevo endoso esta como "edit"
                            $subject = "Solo-trucking Insurance system notification - new endorsement from company: ".$endorsement['sNombreCompania'].", ".$subject_policy; 
                            break; 
                        case 'D': //Si esta como "Denied", entonces es una correccion del endoso.                                                                  
                            $subject = "Solo-trucking Insurance system notification - new updated in endorsement from company: ".$endorsement['sNombreCompania'].", ".$subject_policy; 
                        break;
                 }
                #Definiendo la variable accion.
                switch($endorsement["eAccion"]){ 
                    case 'A': 
                        $action = 'Notification to add following driver in my policy.';                               
                        break; 
                    case 'D': 
                        $action = 'Notification to delete following driver from my policy.';                                                                   
                    break;
                }
                 
                         
                        #Building Email Body:                                   
                        require_once("./lib/phpmailer_master/class.phpmailer.php");
                        require_once("./lib/phpmailer_master/class.smtp.php");
                        
                        //header
                        $htmlEmail .= "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"\"http://www.w3.org/TR/html4/strict.dtd\"><html>
                                      <head>
                                          <meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\">
                                          <title>endorsement from solo-trucking insurance</title>
                                      </head>";
                        //Body            
                        $htmlEmail .= "<body>".
                                      "<table style=\"font-size:12px;border:1px solid #6191df;border-radius:3px;padding:10px;width:95%; margin:5px auto;font-family: Arial, Helvetica, sans-serif;\">".
                                      "<tr><td>".
                                      "<h2 style=\"color:#313131;text-transform: uppercase; text-align:center;\">System Notification</h2>".
                                      "</td></tr>".
                                      "<tr><td>".
                                      "<p style=\"color:#000;margin:5px auto; text-align:left;\">$action</p><br><br>".
                                      "</td></tr>".
                                      "<tr><td>".
                                      "<p style=\"color:#000;margin:5px auto; text-align:left;\"><b>ENDORSEMENT INFORMATION:</b></p>".
                                      "<ul style=\"color:#010101;line-height:15px;list-style:none;\"> ".
                                          "<li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">ID: </strong>".$endorsement['iConsecutivo']."</li>".
                                          "<li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">COMPANY: </strong>".$endorsement['sNombreCompania']."</li>".
                                          "<li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">APPLICATION DATE: </strong>".$fechaAplicacion."</li>".
                                          "<li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">TYPE: </strong>Driver</li>".
                                      "</ul>".
                                      "</td></tr>".
                                      "<tr><td></td></tr>".
                                      "<tr><td>".
                                      "<p style=\"color:#000;margin:5px auto; text-align:left;\"><b>DRIVER INFORMATION:</b></p>".
                                      "<ul style=\"color:#010101;line-height:15px;list-style:none;\"> ".
                                          "<li style=\"line-height:15px;\"><b style=\"color:#044e8d;\">NAME: </b>".$driver['sNombre']."</li>".
                                          "<li style=\"line-height:15px;\"><b style=\"color:#044e8d;\">DOB: </b>".$driver['dFechaNacimiento']."</li>".
                                          "<li style=\"line-height:15px;\"><b style=\"color:#044e8d;\">YOE: </b>".$driver['iExperienciaYear']."</li>".
                                          "<li style=\"line-height:15px;\"><b style=\"color:#044e8d;\">LICENSE EXP: </b>".$driver['dFechaExpiracionLicencia']."</li>".
                                          "<li style=\"line-height:15px;\"><b style=\"color:#044e8d;\">LICENSE NUMBER: </b>".$driver['iNumLicencia']."</li>".
                                      "</ul>".
                                      "</td></tr>".
                                      "<tr>".
                                      "<td><p style=\"color:#858585;margin:5px auto; text-align:left;font-size:10px;\">e-mail sent from Solo-trucking Insurance Agency System.</p></td>".
                                      "</tr>".
                                      "</table>".
                                      "</body>";
                        //footer              
                        $htmlEmail .= "</html>"; 
                        
                        #TERMINA CUERPO DEL MENSAJE
                        $mail = new PHPMailer();   
                        $mail->IsSMTP(); // telling the class to use SMTP
                        $mail->Host       = "mail.solo-trucking.com"; // SMTP server
                        //$mail->SMTPDebug  = 2; // enables SMTP debug information (for testing) 1 = errors and messages 2 = messages only
                        $mail->SMTPAuth   = true;                  // enable SMTP authentication
                        $mail->SMTPSecure = "TLS";                 // sets the prefix to the servier
                        $mail->Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
                        $mail->Port       = 587;                   // set the SMTP port for the GMAIL server
                        $mail->Username   = "systemsupport@solo-trucking.com";  // GMAIL username
                        $mail->Password   = "SL09100242"; 
                        $mail->SetFrom('systemsupport@solo-trucking.com', 'Solo-Trucking Insurance');
                        $mail->AddReplyTo(trim($_SESSION["usuario_actual"]),trim($_SESSION["company_name"]));
                        $mail->Subject    = $subject;
                        $mail->AltBody    = "To view the message, please use an HTML compatible email viewer!";  // optional, comment out and test
                        $mail->MsgHTML($htmlEmail);
                        $mail->IsHTML(true); 
                        $mail->AddAddress($email_destinatario,'Customer service Solo-Trucking');
                        
                        //VALIDAR QUE SI ES UN ENDOSO ADD VENGAN LOS ARCHIVOS NECESARIOS PARA PROCEDER:
                        if($endorsement['eAccion'] == 'A'){
                            if($driver['eTipoLicencia'] == 2){ //Validar si subio el MVR
                                //Consult the driver files:
                                $driver_files_query = "SELECT COUNT(iConsecutivo) AS total ".  
                                                      "FROM cb_operador_files ". 
                                                      "WHERE  iConsecutivoOperador = '".$endorsement['iConsecutivoOperador']."' AND eArchivo = 'MVR'";
                                
                                $result_files = $conexion->query($driver_files_query);
                                $files = $result_files->fetch_assoc();
                                $registros_files = $files["total"]; 
                                if($registros_files == "0"){
                                    $error = '1';
                                    $msj = "The driver MVR was not found, please verify your data.";
                                }
                           
                                
                            } 
                        } 
                        if($error == '0'){
                           $mail_error = false;
                           if(!$mail->Send()){
                               $mail_error = true; 
                               $mail->ClearAddresses();
                               //echo "Mailer Error: " . $mail->ErrorInfo; 
                           }
                           if(!$mail_error){
                                //$mensaje = "Mail confirmation to the user $usuario was successfully sent.";
                           }else{$mensaje = "Error: The e-mail cannot be sent.";$error = "1";} 
                            
                        }
                        
                        
                        
                 #VERIFICAR SI SE ENVIO CORRECTAMENTE:
                 if($error == '0'){ 
                    #UPDATE ENDORSEMENT DETAILS:
                    $sql_endoso = "UPDATE cb_endoso SET eStatus = 'S', dFechaAplicacion = '".date("Y-m-d H:i:s")."', sUsuarioActualizacion='".$_SESSION['usuario_actual']."', ".
                                  "sIP='".$_SERVER['REMOTE_ADDR']."', sUsuarioActualizacion='".$_SESSION['usuario_actual']."'".
                                  " WHERE iConsecutivo = '".$endorsement['iConsecutivo']."'"; 
                    $conexion->query($sql_endoso);
                       if($conexion->affected_rows < 1){
                            $transaccion_exitosa = false;
                            $error = '1';
                            $msj = "The data of endorsement was not updated properly, please try again.";
                       }else{
                           
                           #CREAR REGISTROS EN LA TABLA DE ESTATUS POR POLIZA:
                           $TablaEstatusEndoso = explode('|',$endorsement['sNumPolizas']);
                           for ($i = 0; $i < count($TablaEstatusEndoso); $i++) {
                                   $poliza = explode('/',$TablaEstatusEndoso[$i]);
                                   $filtro = "AND sNumeroPoliza = '".$poliza[0]."' AND iTipoPoliza = '".$poliza[1]."' "; 
                                   
                                   $policy_query = "SELECT iConsecutivo FROM ct_polizas WHERE iConsecutivoCompania = '".$endorsement['iConsecutivoCompania']."' $filtro AND iDeleted ='0'";   
                                   $result_policy = $conexion->query($policy_query); 
                                   $rows_policy = $result_policy->num_rows; 
                                   
                                   if($rows_policy > 0){
                                       $policies = $result_policy->fetch_assoc();
                                       $sql_endoso_estatus = "INSERT INTO cb_endoso_estatus (iConsecutivoEndoso, iConsecutivoPoliza, eStatus) VALUES ('".$endorsement['iConsecutivo']."','".$policies['iConsecutivo']."','S')";
                                       $conexion->query($sql_endoso_estatus);
                                       if($conexion->affected_rows < 1){
                                            $transaccion_exitosa = false;
                                            $msj = "The data of endorsement was not updated properly, please try again.";
                                       }
                                   }    
                           }
                       } 
  
                 }
  
             }else{
                 $error = '1';
                 $msj = "The data driver was not found, please try again.";
             }
                               
          }
          
          //UNITS
          if($endorsement['iConsecutivoTipoEndoso'] == '1' && $endorsement['iConsecutivoUnidad'] != ''){
             #Unit: Consult the unit information:
             $unit_query = "SELECT A.iConsecutivo, iConsecutivoCompania, iYear, iModelo, sVIN, sModelo, B.sAlias, B.sDescripcion AS Make, C.sDescripcion AS Radius, sTipo ".  
                           "FROM ct_unidades A ".
                           "LEFT JOIN ct_unidad_modelo B ON A.iModelo = B.iConsecutivo ".
                           "LEFT JOIN ct_unidad_radio C ON A.iConsecutivoRadio = C.iConsecutivo ".
                           "WHERE A.iConsecutivo = '".$endorsement['iConsecutivoUnidad']."'";
             $result_d = $conexion->query($unit_query);
             $rows_d = $result_d->num_rows; 
             $rows_d > 0 ? $unit = $result_d->fetch_assoc() : $unit = "";
             
             if($unit['iConsecutivo'] != ''){

                       $htmlEmail = "";
                       $pd = false; // Bandera para saber si la compania tiene poliza de PD.
                       $PolizasEndoso = explode('|',$endorsement['sNumPolizas']);
                       $filtro = "";
                       $subject_policy = "";
                       for ($i = 0; $i < count($PolizasEndoso); $i++) {
                           $poliza = explode('/',$PolizasEndoso[$i]);
                           $filtro = "AND sNumeroPoliza = '".$poliza[0]."' AND iTipoPoliza = '".$poliza[1]."' "; 
                           
                           $policy_query = "SELECT CONCAT(D.sDescripcion,' - ',sNumeroPoliza) AS policy ".
                                           "FROM ct_polizas A LEFT JOIN ct_tipo_poliza D ON A.iTipoPoliza = D.iConsecutivo ".
                                           "WHERE iConsecutivoCompania = '".$endorsement['iConsecutivoCompania']."' ".
                                           "$filtro AND A.iDeleted ='0'";   
                           $result_policy = $conexion->query($policy_query);
                           $rows_policy = $result_policy->num_rows; 
                           if($rows_policy > 0){
                               $policies = $result_policy->fetch_assoc();
                               $subject_policy == '' ? $subject_policy = $policies['policy'] : $subject_policy .= '//'.$policies['policy'];
                               if($policies['iTipoPoliza'] == '1'){$pd = true;} // si es verdadero si tiene poliza de tipo PD
                            
                           }    
                       }
                       
                      #MAKE
                      if($unit['sAlias'] != ''){$make = $unit['sAlias'];}else if($unit['Make'] != ''){$make = $unit['Make']; }
                      
                      #action to define the subject:
                      switch($endorsement["eStatus"]){ 
                            case 'E': //Si es un nuevo endoso esta como "edit"
                                $subject = "Solo-trucking Insurance system notification - new endorsement from company: ".$endorsement['sNombreCompania'].", ".$subject_policy; 
                                break; 
                            case 'D': //Si esta como "Denied", entonces es una correccion del endoso.                                                                  
                                $subject = "Solo-trucking Insurance system notification - new updated in endorsement from company: ".$endorsement['sNombreCompania'].", ".$subject_policy; 
                            break;
                      }
                              
                      #action to define the subject an body email: 
                      if($endorsement["eAccion"] == 'A'){
                            $action = 'Notification to add following '.strtolower($unit['sTipo']).' in my policy.';
                            //$subject = "Endorsement application - please add the following unit from policy number: ".$endorsement['sNombreCompania'].", ".$subject_policy;
                            
                            #PDAmount
                            $pd && $endorsement["iPDApply"] == '1' && $endorsement["iPDAmount"] != '' ? $PDAmount = number_format($endorsement["iPDAmount"]) : $PDAmount = '';
                            
                            #RADIUS:
                            $radius = explode('(',$unit['Radius']);
                            
                            $bodyData = "<p style=\"color:#000;margin:5px auto; text-align:left;\">".$unit['iYear']."&nbsp;".$make."&nbsp;".$unit['sVIN']."&nbsp;".$radius[0]."&nbsp;11-22&nbsp;TONS&nbsp;".$PDAmount."</p><br><br>";
                          
                      }else if($endorsement["eAccion"] == 'D'){
                           $action = 'Notification to delete following '.strtolower($unit['sTipo']).' from my policy.';                                                                   
                           //$subject = "Endorsement application - please delete the following unit from policy number: ".$endorsement['sNombreCompania'].", ".$subject_policy;
                           $bodyData = "<p style=\"color:#000;margin:5px auto; text-align:left;\">".$unit['iYear']."&nbsp;".$make."&nbsp;".$unit['sVIN']."</p><br><br>";
                      } 
                    #Building Email Body:                                   
                    require_once("./lib/phpmailer_master/class.phpmailer.php");
                    require_once("./lib/phpmailer_master/class.smtp.php");
                    
                    //header
                    $htmlEmail .= "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"\"http://www.w3.org/TR/html4/strict.dtd\"><html>".
                                    "<head><meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\">".
                                    "<title>Endorsement from Solo-Trucking Insurance</title></head>"; 
                     
                     //Body - Armar Body dependiendo el tipo de poliza:
                    $htmlEmail .= "<body>".
                                  "<table style=\"font-size:12px;border:1px solid #6191df;border-radius:3px;padding:10px;width:95%; margin:5px auto;font-family: Arial, Helvetica, sans-serif;\">".
                                  "<tr><td>".
                                  "<h2 style=\"color:#313131;text-transform: uppercase; text-align:center;\">Endorsement application from Solo-Trucking Insurance</h2>".
                                  "</td></tr>".
                                  "<tr><td>".
                                  "<p style=\"color:#000;margin:5px auto; text-align:left;\">$action</p><br><br>".
                                  "</td></tr>".
                                  "<tr><td>".
                                  "<p style=\"color:#000;margin:0px auto; text-align:left;\"><b>ENDORSEMENT INFORMATION:</b></p>".
                                  "<ul style=\"color:#010101;list-style:none;\"> ".
                                      "<li style=\"line-height:15px;\"><b style=\"color:#044e8d;\">ID: </b>".$endorsement['iConsecutivo']."</li>".
                                      "<li style=\"line-height:15px;\"><b style=\"color:#044e8d;\">COMPANY: </b>".$endorsement['sNombreCompania']."</li>".
                                      "<li style=\"line-height:15px;\"><b style=\"color:#044e8d;\">APPLICATION DATE: </b>".$endorsement['dFechaIngreso']."</li>".
                                      "<li style=\"line-height:15px;\"><b style=\"color:#044e8d;\">TYPE: </b>Driver</li>".
                                  "</ul>".
                                  "</td></tr>".
                                  "<tr><td></td></tr>". 
                                  "<tr><td><p style=\"color:#000;margin:5px auto; text-align:left;\"><b>".$unit['sTipo']." INFORMATION:</b></p></td></tr>".
                                  "<tr><td>".$bodyData."</td></tr>".
                                  "<tr>".
                                  "<td><p style=\"color:#858585;margin:5px auto; text-align:left;font-size:10px;\">e-mail sent from Solo-trucking Insurance System.</p></td>".
                                  "</tr>".
                                  "</table>".
                                  "</body>";   
                        
                     //footer              
                     $htmlEmail .= "</html>"; 
                    
                    #TERMINA CUERPO DEL MENSAJE
                    $mail = new PHPMailer();   
                    $mail->IsSMTP(); // telling the class to use SMTP
                    $mail->Host       = "mail.solo-trucking.com"; // SMTP server
                    //$mail->SMTPDebug  = 2; // enables SMTP debug information (for testing) 1 = errors and messages 2 = messages only
                    $mail->SMTPAuth   = true;                  // enable SMTP authentication
                    $mail->SMTPSecure = "TLS";                 // sets the prefix to the servier
                    $mail->Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
                    $mail->Port       = 587;                   // set the SMTP port for the GMAIL server
                    $mail->Username   = "systemsupport@solo-trucking.com";  // GMAIL username
                    $mail->Password   = "SL09100242"; 
                    $mail->SetFrom('systemsupport@solo-trucking.com', 'Solo-Trucking Insurance');
                    $mail->AddReplyTo(trim($_SESSION["usuario_actual"]),trim($_SESSION["company_name"]));
                    $mail->Subject    = $subject;
                    $mail->AltBody    = "To view the message, please use an HTML compatible email viewer!";  // optional, comment out and test
                    $mail->MsgHTML($htmlEmail);
                    $mail->IsHTML(true); 
                    $mail->AddAddress($email_destinatario,'Customer service Solo-Trucking');
                     
                    $mail_error = false; 
                    if(!$mail->Send()){
                        $mail_error = true; 
                        $mail->ClearAddresses();
                        //echo "Mailer Error: " . $mail->ErrorInfo;
                    }
                    if(!$mail_error){
   
                    }else{
                        $mensaje = "Error: The e-mail cannot be sent.";
                        $error = "1";            
                    }

                        
                 #VERIFICAR SI SE ENVIARON LOS CORREOS CORRECTAMENTE:
                 if($error == '0'){
                    #UPDATE ENDORSEMENT DETAILS:
                    $sql_endoso = "UPDATE cb_endoso SET eStatus = 'S',  dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."', sUsuarioActualizacion='".$_SESSION['usuario_actual']."',".
                                  "dFechaAplicacion='".date("Y-m-d H:i:s")."' ". 
                                  "WHERE iConsecutivo = '".$endorsement['iConsecutivo']."'"; 
                    $conexion->query($sql_endoso);
                       if($conexion->affected_rows < 1){
                            $transaccion_exitosa = false;
                            $error = '1';
                            $msj = "The data of endorsement was not updated properly, please try again.";
                       }else{
                            #CREAR REGISTROS EN LA TABLA DE ESTATUS POR POLIZA:
                           $TablaEstatusEndoso = explode('|',$endorsement['sNumPolizas']);
                           for ($i = 0; $i < count($TablaEstatusEndoso); $i++) {
                                   $poliza = explode('/',$TablaEstatusEndoso[$i]);
                                   $filtro = "AND sNumeroPoliza = '".$poliza[0]."' AND iTipoPoliza = '".$poliza[1]."' "; 
                                   $policy_query = "SELECT iConsecutivo FROM ct_polizas WHERE iConsecutivoCompania = '".$endorsement['iConsecutivoCompania']."' $filtro AND iDeleted ='0'";   
                                   $result_policy = $conexion->query($policy_query); 
                                   $rows_policy = $result_policy->num_rows; 
                                   
                                   if($rows_policy > 0){
                                       $policies = $result_policy->fetch_assoc();
                                       $sql_endoso_estatus = "INSERT INTO cb_endoso_estatus (iConsecutivoEndoso, iConsecutivoPoliza, eStatus) VALUES ('".$endorsement['iConsecutivo']."','".$policies['iConsecutivo']."','S')";
                                       
                                       $conexion->query($sql_endoso_estatus);
                                       if($conexion->affected_rows < 1){
                                            $transaccion_exitosa = false;
                                            $msj = "The data of endorsement was not updated properly, please try again.";
                                       }
                                   }    
                           }
                            
                       } 
  
                 }
  
             }else{
                 $error = '1';
                 $msj = "The data of unit/trailer was not found, please try again.";
             } 
          } 
          
            
      }else{
          $error = '1';
          $msj = "The endorsement data was not found, please try again.";
      }
      
      if($transaccion_exitosa && $error == "0"){
            $conexion->commit();
            $conexion->close();
            $msj = "The Endorsement was sent successfully, please check the status waiting for our response."; 
      }else{
            $conexion->rollback();
            $conexion->close();
      }
      $response = array("error"=>"$error","msj"=>"$msj");
      echo json_encode($response);
  }
  function resend_endorsement_data(){
     
     include("cn_usuarios.php");
     $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
     $transaccion_exitosa = true; 
      
     //variables:
     $id= $_POST['clave'];
     $error = '0';
     $mensaje = '';
     
     //Get the endorsement data status...
     $sql = "SELECT iConsecutivoEndoso, iConsecutivoPoliza FROM cb_endoso_estatus ".
            "WHERE iConsecutivoEndoso = '$id' AND eStatus = 'D'";
     $result = $conexion->query($sql);
     $rows = $result->num_rows;
      
      if($rows > 0){
          while ($items = $result->fetch_assoc()) {
              $query = "UPDATE cb_endoso_estatus SET eStatus='S', dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."',sUsuarioActualizacion='".$_SESSION['usuario_actual']."'".
                       "WHERE iConsecutivoEndoso = '".$items['iConsecutivoEndoso']."' AND iConsecutivoPoliza = '".$items['iConsecutivoPoliza']."'"; 
              $conexion->query($query);
              $conexion->affected_rows < 1 ? $transaccion_exitosa = false : $transaccion_exitosa = true;
              if(!$transaccion_exitosa){
                        $error = '1';
                        $msj = "Error: The Endorsement status data was not updated successfully, please try again.";
              }else{ $msj = "The data has been resend successfully.";}
          }
      }else{
          $error = '1';
          $mensaje = 'Error: Edorsement status data not found, please try again.';
      }
      
      if($error == '0'){
         $conexion->commit();
         $conexion->close(); 
      }else{
         $conexion->rollback();
         $conexion->close(); 
      }
      
      $response = array("error"=>"$error","msj"=>"$msj");
      echo json_encode($response);
  
      
  }

  function send_quote_email(){
      
      //variables:  
      include("cn_usuarios.php");
      $error = '0';
      $htmlEmail = ""; 
      $iConsecutivo_Endoso = trim($_POST['clave']);
      $conexion->autocommit(FALSE);
      $transaccion_exitosa = true;
      
      #1- Primero consultamos toda la informacion del Endoso:
      $endorsement_query = "SELECT U.iConsecutivo, A.iConsecutivoCompania, sNombreCompania, iConsecutivoTipoEndoso, sNumPolizas,eAccion, iConsecutivoUnidad,sVIN,iYear, sAlias AS Modelo, R.sDescripcion AS Radio, sPeso, iPDAmount, iPDApply, sTipo  ".
                           "FROM ct_unidades A ".
                           "LEFT JOIN ct_companias B ON A.iConsecutivoCompania = B.iConsecutivo ".
                           "LEFT JOIN cb_endoso U ON A.iConsecutivo = U.iConsecutivoUnidad ".
                           "LEFT JOIN ct_unidad_modelo M ON A.iModelo = M.iConsecutivo ".
                           "LEFT JOIN ct_unidad_radio R ON A.iConsecutivoRadio = R.iConsecutivo ".
                           "WHERE U.iConsecutivo = '$iConsecutivo_Endoso'";
      $result = $conexion->query($endorsement_query);
      $rows = $result->num_rows; 
      $rows > 0 ? $endorsement = $result->fetch_assoc() : $endorsement = "";
      
      if($endorsement['iConsecutivo'] != "" && $endorsement['iConsecutivoTipoEndoso'] == "1" && $endorsement['eAccion'] == "A"){ 
          
          #2 - Cargamos Datos de Polizas y Brokers:
          if($endorsement['iConsecutivoUnidad'] != ''){
                
              $pd = false; // Bandera para saber si la compania tiene poliza de PD.
              $PolizasEndoso = explode('|',$endorsement['sNumPolizas']); 
              $PolizasEndoso = explode('|',$endorsement['sNumPolizas']);
              $filtro = "";
              $subject_policy = "";
              
              for ($i = 0; $i < count($PolizasEndoso); $i++) {
                   
                   $poliza = explode('/',$PolizasEndoso[$i]);
                   $filtro = "AND sNumeroPoliza = '".$poliza[0]."' AND iTipoPoliza = '".$poliza[1]."' "; 
                   
                   $policy_query = "SELECT CONCAT(D.sDescripcion,' - ',sNumeroPoliza) AS policy ".
                                   "FROM ct_polizas A LEFT JOIN ct_tipo_poliza D ON A.iTipoPoliza = D.iConsecutivo ".
                                   "WHERE iConsecutivoCompania = '".$endorsement['iConsecutivoCompania']."' ".
                                   "$filtro AND A.iDeleted ='0'";   
                   $result_policy = $conexion->query($policy_query);
                   $rows_policy = $result_policy->num_rows; 
                   if($rows_policy > 0){
                       $policies = $result_policy->fetch_assoc();
                       $subject_policy == '' ? $subject_policy = $policies['policy'] : $subject_policy .= '//'.$policies['policy'];
                       if($policies['iTipoPoliza'] == '1'){$pd = true;} // si es verdadero si tiene poliza de tipo PD
                    
                   }    
               }
              
              #define the subject:
              $subject = "Solo-trucking Insurance system notification - new quote to unit endorsement from company: ".$endorsement['sNombreCompania'].", ".$subject_policy; 
              #PDAmount
              $pd && $endorsement["iPDApply"] == '1' && $endorsement["iPDAmount"] != '' ? $PDAmount = number_format($endorsement["iPDAmount"]) : $PDAmount = '';
              
              #Building Email Body:                                   
              require_once("./lib/phpmailer_master/class.phpmailer.php");
              require_once("./lib/phpmailer_master/class.smtp.php"); 
              
              //header
              $htmlEmail .= "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"\"http://www.w3.org/TR/html4/strict.dtd\"><html>".
                            "<head><meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\">".
                            "<title>Endorsement from Solo-Trucking Insurance</title></head>"; 
              //Body - Armar Body dependiendo el tipo de poliza:
              $htmlEmail .= "<body>".
                            "<table style=\"font-size:12px;border:1px solid #6191df;border-radius:3px;padding:10px;width:95%; margin:5px auto;font-family: Arial, Helvetica, sans-serif;\">".
                            "<tr><td>".
                            "<h2 style=\"color:#313131;text-transform: uppercase; text-align:center;\">Request to quote an ".strtolower($endorsement['sTipo'])." endorsement from Solo-Trucking Insurance system</h2>".
                            "</td></tr>".
                            "<tr><td>".
                            "<p style=\"color:#000;margin:5px auto; text-align:left;\">Hello, <br> This email has been sent from the solo-trucking system by the company: </p><br><br>".
                            "</td></tr>".
                            "<tr><td>".
                            "<p style=\"color:#000;margin:0px auto; text-align:left;\"><b>The data of unit endorsement are:</b></p>".
                            "<ul style=\"color:#010101;list-style:none;\"> ".
                                  "<li style=\"line-height:15px;\"><b style=\"color:#044e8d;\">System ID: </b>".$endorsement['iConsecutivo']."</li>".
                                  "<li style=\"line-height:15px;\"><b style=\"color:#044e8d;\">Company Name: </b>".$endorsement['sNombreCompania']."</li>".
                                  "<li style=\"line-height:15px;\"><b style=\"color:#044e8d;\">Company Contact E-mail: </b>".trim($_SESSION['usuario_actual'])."</li>".
                            "</ul>".
                            "</td></tr>".
                            "<tr><td>".
                            "<p style=\"color:#000;margin:0px auto; text-align:left;\"><b>Data of unit:</b></p>".
                            "<p style=\"color:#000;margin:5px auto; text-align:left;font-size:13px;\">".$endorsement['iYear']."&nbsp;".$endorsement['Modelo']."&nbsp;".$endorsement['sVIN']."&nbsp;".$endorsement['Radio']."&nbsp;".$endorsement['sPeso']."&nbsp;&nbsp;".$PDAmount."</p><br><br>".
                            "</td></tr>".
                            "<tr><td>".
                            "<p style=\"color:#9e2e2e;margin:5px auto; text-align:left;\">Note:</p>".
                            "<p style=\"color:#000;margin:5px auto; text-align:left;\">Please follow up on this quote sending it directly to the customer and to the corresponding brokers.</p><br><br>".
                            "</td></tr>". 
                            "<tr>".
                            "<td><p style=\"color:#858585;margin:5px auto; text-align:left;font-size:10px;\">e-mail sent from Solo-trucking Insurance System.</p></td>".
                            "</tr>".
                            "</table>".
                            "</body>";   
                
             //footer              
             $htmlEmail .= "</html>"; 
            
            #TERMINA CUERPO DEL MENSAJE
            $mail = new PHPMailer();   
            $mail->IsSMTP(); // telling the class to use SMTP
            $mail->Host       = "mail.solo-trucking.com"; // SMTP server
            //$mail->SMTPDebug  = 2; // enables SMTP debug information (for testing) 1 = errors and messages 2 = messages only
            $mail->SMTPAuth   = true;                  // enable SMTP authentication
            $mail->SMTPSecure = "TLS";                 // sets the prefix to the servier
            $mail->Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
            $mail->Port       = 587;                   // set the SMTP port for the GMAIL server
            $mail->Username   = "systemsupport@solo-trucking.com";  // GMAIL username
            $mail->Password   = "SL09100242"; 
            $mail->SetFrom('systemsupport@solo-trucking.com', 'Solo-Trucking Insurance');
            $mail->AddReplyTo(trim($_SESSION["usuario_actual"]),trim($_SESSION["company_name"]));
            $mail->Subject    = $subject;
            $mail->AltBody    = "To view the message, please use an HTML compatible email viewer!";  // optional, comment out and test
            $mail->MsgHTML($htmlEmail);
            $mail->IsHTML(true); 
            $email_destinatario = "customerservice@solo-trucking.com"; 
            $mail->AddAddress($email_destinatario,'Customer service Solo-Trucking');
             
            $mail_error = false; 
            if(!$mail->Send()){
                $mail_error = true; 
                $mail->ClearAddresses();
                //echo "Mailer Error: " . $mail->ErrorInfo;
            }
            if(!$mail_error){

            }else{
                $mensaje = "Error: The e-mail cannot be sent.";
                $error = "1";            
            }
              
                       
              
          }else{
             $error = '1';
             $msj = "The unit endorsement data was not found, please try again.";     
          }
          
          
      }else{
          $error = '1';
          $msj = "The endorsement data was not found, please try again.";
      }     
      
      
      if($error == '0'){
            $conexion->commit();
            $conexion->close();
            $msj = "The quote request has been sent successfully!, please check your e-mail to wait for your information."; 
      }else{
            $conexion->rollback();
            $conexion->close();
      }
      $response = array("error"=>"$error","msj"=>"$msj");
      echo json_encode($response);
  }
  
  #NUEVAS FUNCIONES:
  //UNITS:
  function delete_unit_files(){
      
      $iConsecutivo = $_POST['iConsecutivoFile'];
      $error = '0';   
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      #borrar archivos de unidades si un id de unidad asignado
      $query = "DELETE FROM cb_unidad_files WHERE iConsecutivo = '$iConsecutivo'"; 
      if($conexion->query($query)){$transaccion_exitosa = true;}else{$transaccion_exitosa = false;}

      if($transaccion_exitosa){
        $conexion->commit();
        $conexion->close();
        $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>
                The files has been deleted succesfully!</p>';
      }else{
        $conexion->rollback();
        $conexion->close();
        $msj = "A general system error ocurred : internal error";
        $error = "1";
      }
        
      $response = array("msj"=>"$msj","error"=>"$error");   
      echo json_encode($response);
  }  
  function cargar_polizas(){
      include("cn_usuarios.php");
      $company = $_SESSION['company'];
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $error = '0';
      $sql = "SELECT A.iConsecutivo, sNumeroPoliza, sName, sDescripcion, D.iConsecutivo AS TipoPoliza ".
             "FROM ct_polizas A ".
             "LEFT JOIN ct_brokers C ON A.iConsecutivoBrokers = C.iConsecutivo ".
             "LEFT JOIN ct_tipo_poliza D ON A.iTipoPoliza = D.iConsecutivo ".
             "WHERE iConsecutivoCompania = '".$company."' ".
             "AND  A.iDeleted = '0' AND dFechaCaducidad >= CURDATE() AND (D.iConsecutivo = '1' OR D.iConsecutivo = '3' OR D.iConsecutivo = '5' OR D.iConsecutivo = '2') ".
             "ORDER BY sNumeroPoliza ASC";  
      $result = $conexion->query($sql);
      $rows = $result->num_rows;
      
      if($rows > 0) { 
            $checkpolicies = "<table style=\"width:100%;margin:0 auto;\">";  
            $count = 1; 
            while ($items = $result->fetch_assoc()) {
               if($count == 1) $checkpolicies .= "<tr>";
               if($items["sNumeroPoliza"] != ""){
                    $spanstyle = "style=\"font-size: 10px;padding-left: 24px;position: relative;top:-3px;font-weight: normal;display: -webkit-inline-box;\"";
                     if($items['TipoPoliza'] == 1){
                         $valida_pd = '1';
                         $checkpolicies .= "<td style=\"width:25%\"><input class=\"sNumPolizas PD\" type=\"checkbox\" name=\"sNumPolizas\" checked=\"checked\" ".
                                           "value=\"".$items['iConsecutivo']."/".$items['sNumeroPoliza']."/".$items['TipoPoliza']."\" onchange=\"if($(this).prop('checked')){\$('#frm_unit_add #pd_information').show();}else{\$('#frm_unit_add #pd_information').hide();\$('#frm_unit_add #iPDAmount').val('');}\">".
                                           "<label class=\"check-label\">".$items['sNumeroPoliza']."<br><span $spanstyle> ".$items['sDescripcion']."</span></label></td>"; 
                     }else{
                          $checkpolicies .= "<td style=\"width:25%\"><input class=\"sNumPolizas\" type=\"checkbox\" name=\"sNumPolizas\" checked=\"checked\" value=\"".$items['iConsecutivo']."/".$items['sNumeroPoliza']."/".$items['TipoPoliza']."\">".
                                            "<label class=\"check-label\">".$items['sNumeroPoliza']."<br><span $spanstyle>".$items['sDescripcion']."</span></label></td>"; 
                     }
                     
                 }
                 $count ++;
                 if($count > 4) {$checkpolicies .= "</tr>";$count = 1;}
                    
            }  
            $checkpolicies .= "</table>";                                                                                                                                                                      
        }else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";}
        $conexion->rollback();
        $conexion->close();
        $response = array("mensaje"=>"$mensaje","error"=>"$error","valida_pd"=>"$valida_pd","checkpolicies"=>"$checkpolicies");   
        echo json_encode($response);
  } 
  function unidad_guardar(){
      
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);
      $company             = $_SESSION['company']; 
      $error               = '0';  
      $mensaje             = "";                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $valores             = array();
      $campos              = array();
      $_POST['sVIN']       = strtoupper(trim($_POST['sVIN']));  
      
      #1 - Revisamos si se esta editando o es nuevo registro por medio del id:
      if($_POST['iConsecutivo'] != ""){
         #UPDATE
         foreach($_POST as $campo => $valor){
            if($campo != "accion" && $campo != "iConsecutivo" && $campo != "eModoIngreso" && $campo != "sVIN"){
                if($valor != ""){array_push($valores,"$campo='".trim($valor)."'");}
            }
         }
        
         //actualizamos...
         array_push($valores ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
         array_push($valores,"sIP='".$_SERVER['REMOTE_ADDR']."'");
         array_push($valores ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'"); 
         array_push($valores ,"iDeleted='0'"); //la marcamos como no eliminada del catalogo por si lo esta...
         $sql = " UPDATE ct_unidades SET ".implode(",",$valores)." WHERE iConsecutivo = '".$_POST['iConsecutivo']."' AND iConsecutivoCompania = '$company'";
         
         if($conexion->query($sql)){$iConsecutivoUnidad = $_POST['iConsecutivo'];}
         else{
            $transaccion_exitosa = false; 
            $mensaje = "The unit data was not saved properly, please try again.";  
            $error = '1'; 
         }
      }
      else{
         #INSERT 
         // 2- Verificamos que no exista una unidad con el mismo VIN y polizas activadas.
         $query  = "SELECT iConsecutivo FROM ct_unidades WHERE iConsecutivoCompania = '$company' AND sVIN = '".$_POST['sVIN']."'";
         $result = $conexion->query($query);
         $rows   = $result->num_rows; 
         
         if($rows > 0){
             //3 - Si existe un registro:
            $unidad = $result->fetch_assoc();
            
            //4 - Si ya exite pero no tiene polizas asignadas, la actualizamos:
            foreach($_POST as $campo => $valor){
                if($campo != "accion" && $campo != "iConsecutivo" && $campo != "eModoIngreso" && $campo != "sVIN"){
                    if($valor != ""){array_push($valores,"$campo='".trim($valor)."'");}
                }
            }
                
            //actualizamos...
            array_push($valores ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
            array_push($valores ,"sIP='".$_SERVER['REMOTE_ADDR']."'");
            array_push($valores ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'"); 
            
            $sql = " UPDATE ct_unidades SET ".implode(",",$valores)." WHERE iConsecutivo = '".$unidad['iConsecutivo']."' AND iConsecutivoCompania = '$company'";
            if($conexion->query($sql)){ $iConsecutivoUnidad = $unidad['iConsecutivo'];}
            else{
                $transaccion_exitosa = false; 
                $mensaje             = "The unit data was not saved properly, please try again.";  
                $error               = '1'; 
            }
                    
            
         }
         else{
            foreach($_POST as $campo => $valor){
                if($campo != "accion" && $campo != "iConsecutivo"){if($valor != ""){array_push($campos,$campo); array_push($valores,strtoupper(trim($valor)));}}
            } 
            array_push($campos ,"iConsecutivoCompania");
            array_push($valores,$company);
            array_push($campos ,"dFechaIngreso");
            array_push($valores,date("Y-m-d H:i:s"));
            array_push($campos ,"sIP");
            array_push($valores,$_SERVER['REMOTE_ADDR']);
            array_push($campos ,"sUsuarioIngreso");
            array_push($valores,$_SESSION['usuario_actual']); 
            
            $sql = "INSERT INTO ct_unidades (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')";
            $conexion->query($sql);
            
            if($conexion->affected_rows < 1){ 
                $transaccion_exitosa = false; 
                $mensaje = "The unit data was not saved properly, please try again.";  
                $error = '1';
            }
            else{$iConsecutivoUnidad = $conexion->insert_id;}
         }        
      
      }
      
      if($transaccion_exitosa){
        $conexion->commit();
        $conexion->close();
      }else{
        $conexion->rollback();
        $conexion->close();
        $error = "1";
      }
      $response = array("error"=>"$error","msj"=>"$mensaje",'iConsecutivoUnidad' => "$iConsecutivoUnidad");
      echo json_encode($response);   
  }
  function unidad_actualiza_archivos(){
       //Conexion:
      include("cn_usuarios.php"); 
      $company = $_SESSION['company']; 
      $conexion->autocommit(FALSE);
      $error = "0";
      $mensaje = "";
      $sql = "UPDATE cb_unidad_files SET iConsecutivoUnidad ='".$_POST['iConsecutivoUnidad']."', dFechaActualizacion = '".date("Y-m-d H:i:s")."',sUsuarioActualizacion='".$_SESSION['usuario_actual']."',sIP = '".$_SERVER['REMOTE_ADDR']."' ".
             "WHERE iConsecutivo = '".$_POST['iConsecutivo']."'";
      $conexion->query($sql);
      
      if($conexion->query($sql)){
          $mensaje = "The data was updated successfully.";
          $conexion->commit();
          $conexion->close();
      }
      else{
          $mensaje = "The endorsement data was not updated properly, please try again.";
          $error = '1';
          $conexion->rollback();
          $conexion->close();
      }

      $response = array("error"=>"$error","mensaje"=>"$mensaje");
      echo json_encode($response); 
      
  }
  function unidad_cargar(){

      #Err flags:
      $error = '0';
      $msj   = "";
      #Variables
      $fields   = "";
      $clave    = trim($_POST['iConsecutivoUnidad']);
      $idEndoso = trim($_POST['iConsecutivoEndoso']);
      $domroot  = $_POST['domroot']; 
      
      #Function Begin
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);
      $sql    = "SELECT A.iConsecutivoUnidad, A.sVIN, A.iConsecutivoRadio, A.iTotalPremiumPD AS iPDAmount, A.eAccion, B.iModelo, B.iYear, B.sTipo ".  
                "FROM      cb_endoso_unidad AS A ".
                "LEFT JOIN ct_unidades      AS B ON A.iConsecutivoUnidad = B.iConsecutivo ". 
                "WHERE A.iConsecutivoEndoso = '$idEndoso' AND A.iConsecutivoUnidad='$clave'";
      $result = $conexion->query($sql);
      $items  = $result->num_rows; 
      if ($items > 0) {
            
          $data    = $result->fetch_assoc();
          $llaves  = array_keys($data);
          $datos   = $data; 
          
          $data['eAccion'] == 'ADD' || $data['eAccion'] == 'ADDSWAP' ? $form = '#frm_unit_add' : $form = '#frm_unit_delete';
            
          foreach($datos as $i => $b){ 
            $fields .= "\$('$domroot $form [id=".$i."]').val('".$datos[$i]."');";   
          }  
            
      }else{$error = "1"; $msj = "Error to data query, please try again later.";}
      $conexion->rollback();
      $conexion->close(); 
      
      $response = array("msj"=>"$msj", "error"=>"$error", "fields"=>"$fields",'accion'=>$data['eAccion']);   
      echo json_encode($response); 
     
  }
  
  //DRIVERS:
  function delete_driver_files(){
      
      $iConsecutivo = $_POST['iConsecutivoFile'];
      $error = '0';   
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      #borrar archivos de unidades si un id de unidad asignado
      $query = "DELETE FROM cb_operador_files WHERE iConsecutivo = '$iConsecutivo'"; 
      if($conexion->query($query)){$transaccion_exitosa = true;}else{$transaccion_exitosa = false;}

      if($transaccion_exitosa){
        $conexion->commit();
        $conexion->close();
        $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>
                The files has been deleted succesfully!</p>';
      }else{
        $conexion->rollback();
        $conexion->close();
        $msj = "A general system error ocurred : internal error";
        $error = "1";
      }
        
      $response = array("msj"=>"$msj","error"=>"$error");   
      echo json_encode($response);
  } 
  function driver_actualiza_archivos(){
      //Conexion:
      include("cn_usuarios.php"); 
      $company = $_SESSION['company']; 
      $conexion->autocommit(FALSE);
      $error = "0";
      $mensaje = "";
      $iConsecutivosFiles = explode('|',$_POST['iConsecutivosFiles']);
      $total = count($iConsecutivosFiles);
      $transaccion_exitosa = true;
      
      for($x = 0; $x < $total ; $x ++){
          
          $sql = "UPDATE cb_operador_files SET iConsecutivoOperador ='".$_POST['iConsecutivoOperador']."', dFechaActualizacion = '".date("Y-m-d H:i:s")."',".
                 "sUsuarioActualizacion='".$_SESSION['usuario_actual']."',sIP = '".$_SERVER['REMOTE_ADDR']."' ".
                 "WHERE iConsecutivo = '".$iConsecutivosFiles[$x]."'";
          if(!($conexion->query($sql))){$transaccion_exitosa = false;}
          
      }

      if($transaccion_exitosa){
          $mensaje = "The data was updated successfully.";
          $conexion->commit();
          $conexion->close();
      }
      else{
          $mensaje = "The endorsement data was not updated properly, please try again.";
          $error = '1';
          $conexion->rollback();
          $conexion->close();
      }

      $response = array("error"=>"$error","mensaje"=>"$mensaje");
      echo json_encode($response);    
  }
  function driver_guardar(){
          //Conexion:
          include("cn_usuarios.php"); 
          $company = $_SESSION['company']; 
          $conexion->autocommit(FALSE);
          $error = '0';  
          $mensaje = "";                                                                                                                                                                                                                                      
          $transaccion_exitosa = true;
          $valores = array();
          $campos  = array();
          
          //FORMATEANDO CAMPOS:
          $_POST['sNombre']      = strtoupper(trim($_POST['sNombre']));
          $_POST['iNumLicencia'] = strtoupper(trim($_POST['iNumLicencia'])); 
          $_POST['dFechaNacimiento'] = format_date($_POST['dFechaNacimiento']); 
          $_POST['dFechaExpiracionLicencia'] = format_date($_POST['dFechaExpiracionLicencia']);
           
  
          
          #1 - Revisamos si se esta editando o es nuevo registro por medio del id:
          if($_POST['iConsecutivo'] != ""){
             #UPDATE
             foreach($_POST as $campo => $valor){
                if($campo != "accion" && $campo != "iConsecutivo" && $campo != "eModoIngreso" && $campo != "iNumLicencia"){array_push($valores,"$campo='".trim($valor)."'");}
             }
            
             //actualizamos...
             array_push($valores ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
             array_push($valores ,"sIP='".$_SERVER['REMOTE_ADDR']."'");
             array_push($valores ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'"); 
             $sql = " UPDATE ct_operadores SET ".implode(",",$valores).
                    " WHERE iConsecutivo = '".$_POST['iConsecutivo']."' AND iConsecutivoCompania = '$company'";
             if($conexion->query($sql)){ 
                $iConsecutivoRegistro = $_POST['iConsecutivo']; 
             }else{
                $transaccion_exitosa = false; 
                $mensaje = "The unit data was not saved properly, please try again.";  
                $error = '1'; 
             }
          }
          else{
             #INSERT 
             // 2- Verificamos que no exista una unidad con el mismo VIN y polizas activadas.
             $query = "SELECT iConsecutivo, inPoliza FROM ct_operadores ".
                      "WHERE  iConsecutivoCompania = '$company' AND iNumLicencia = '".$_POST['iNumLicencia']."'";
             $result = $conexion->query($query);
             $rows = $result->num_rows; 
             
             if($rows > 0){
                 //3 - Si existe un registro revisamos si tiene polizas asignadas:
                $driver = $result->fetch_assoc();
                if($driver['inPoliza'] == '1'){
                    $error = "1";
                    $mensaje = "The driver that you tried to add is already exist in the system and its in one or more of your policies. Please verify it.";
                }else{
                    //4 - Si ya exite pero no tiene polizas asignadas, la actualizamos:
                    foreach($_POST as $campo => $valor){
                        if($campo != "accion" && $campo != "iConsecutivo" && $campo != "eModoIngreso" && $campo != "iNumLicencia"){array_push($valores,"$campo='".trim($valor)."'");}
                    }
                    
                    //actualizamos...
                    array_push($valores,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
                    array_push($valores,"sIP='".$_SERVER['REMOTE_ADDR']."'");
                    array_push($valores,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'"); 
             
                    $sql = " UPDATE ct_operadores SET ".implode(",",$valores).
                           " WHERE iConsecutivo = '".$driver['iConsecutivo']."' AND iConsecutivoCompania = '$company'";
                           
                    if($conexion->query($sql)){$iConsecutivoRegistro = $driver['iConsecutivo'];}
                    else{
                       $transaccion_exitosa = false; 
                       $mensaje = "The unit data was not saved properly, please try again.";  
                       $error = '1'; 
                    }
                        
                }
             }else{
                foreach($_POST as $campo => $valor){
                    if($campo != "accion" && $campo != "iConsecutivo"){array_push($campos,$campo); array_push($valores,strtoupper(trim($valor)));}
                } 
                array_push($campos ,"iConsecutivoCompania");
                array_push($valores,$company);
                array_push($campos ,"dFechaIngreso");
                array_push($valores,date("Y-m-d H:i:s"));
                array_push($campos ,"sIP");
                array_push($valores,$_SERVER['REMOTE_ADDR']);
                array_push($campos ,"sUsuarioIngreso");
                array_push($valores,$_SESSION['usuario_actual']); 
                
                $sql = "INSERT INTO ct_operadores (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')";
                $conexion->query($sql);
                
                if($conexion->affected_rows < 1){ 
                    $transaccion_exitosa = false; 
                    $mensaje = "The unit data was not saved properly, please try again.";  
                    $error = '1';
                    
                }else{$iConsecutivoRegistro = $conexion->insert_id;}
             }        
          
          }
          
          if($transaccion_exitosa){
            $conexion->commit();
            $conexion->close();
          }else{
            $conexion->rollback();
            $conexion->close();
            $error = "1";
          }
          $response = array("error"=>"$error","msj"=>"$mensaje",'iConsecutivoDriver' => "$iConsecutivoRegistro");
          echo json_encode($response);       
  }
  function driver_cargar(){
      #Err flags:
      $error = '0';
      $msj   = "";
      #Variables
      $fields   = "";
      $clave    = trim($_POST['iConsecutivoOperador']);
      $idEndoso = trim($_POST['iConsecutivoEndoso']);
      $domroot  = $_POST['domroot']; 
      
      #Function Begin
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);
      $sql    = "SELECT A.iConsecutivoOperador, A.eAccion, A.sNombre, A.iNumLicencia, DATE_FORMAT(B.dFechaNacimiento,'%m/%d/%Y') AS dFechaNacimiento, DATE_FORMAT(B.dFechaExpiracionLicencia,'%m/%d/%Y') AS dFechaExpiracionLicencia, B.iExperienciaYear, B.eTipoLicencia ".  
                "FROM      cb_endoso_operador AS A ".
                "LEFT JOIN ct_operadores      AS B ON A.iConsecutivoOperador = B.iConsecutivo ". 
                "WHERE A.iConsecutivoEndoso = '$idEndoso' AND A.iConsecutivoOperador='$clave'";
      $result = $conexion->query($sql);
      $items  = $result->num_rows; 
      if ($items > 0) {
            
          $data    = $result->fetch_assoc();
          $llaves  = array_keys($data);
          $datos   = $data; 
          
          $data['eAccion'] == 'ADD' || $data['eAccion'] == 'ADDSWAP' ? $form = '#frm_driver_add' : $form = '#frm_driver_delete';
            
          foreach($datos as $i => $b){ 
            $fields .= "\$('$domroot $form [id=".$i."]').val('".$datos[$i]."');";   
          }  
          
          /*if($data['eAccion'] == 'DELETE' || $data['eAccion'] == 'DELETESWAP'){
              $sql = "SELECT sNombreArchivo, sTipoArchivo, eArchivo='$eArchivo', ".
                     "FROM cb_operador_files WHERE iConsecutivo ='".$_POST['iConsecutivo']."'"; 
          } */
          
            
      }else{$error = "1"; $msj = "Error to data query, please try again later.";}
      $conexion->rollback();
      $conexion->close(); 
      
      $response = array("msj"=>"$msj", "error"=>"$error", "fields"=>"$fields",'accion'=>$data['eAccion']);   
      echo json_encode($response);     
  }
  
  function guardar_endoso(){
    
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE); 
      $company = $_SESSION['company'];                                                                                                                                                                                                                                     
      $transaccion_exitosa = true;
      $error   = 0;  
      $mensaje = "";
      $valores = array();
      $campos  = array();
      $eAccion = trim($_POST['eAccion']);
      $ErrMySQL= "";
      
      //CONSULTAR DATOS DE LA UNIDAD / OPERADOR:
      if($_POST['iConsecutivoUnidad'] != '' && $_POST['iConsecutivoTipoEndoso'] == '1'){
        $query        = "SELECT sVIN, iConsecutivoRadio, iTotalPremiumPD, iValue FROM ct_unidades WHERE iConsecutivo='".$_POST['iConsecutivoUnidad']."' AND iConsecutivoCompania='".$company."'";    
        $result       = $conexion->query($query);
        $Detalle = $result->fetch_assoc();
      }
      else if($_POST['iConsecutivoOperador'] != '' && $_POST['iConsecutivoTipoEndoso'] == '2'){
        $query        = "SELECT sNombre, iNumLicencia FROM ct_operadores WHERE iConsecutivo='".$_POST['iConsecutivoOperador']."' AND iConsecutivoCompania='".$company."'";    
        $result       = $conexion->query($query);
        $Detalle = $result->fetch_assoc();    
      }
      
      #------------------- DATOS GENERALES DEL ENDOSO -------------------------------#
      //UPDATE
      if($_POST['edit_mode'] == 'true'){
          foreach($_POST as $campo => $valor){
              if($campo != "accion" && $campo != "iConsecutivo" && $campo != "edit_mode" && $campo != 'iConsecutivoOperador' && $campo != 'iConsecutivoUnidad' && $campo != 'edit_detalle' && $campo != 'eAccion' && $campo != 'iPDAmount'){
                /*if($campo == "iConsecutivoOperador" && $eAccion == 'D'){
                    if(!(strpos($valor,"|"))){
                        array_push($valores,"$campo=NULL"); //Borrar consecutivo si existe un anterior...
                        $campo = "sNombreOperador"; $valor = strtoupper(utf8_decode(trim($valor))); 
                    }
                    else{$valor = explode("|",$valor);$valor = $valor[0];}
                }
                else if($campo == "iConsecutivoUnidad" && $eAccion == 'D'){
                    if(!(strpos($valor,"|"))){
                        array_push($valores,"$campo=NULL"); //Borrar consecutivo si existe un anterior...
                        $campo = "sVINUnidad"; $valor = strtoupper(utf8_decode(trim($valor))); 
                    }
                    else{$valor = explode("|",$valor);$valor = $valor[0];}
                }*/ 
                array_push($valores,"$campo='".trim($valor)."'");
              
              }
          }  
          
          array_push($valores,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
          array_push($valores,"sIP='".$_SERVER['REMOTE_ADDR']."'");
          array_push($valores,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'");
          array_push($valores,"iEndosoMultiple='1'");
          //Datos del Solicitante:
          array_push($valores,"sSolicitanteNombre='".$_SESSION['usuario_actual']."'");
          array_push($valores,"sSolicitanteEmail='THROUGH THE SYSTEM'");
          
          $sql     = "UPDATE cb_endoso SET ".implode(",",$valores)." WHERE iConsecutivo = '".$_POST['iConsecutivo']."'"; 
          $success = $conexion->query($sql);
          
          if(!($success)){$mensaje = "The endorsement data was not updated properly, please try again.";$error = 1;$transaccion_exitosa = false;$ErrMySQL =  $conexion->error;}
          else{$iConsecutivoEndoso = $_POST['iConsecutivo'];}
      }
      //INSERT
      else{
          
          foreach($_POST as $campo => $valor){
              if($campo != "accion" && $campo != "iConsecutivo" && $campo != "edit_mode" && $campo != 'iConsecutivoOperador' && $campo != 'iConsecutivoUnidad' && $campo != 'edit_detalle' && $campo != 'eAccion' && $campo != 'iPDAmount'){
                  /*if($campo == "iConsecutivoOperador" && $eAccion == 'D'){
                    if(!(strpos($valor,"|"))){$campo = "sNombreOperador"; $valor = strtoupper(utf8_decode(trim($valor)));}
                    else{$valor = explode("|",$valor);$valor = $valor[0];} 
                  }
                  else if($campo == "iConsecutivoUnidad" && $eAccion == 'D'){
                    if(!(strpos($valor,"|"))){$campo = "sVINUnidad"; $valor = strtoupper(utf8_decode(trim($valor)));}
                    else{$valor = explode("|",$valor);$valor = $valor[0];} 
                  } */
                  array_push($campos,$campo); array_push($valores,strtoupper(trim($valor)));
              }
          }   
          array_push($campos ,"iConsecutivoCompania"); array_push($valores,$company);
          array_push($campos ,"eStatus");              array_push($valores,'E');
          array_push($campos ,"dFechaIngreso");        array_push($valores ,date("Y-m-d H:i:s"));
          array_push($campos ,"sIP");                  array_push($valores ,$_SERVER['REMOTE_ADDR']);
          array_push($campos ,"sUsuarioIngreso");      array_push($valores ,$_SESSION['usuario_actual']);
          array_push($campos ,"iEndosoMultiple");      array_push($valores ,'1');
          //Datos del Solicitante:
          array_push($campos ,"sSolicitanteNombre");   array_push($valores ,$_SESSION['usuario_actual']);
          array_push($campos ,"sSolicitanteEmail");    array_push($valores ,'THROUGH THE SYSTEM');
          
          $sql     = "INSERT INTO cb_endoso (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')"; 
          $success = $conexion->query($sql); 
          if(!($success)){$ErrMySQL =  $conexion->error; $error = 1; $transaccion_exitosa = false;$mensaje = "The data of endorsement was not saved properly, please try again.";}
          else{$iConsecutivoEndoso = $conexion->insert_id;;}
    
      }
     
      #---------------- DETALLE Y POLIZAS ------------------------#
      if($error == 0 && $transaccion_exitosa){
        
        #UNIDAD DETALLE
        if($_POST['iConsecutivoUnidad'] != '' && $_POST['iConsecutivoTipoEndoso'] == '1'){
            $valores = array();
            $campos  = array();
            if($_POST['edit_detalle'] == 'true'){
                
                foreach($Detalle as $campo => $valor){
                  if($valor != ""){array_push($valores,"$campo='".trim($valor)."'");}
                } 
                
                array_push($valores,"eAccion='$eAccion'");
                //array_push($valores,"iTotalPremiumPD='".$_POST['iPDAmount']."'");
                 
                $query  = "UPDATE cb_endoso_unidad SET ".implode(",",$valores)." WHERE iConsecutivoEndoso='".$iConsecutivoEndoso."' AND iConsecutivoUnidad='".$_POST['iConsecutivoUnidad']."'";   
                $success= $conexion->query($query);
                if(!($success)){$error = 1; $transaccion_exitosa = false; $ErrMySQL =  $conexion->error; $mensaje = "The endorsement vehicle detail was not updated properly, please try again.";}
                 
            }
            else{
                //Revisar si ya existe:
                $query = "SELECT COUNT(iConsecutivoEndoso) AS total FROM cb_endoso_unidad WHERE iConsecutivoEndoso='".$iConsecutivoEndoso."' AND iConsecutivoUnidad='".$_POST['iConsecutivoUnidad']."'";
                $result= $conexion->query($query);
                $valid = $result->fetch_assoc();
                if($valid['total'] != 0 ){$error = 1; $ErrMySQL =  $conexion->error; $transaccion_exitosa = false; $mensaje = "The vehicle that you tried to add it's already exists on the endorsement, please edit it or save a new.";}
                else{
                    
                    foreach($Detalle as $campo => $valor){
                        if($valor != ""){
                            array_push($campos,$campo); array_push($valores,trim($valor));
                        }
                    } 
                    array_push($campos ,"iConsecutivoEndoso"); array_push($valores,$iConsecutivoEndoso);
                    array_push($campos ,"iConsecutivoUnidad"); array_push($valores,$_POST['iConsecutivoUnidad']);
                    array_push($campos ,"eAccion");            array_push($valores,$eAccion);
                    
                    $query  = "INSERT INTO cb_endoso_unidad (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')"; 
                    $success= $conexion->query($query);
                    if(!($success)){$error = 1; $transaccion_exitosa = false; $mensaje = "The endorsement vehicle detail was not saved properly, please try again.";$ErrMySQL = $conexion->error;}
                }
            }      
        }
        #OPERADOR DETALLE
        else if($_POST['iConsecutivoOperador'] != '' && $_POST['iConsecutivoTipoEndoso'] == '2'){
            $valores = array();
            $campos  = array();
            if($_POST['edit_detalle'] == 'true'){
                foreach($Detalle as $campo => $valor){
                  if($valor != ""){array_push($valores,"$campo='".trim($valor)."'");}
                }  
                array_push($valores,"eAccion='$eAccion'");
                $query  = "UPDATE cb_endoso_operador SET ".implode(",",$valores)." WHERE iConsecutivoEndoso='".$iConsecutivoEndoso."' AND iConsecutivoOperador='".$_POST['iConsecutivoOperador']."'";   
                $success= $conexion->query($query);
                if(!($success)){$error = 1; $transaccion_exitosa = false; $mensaje = "The endorsement driver detail was not updated properly, please try again.";}    
            }
            else{
                //Revisar si ya existe:
                $query = "SELECT COUNT(iConsecutivoEndoso) AS total FROM cb_endoso_operador WHERE iConsecutivoEndoso='".$iConsecutivoEndoso."' AND iConsecutivoOperador='".$_POST['iConsecutivoOperador']."'";
                $result= $conexion->query($query);
                $valid = $result->fetch_assoc();
                if($valid['total'] != 0 ){$error = 1; $ErrMySQL =  $conexion->error; $transaccion_exitosa = false; $mensaje = "The vehicle that you tried to add it's already exists on the endorsement, please edit it or save a new.";}
                else{
                    
                    foreach($Detalle as $campo => $valor){
                        if($valor != ""){
                            array_push($campos,$campo); array_push($valores,trim($valor));
                        }
                    } 
                    array_push($campos ,"iConsecutivoEndoso");   array_push($valores,$iConsecutivoEndoso);
                    array_push($campos ,"iConsecutivoOperador"); array_push($valores,$_POST['iConsecutivoOperador']);
                    array_push($campos ,"eAccion");              array_push($valores,$eAccion);
                    
                    $query = "INSERT INTO cb_endoso_operador (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')"; 
                    $success= $conexion->query($query);
                    if(!($success)){$error = 1; $ErrMySQL =  $conexion->error; $transaccion_exitosa = false; $mensaje = "The endorsement vehicle detail was not saved properly, please try again.";}
                }    
            }
            
        } 
        
        #POLIZAS
        $polizas = explode("|",trim($_POST['sNumPolizas']));
        $count   = count($polizas);
        $query   = "DELETE FROM cb_endoso_estatus WHERE iConsecutivoEndoso='$iConsecutivoEndoso' ";
        $success = $conexion->query($query);
        
        for($x=0;$x<$count;$x++){
            $poliza            = explode("/",$polizas[$x]);
            $iConsecutivoPoliza= $poliza[0];
            
            $query  = "INSERT INTO cb_endoso_estatus (iConsecutivoEndoso,iConsecutivoPoliza,eStatus,sIP,sUsuarioIngreso,dFechaIngreso) ".
                      "VALUES('$iConsecutivoEndoso','$iConsecutivoPoliza','E','".$_SERVER['REMOTE_ADDR']."','".$_SESSION['usuario_actual']."','".date("Y-m-d H:i:s")."')";
            $success= $conexion->query($query);
            if(!($success)){$error = 1; $transaccion_exitosa = false; $ErrMySQL =  $conexion->error; $mensaje = "The endorsement policies detail was not saved properly, please try again.";}
        }
      }
      
      if($transaccion_exitosa || $error == 0){$conexion->commit();$mensaje = "The data has been saved successfully!";}
      else{$conexion->rollback();}
      
      $conexion->close();
      $response = array("error"=>"$error","msj"=>"$mensaje","error_mysqli"=>$ErrMySQL,'iConsecutivo'=>$iConsecutivoEndoso);
      echo json_encode($response); 
  }
  function cargar_endoso(){
      $error   = '0';
      $msj     = "";
      $fields  = "";
      $clave   = trim($_POST['clave']);
      $company = $_SESSION['company'];
      $domroot = $_POST['domroot'];
      
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                 
      $sql    = "SELECT iConsecutivo, iConsecutivoTipoEndoso, eStatus, iReeferYear,iTrailerExchange, iPDAmount, iPDApply, iConsecutivoOperador, iConsecutivoUnidad, eAccion, sComentarios, sNumPolizas,sNombreOperador, sVINUnidad, iEndosoMultiple ".  
                "FROM cb_endoso WHERE iConsecutivo = '".$clave."'";
      $result = $conexion->query($sql);
      $items  = $result->num_rows;
      
      if ($items > 0){ 
          $data    = $result->fetch_assoc();
          $llaves  = array_keys($data);
          $datos   = $data;
          
          //Datos Generales:
          foreach($datos as $i => $b){
            if($i == 'iConsecutivo' || $i == 'iEndosoMultiple'){
                $fields .= "\$('#$domroot #frm_general_information :input[name=".$i."]').val('".$datos[$i]."');";    
            }    
          }
       
          //ENDOSO EL MULTIPLE:
          if($data['iEndosoMultiple'] == 1){
                   
          }
          // ENDOSO NO MULTIPLE:
          else{
              
            $data['eAccion'] == 'A' ? $action = 'ADD' : $action = 'DELETE';
          
            #DRIVERS 
            if($data['iConsecutivoTipoEndoso']== '2'){
                
                // Si es ADD
                if($data['eAccion'] == 'A' && $data['iConsecutivoOperador']!= ''){
                    //consultamos los datos del operador.  
                    $sql    = "SELECT iConsecutivo, sNombre,DATE_FORMAT(dFechaNacimiento,'%m/%d/%Y') AS dFechaNacimiento, ".
                              "DATE_FORMAT(dFechaExpiracionLicencia,'%m/%d/%Y') AS dFechaExpiracionLicencia,iExperienciaYear,iNumLicencia,dFechaContratacion, ".
                              "iEntidad,eTipoLicencia FROM ct_operadores ".
                              "WHERE iConsecutivo = '".$data['iConsecutivoOperador']."'";
                    $result = $conexion->query($sql);
                    $rows   = $result->num_rows;
                    if($rows > 0) {
                        $data_driver = $result->fetch_assoc();
                        $llaves      = array_keys($data_driver);
                        $datos       = $data_driver; 
                        
                        foreach($datos as $i => $b){ $fields .= "\$('#$domroot #frm_driver_add :input[id=".$i."]').val('".$datos[$i]."');";}
                        
                        //Get files from driver:
                        $sql    = "SELECT iConsecutivo, sNombreArchivo, eArchivo FROM cb_operador_files WHERE  iConsecutivoOperador = '".$data['iConsecutivoOperador']."'";
                        $result = $conexion->query($sql);
                        
                        while ($files = $result->fetch_assoc()) {
                             if($files["eArchivo"] == 'LICENSE'){
                                $fields .= "\$('#$domroot #frm_driver_add :input[id=txtsLicenciaPDF]').val('".$files['sNombreArchivo']."');";
                                $fields .= "\$('#$domroot #frm_driver_add :input[id=iConsecutivoLicenciaPDF]').val('".$files['iConsecutivo']."');";  
                             }
                             if($files["eArchivo"] == 'MVR'){
                                $fields .= "\$('#$domroot #frm_driver_add :input[id=txtsMVRPDF]').val('".$files['sNombreArchivo']."');";
                                $fields .= "\$('#$domroot #frm_driver_add :input[id=iConsecutivoMVRPDF]').val('".$files['iConsecutivo']."');"; 
                             }
                             if($files["eArchivo"] == 'LONGTERM'){
                                $fields .= "\$('#$domroot #frm_driver_add :input[id=txtsLTMPDF]').val('".$files['sNombreArchivo']."');";
                                $fields .= "\$('#$domroot #frm_driver_add :input[id=iConsecutivoLTMPDF]').val('".$files['iConsecutivo']."');"; 
                             }
                             if($files["eArchivo"] == 'PSP'){
                                $fields .= "\$('#$domroot #frm_driver_add :input[id=txtsPSPFile]').val('".$files['sNombreArchivo']."');";
                                $fields .= "\$('#$domroot #frm_driver_add :input[id=iConsecutivoPSPFile]').val('".$files['iConsecutivo']."');"; 
                             }
                         }    
                    }
                }
                // - Si es DELETE 
                else if($data['eAccion'] == 'D' && $data['iConsecutivoOperador']!= ''){
                   
                   $query  = "SELECT sNombre,iNumLicencia FROM ct_operadores WHERE iConsecutivo = '".$data['iConsecutivoOperador']."'"; 
                   $result = $conexion->query($sql);
                   $driver = $result->fetch_assoc();
                   $fields.= "\$('#$domroot #frm_driver_delete :input[id=sDriver]').val('".$data['iConsecutivoOperador']." | ".utf8_decode($driver['sNombre'])."');";
                }
                // - Si es DELETE pero no esta guardado el driver en el catalogo: (opcion ya obsoleta...)
                else if($data['eAccion'] == 'D' && $data['sNombreOperador'] != ""){
                  $fields .= "\$('#$domroot #frm_driver_delete :input[id=sDriver]').val('".utf8_decode($data['sNombreOperador'])."');";  
                }
            }
            #VEHICLES
            else if($data['iConsecutivoTipoEndoso']== '1'){
                // Si es ADD
               if($data['eAccion'] == 'A' && $data['iConsecutivoUnidad']!= ''){
                   //Consultamos los datos de la unidad.
                   $sql    = "SELECT iConsecutivo, iConsecutivoRadio, iYear, iModelo, sVIN, sModelo, sTipo, iTotalPremiumPD, iValue FROM ct_unidades ".
                             "WHERE iConsecutivo = '".$data['iConsecutivoUnidad']."'";
                   $result = $conexion->query($sql);
                   $rows   = $result->num_rows;
                   if ($rows > 0) {
                      $data_unit = $result->fetch_assoc();
                      $llaves    = array_keys($data_unit);
                      $datos     = $data_unit;
                      foreach($datos as $i => $b){ $fields .= "\$('#$domroot #frm_unit_add :input[id=".$i."]').val('".$datos[$i]."');"; } 
                   }
                   //Consultamos id del archivo con el ue se hizo el delete:
                   $sql    = "SELECT iConsecutivo, sNombreArchivo, eArchivo FROM cb_unidad_files WHERE  iConsecutivoUnidad = '".$data['iConsecutivoUnidad']."'";
                   $result = $conexion->query($sql);
                   while ($files = $result->fetch_assoc()){
                     if($files["eArchivo"] == 'TITLE'){
                        $fields .= "\$('#$domroot #frm_unit_add :input[id=txtsTituloPDF]').val('".$files['sNombreArchivo']."');";
                        $fields .= "\$('#$domroot #frm_unit_add :input[id=iConsecutivoTituloPDF]').val('".$files['iConsecutivo']."');"; 
                     }
                   } 
                   
               }
               // Si es DELETE
               else if($data['eAccion'] == 'D' && $data['iConsecutivoUnidad']!= ''){
                   
                   $query  = "SELECT sVIN, iYear FROM ct_unidades WHERE iConsecutivo='".$data['iConsecutivoUnidad']."'";
                   $result = $conexion->query($query);
                   $unit   = $result->fetch_assoc();
                   
                   //4 - asignamos solamente el id al campo del pick list:
                   $fields .= "\$('#$domroot #frm_unit_delete :input[id=sUnitTrailer]').val('".$data['iConsecutivoUnidad']." | ".$unit['sVIN']." / ".$unit['iYear']."');";
                   
                   #CONSULTAR ARCHIVO...
                   $sql_files    = "SELECT iConsecutivo, sNombreArchivo, eArchivo FROM cb_endoso_files WHERE  iConsecutivoEndoso = '".$data['iConsecutivo']."'";
                   $result_files = $conexion->query($sql_files);
                   while ($items_files = $result_files->fetch_assoc()){
                     if($items_files["eArchivo"] != 'TITLE'){
                        $fields .= "\$('#$domroot #frm_unit_delete :input[id=DeleteCause]').val('".$items_files['eArchivo']."');";
                        $fields .= "\$('#$domroot #frm_unit_delete :input[id=txtsDAPDF]').val('".$items_files['sNombreArchivo']."');";
                        $fields .= "\$('#$domroot #frm_unit_delete :input[id=iConsecutivoDAPDF]').val('".$items_files['iConsecutivo']."');"; 
                     }
                   }  
                     
               }
               // Si es DELETE pero la unidad no esta reg. en el catalogo (opcion obsoleta...)
               else if($data['eAccion'] == 'D' && $data['sVINUnidad'] != ""){$fields .= "\$('#$domroot #frm_unit_delete :input[id=sUnitTrailer]').val('".$data['sVINUnidad']."');";}
                  
            }
          }
          
          // POLIZAS:
          $query  = "SELECT iConsecutivoPoliza, eStatus, sNumeroPoliza, iTipoPoliza ".
                    "FROM cb_endoso_estatus AS A LEFT JOIN ct_polizas AS B ON A.iConsecutivoPoliza = B.iConsecutivo WHERE iConsecutivoEndoso='".$clave."'";
          $result = $conexion->query($query);
          $rows   = $result->num_rows;
          if($rows > 0){
            while($item = $result->fetch_assoc()){
                $fields .= "\$('#$domroot #frm_general_information input[type=checkbox][value=\"".$item['iConsecutivoPoliza']."/".$item['sNumeroPoliza']."/".$item['iTipoPoliza']."\"]').prop('checked',true);";    
            }    
          }
          
          
          
          //1- recorremos el array con los datos generales del endoso:
          /*foreach($datos as $i => $b){
             //1.2- Revisamos que no sea alguno de los siguientes campos:
             if($i != 'eStatus' && $i != 'sComentarios' && $i != 'sNumPolizas' && $i != 'eAccion' && $i != 'iConsecutivoOperador' && $i != 'sNombreOperador' && $i != 'sVINUnidad' && $i != 'iConsecutivoUnidad' && $i != 'iEndosoMultiple'){ 
                   $fields .= "\$('#$domroot #frm_general_information :input[id=".$i."]').val('".$datos[$i]."');";
             }
             //1.3 - Revisamos si es la accion, esta ira por aparte.
             else if($i == 'eAccion'){
                  $action = "\$('#$domroot #frm_general_information :input[id=".$i."]').val('".$datos[$i]."');";
             }
             //1.4 - Revisamos que polizas selecciono:
             else if($i == 'sNumPolizas'){
               
               $PolizasEndoso = explode('|',$datos[$i]); 
               $tablaPolizas = "<table class=\"popup-datagrid\"><thead>"."<tr id=\"grid-head2\">"."<td class=\"etiqueta_grid\">Policy Number</td>"."<td class=\"etiqueta_grid\">Policy Type</td>"."</tr></thead><tbody>";
               for ($i = 0; $i < count($PolizasEndoso); $i++) {
                    $poliza = explode('/',$PolizasEndoso[$i]);
                    $filtro = "AND sNumeroPoliza = '".$poliza[0]."' AND iTipoPoliza = '".$poliza[1]."' "; 
                       
                       $fields .= "\$('#$domroot #policies_endorsement  :input[value=\"".$PolizasEndoso[$i]."\"]').prop(\"checked\",\"true\");";
                       
                       $policy_query = "SELECT sNumeroPoliza, D.sDescripcion ".
                                       "FROM ct_polizas A LEFT JOIN ct_tipo_poliza D ON A.iTipoPoliza = D.iConsecutivo ".
                                       "WHERE iConsecutivoCompania = '".$company."' ".
                                       "$filtro AND A.iDeleted ='0'"; 

                       $result_policy = $conexion->query($policy_query);
                       $rows_policy = $result_policy->num_rows; 
                       if($rows_policy > 0){
                           while($policies = $result_policy->fetch_assoc()){
                              $tablaPolizas .= "<tr><td>".$policies['sNumeroPoliza']."</td>".
                                               "<td>".$policies['sDescripcion']."</td></tr>"; 
                           }
                           
                       }    
               }
               
               $tablaPolizas .= "</tbody></table>";
            } 
              
          } */
        
          //Verificar si el endoso a sido Denegado:
          $endorsement_denied = "";
          if($data['eStatus'] == 'D' && $data['sComentarios'] != ''){
               $endorsement_denied = "<label>Denied Reason(s):</label></br><p>".utf8_decode($data['sComentarios'])."</p>";    
          } 
          
      }else{$error = 1; $msj = "The endorsement data was not found, please try again.";}

      $conexion->rollback();
      $conexion->close(); 
      $response = array(
        "msj"=>"$msj",
        "error"=>"$error",
        "fields"=>"$fields",
        "denied" => "$endorsement_denied",
        //"policies_table"=>"$tablaPolizas",
        "action"=>"$action",
        "EndosoMultiple" => $data['iEndosoMultiple'],
      );   
      echo json_encode($response);  
      
  }   
  function guarda_pdf_endoso(){
      
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);
      //Variables:
      $error                  = "0";                                                                                                                                                                                                                                    
      $transaccion_exitosa    = true; 
      $_POST['iConsecutivo'] != "" ? $edit_mode = true : $edit_mode = false; 
       
      //Revisamos Archivo de que modulo viene:
      if(isset($_FILES['userfile'])){
          $file        = fopen($_FILES['userfile']["tmp_name"], 'r'); 
          $fileContent = fread($file, filesize($_FILES['userfile']["tmp_name"]));
          $fileName    = $_FILES['userfile']['name'];
          $fileType    = $_FILES['userfile']['type']; 
          $fileTmpName = $_FILES['userfile']['tmp_name']; 
          $fileSize    = $_FILES['userfile']['size']; 
          $fileError   = $_FILES['userfile']['error'];
          $fileExten   = explode(".",$fileName); 
           
      }else if(isset($_FILES['file-0'])){
          $file        = fopen($_FILES['file-0']["tmp_name"], 'r'); 
          $fileContent = fread($file, filesize($_FILES['file-0']["tmp_name"]));
          $fileName    = $_FILES['file-0']['name'];
          $fileType    = $_FILES['file-0']['type']; 
          $fileTmpName = $_FILES['file-0']['tmp_name']; 
          $fileSize    = $_FILES['file-0']['size']; 
          $fileError   = $_FILES['file-0']['error'];
          $fileExten   = explode(".",$fileName);
      }else{
          $error = "1";
          $mensaje = "Error to read the file data, please try again.";
      }
      
      #REVISAMOS ERRORES:
      if($error == "0"){
          //Validando nombre del archivo sin puntos...
          if(count($fileExten) != 2){$error="1";$mensaje = "Error: Please check that the name of the file should not contain points.";}
          else{
              //Extension Valida:
              $fileExten = strtolower($fileExten[1]);
              if($fileExten != "pdf" && $fileExten != "jpg" && $fileExten != "jpeg" && $fileExten != "png" && $fileExten != "doc" && $fileExten != "docx" && $fileExten != "xlsx" && $fileExten != "xls" && $fileExten != "mp3" && $fileExten != "mp4" && $fileExten != "key" && $fileExten != "cer" && $fileExten != "zip" && $fileExten != "ppt" && $fileExten != "pptx"){
                  $error = "1"; $mensaje="Error: The file extension is not valid, please check it.";
              }else {
                  //Verificar Tamaño:
                  if($fileSize > 0  && $fileError == 0){
                      
                      $sContenido           = $conexion->real_escape_string($fileContent);
                      $eArchivo             = trim($_POST['eArchivo']); 
                      $iConsecutivoEndoso   = trim($_POST['iConsecutivoEndoso']);
                      if($eArchivo != "OTHERS"){$fileName = strtolower($eArchivo).'.'.$fileExten;} //Si la categoria existe renombramos el archivo.
                      
                      #UPDATE
                      if($edit_mode){
                         $sql = "UPDATE cb_endoso_files SET sNombreArchivo ='$fileName', sTipoArchivo ='$fileType', iTamanioArchivo ='$fileSize', ".
                                "hContenidoDocumentoDigitalizado='$sContenido', eArchivo='$eArchivo', ".
                                "dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."', sUsuarioActualizacion='".$_SESSION['usuario_actual']."'".
                                "WHERE iConsecutivo ='".trim($_POST['iConsecutivo'])."'";  
                      }
                      #INSERT
                      else{
                         $sql = "INSERT INTO cb_endoso_files (sNombreArchivo, sTipoArchivo, iTamanioArchivo, hContenidoDocumentoDigitalizado, eArchivo,iConsecutivoEndoso, dFechaIngreso, sIP, sUsuarioIngreso) ".
                                "VALUES('$fileName','$fileType','$fileSize','$sContenido','$eArchivo','$iConsecutivoEndoso','".date("Y-m-d H:i:s")."', '".$_SERVER['REMOTE_ADDR']."', '".$_SESSION['usuario_actual']."')"; 
                      }
                      
                      if($conexion->query($sql)){
                            $id_file = $conexion->insert_id; 
                            $conexion->commit();
                            $conexion->close();
                            $mensaje = "The file was uploaded successfully.";  
                      }else{
                            $conexion->rollback();
                            $conexion->close();
                            $mensaje = "A general system error ocurred : internal error";
                            $error = "1";
                      }     
                  }
                  else{
                      $error   = "1";
                      $mensaje = "Error: The file you are trying to upload is empty or corrupt, please check it and try again.";}
              }
              
          }   
      }

        
      $response = array("mensaje"=>"$mensaje","error"=>"$error", "id_file"=>"$id_file","name_file"=>"$name_file"); 
      echo json_encode($response);             
      
  } 
  function elimina_archivo_endoso(){
      
      $iConsecutivo = trim($_POST['iConsecutivo']);
      $error        = '0';   
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      #borrar archivos de unidades si un id de unidad asignado
      $query = "DELETE FROM cb_endoso_files WHERE iConsecutivo = '$iConsecutivo'"; 
      if($conexion->query($query)){$transaccion_exitosa = true;}else{$transaccion_exitosa = false;}

      if($transaccion_exitosa){
        $conexion->commit();
        $conexion->close();
        $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The files has been deleted succesfully!</p>';
      }else{
        $conexion->rollback();
        $conexion->close();
        $msj   = "A general system error ocurred : internal error";
        $error = "1";
      }
        
      $response = array("msj"=>"$msj","error"=>"$error");   
      echo json_encode($response);
      
  }    
  function endoso_actualiza_archivos(){
       //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);
       $company = $_SESSION['company']; 
      $error    = "OK";
      $sql      = "UPDATE cb_endoso_files SET iConsecutivoEndoso ='".$_POST['iConsecutivoEndoso']."', dFechaActualizacion = '".date("Y-m-d H:i:s")."',sUsuarioActualizacion='".$_SESSION['usuario_actual']."',sIP = '".$_SERVER['REMOTE_ADDR']."' ".
                  "WHERE iConsecutivo = '".$_POST['iConsecutivo']."'";
      $conexion->query($sql);
      
      if($conexion->query($sql)){$conexion->commit();$conexion->close();}
      else{$error = "The endorsement data was not updated properly, please try again.";$conexion->rollback();$conexion->close();}

      echo $error;
      
  }              

  #CATALOGOS PARA ENDOSOS:
  function get_unit_models(){
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);
      
      $sql = "SELECT iConsecutivo AS clave, sDescripcion AS descripcion 
              FROM ct_unidad_modelo ORDER BY iConsecutivo ASC";
      $result = $conexion->query($sql);
      $tipos = $result->num_rows;  
      if($tipos > 0){
        $htmlTabla .= "<option value=\"\">Select an option...</option>";      
        while ($country = $result->fetch_assoc()) {
           if($country["clave"] != ""){
                 $htmlTabla .= "<option value=\"".$country['clave']."\">".$country['descripcion']."</option>";
             }else{                             
                 $htmlTabla .="";
             }    
        }
        //$htmlTabla .= "<option value=\"OTHER\">Other model</option>";                                                                                                                                                                        
      }else {$htmlTabla .="";}
      $conexion->rollback();
      $conexion->close();
      $htmlTabla = utf8_encode($htmlTabla);  
      $response = array("mensaje"=>"$mensaje","error"=>"$error","select"=>"$htmlTabla");   
      echo json_encode($response);
  }
  function get_units(){
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);
      $company = $_SESSION['company'];
      $error   = "0";
      $mensaje = "";
      $sql     = "SELECT iConsecutivo, sVIN, sTipo, iYear ". 
                 "FROM ct_unidades WHERE iConsecutivoCompania = '$company' ". // AND inPoliza = '1'".
                 "ORDER BY iConsecutivo ASC";
      $result  = $conexion->query($sql);
      $rows    = $result->num_rows;  
      if($rows > 0){
             
        while ($items = $result->fetch_assoc()) {
           $respuesta == '' ? $respuesta .= '"'.$items["iConsecutivo"].' | '.$items["sVIN"].'/'.utf8_encode($response["iYear"]).'"' : $respuesta .= ',"'.$items["iConsecutivo"].' | '.$items["sVIN"].'/'.utf8_encode($items["iYear"]).'"';    
        }                                                                                                                                                                        
      }else {$respuesta .="";}
      $conexion->rollback();
      $conexion->close();
       
      $respuesta = "[".$respuesta."]";
      echo $respuesta;
  }
  function get_drivers(){
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);
      $company = $_SESSION['company'];
      $error = "0";
      $mensaje = "";
      $sql    = "SELECT iConsecutivo, sNombre, iNumLicencia AS descripcion2 ". 
                "FROM ct_operadores WHERE iConsecutivoCompania = '$company' ". //" AND inPoliza = '1'".
                "ORDER BY sNombre ASC";
      $result = $conexion->query($sql);
      $rows   = $result->num_rows;  
      if($rows > 0){
        //$respuesta .= "<option value=\"\">Select an option...</option>";      
        while ($items = $result->fetch_assoc()){
           //$respuesta .= "<option value=\"".$items['clave']."\">".$items['descripcion']." / ".$items['descripcion2']."</option>";
           $respuesta == '' ? $respuesta .= '"'.$items["iConsecutivo"].' | '.utf8_encode($items["sNombre"]).'"' : $respuesta .= ',"'.$items["iConsecutivo"].' | '.utf8_encode($items["sNombre"]).'"';
        }                                                                                                                                                                        
      }else {$respuesta .="";}
      $conexion->rollback();
      $conexion->close();
      $respuesta = "[".$respuesta."]";    
      echo ($respuesta);
  }
  function filtrar_polizas(){
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);
      $company = $_SESSION['company'];
      $error = "0";
      $mensaje = "";
      $iConsecutivo = $_POST['iConsecutivo'];
      $tipo = $_POST['tipo'];
      
      if($tipo == 'DRIVER'){
          $sql  = "SELECT siConsecutivosPolizas FROM ct_operadores WHERE iConsecutivoCompania = '$company' AND iConsecutivo ='$iConsecutivo'";
          $form = "frm_endorsements_driver";
      }
      else if($tipo == 'UNIT'){
          $sql  = "SELECT siConsecutivosPolizas FROM ct_unidades   WHERE iConsecutivoCompania = '$company' AND iConsecutivo ='$iConsecutivo'";
          $form = "frm_endorsements_unit";
      }
      
      $result = $conexion->query($sql);
      $rows = $result->num_rows;
      if($rows > 0){ 
           $items = $result->fetch_assoc();
           if($items['siConsecutivosPolizas'] != ""){
                   $polizas = explode(',',$items['siConsecutivosPolizas']);
                   $total = count($polizas);
                   $fields = "";
                   for($x = 0; $x < $total ; $x++){
                        $sql = "SELECT sNumeroPoliza, D.iConsecutivo AS TipoPoliza ".
                               "FROM ct_polizas A LEFT JOIN ct_tipo_poliza D ON A.iTipoPoliza = D.iConsecutivo ".
                               "WHERE iConsecutivoCompania = '".$company."' AND A.iConsecutivo = '".$polizas[$x]."' ".
                               "AND  A.iDeleted = '0' AND dFechaCaducidad >= CURDATE() ".
                               "AND (D.iConsecutivo = '1' OR D.iConsecutivo = '3' OR D.iConsecutivo = '5' OR D.iConsecutivo = '2') ".
                               "ORDER BY sNumeroPoliza ASC";  
                       $result2 = $conexion->query($sql);
                       $rows = $result2->num_rows; 
                       
                       if($rows > 0){
                          while ($polizaArray = $result2->fetch_assoc()) {
                               $fields .= '$("#'.$form.' #policies_endorsement input:checkbox[value=\''.$polizaArray['sNumeroPoliza']."/".$polizaArray['TipoPoliza'].'\']").prop(\'checked\',\'true\'); ';
                          }  
                       }  
                   }
               }
      }else{
          $error = '1';
          $mensaje = "Data was not found.";
      }
      
      $response = array("mensaje"=>"$mensaje","error"=>"$error","polizas"=>"$fields");   
      echo json_encode($response);
  }
  
?>
