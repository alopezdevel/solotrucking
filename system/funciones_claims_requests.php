<?php
      session_start();
      header('content-type: text/html; charset: UTF-8');
      // Generic functions lib 
      include("functiones_genericas.php"); 
      $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
      define('USER',$_SESSION['usuario_actual']); // Constante UserId 
      
      function get_data_grid(){
        include("cn_usuarios.php");
        $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
        $transaccion_exitosa = true;
        
        $registros_por_pagina = $_POST["registros_por_pagina"];
        $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
        $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
            
        //Filtros de informacion //
        $filtroQuery = " WHERE eStatus != 'EDITABLE' ";
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
        $query_rows = "SELECT COUNT(A.iConsecutivo) AS total FROM cb_claims A LEFT JOIN ct_companias B ON A.iConsecutivoCompania = B.iConsecutivo ".$filtroQuery;             
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
          $sql = "SELECT A.iConsecutivo, DATE_FORMAT(dFechaIncidente,'%m/%d/%Y') AS dFechaIncidente, ".
                 "DATE_FORMAT(dHoraIncidente,'%h:%i %p') AS dHoraIncidente, DATE_FORMAT(dFechaAplicacion,'%m/%d/%Y %H:%i') AS dFechaAplicacion, ".
                 "A.sEstado, A.sCiudad, eCategoria, sMensaje, eStatus, sNombreCompania ".
                 "FROM cb_claims A LEFT JOIN ct_companias B ON A.iConsecutivoCompania = B.iConsecutivo".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
          $result = $conexion->query($sql);
          $rows = $result->num_rows; 
             
            if ($rows > 0) {    
                while ($items = $result->fetch_assoc()) { 
                   if($items["iConsecutivo"] != ""){
                        $btn_confirm = "";
                        $class = "";
                        switch($items["eStatus"]){
                             case 'SENT': 
                                $statusTitle = "SENT TO SOLO-TRUCKING: The data can be edited by you or by the employees of just-trucking.";
                                $class       = "class = \"blue\""; 
                                $btn_confirm = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"Edit Data\"><i class=\"fa fa-pencil-square-o\"></i><span></span></div>".
                                               "<div class=\"btn_send_claim btn-icon send-email btn-left\" title=\"Send Claim by E-mail\"><i class=\"fa fa-envelope\"></i><span></span></div>";  
                             break;
                             case 'INPROCESS': 
                                $statusTitle  = "INPROCESS: Your claim has been sent to the insurers and is in process."; 
                                $class        = "class = \"yellow\""; 
                                $btn_confirm  = "<div class=\"btn_change_status btn-icon edit btn-left\" title=\"Change the status of claim\"><i class=\"fa fa-pencil-square-o\"></i><span></span></div>";
                                $btn_confirm .= "<div class=\"btn-icon send-email btn-left\" title=\"See the e-mail sent\" onclick=\"fn_claims.preview_email('".$items['iConsecutivo']."');\"><i class=\"fa fa-external-link\"></i><span></span></div>";  
                             break;
                             case 'APPROVED':
                                $statusTitle  = "APPROVED: Your claim has been approved successfully.";    
                                $class        = "class = \"green\"";
                                $btn_confirm .= "<div class=\"btn-icon send-email btn-left\" title=\"See the e-mail sent\" onclick=\"fn_claims.preview_email('".$items['iConsecutivo']."');\"><i class=\"fa fa-external-link\"></i><span></span></div>";
                                break;
                             case 'CANCELED': 
                                $statusTitle  = "CANCELED: Your claim has been approved canceled, please see the reasons on the edit button.";
                                $class        = "class = \"red\"";
                                $btn_confirm .= "<div class=\"btn-icon send-email btn-left\" title=\"See the e-mail sent\" onclick=\"fn_claims.preview_email('".$items['iConsecutivo']."');\"><i class=\"fa fa-external-link\"></i><span></span></div>";    
                             break;
                         }

                         $htmlTabla .= "<tr $class>
                                        <td>".$items['iConsecutivo']."</td>".
                                        "<td>".$items['sNombreCompania']."</td>".
                                        "<td>".$items['eCategoria']."</td>".
                                        "<td>".$items['dFechaIncidente']."</td>". 
                                        "<td>".$items['dHoraIncidente']."</td>".  
                                        "<td>".$items['sCiudad']."</td>".
                                        "<td>".$items['sEstado']."</td>".
                                        "<td title=\"$statusTitle\">".$items['eStatus']."</td>".
                                        "<td>".$items['dFechaAplicacion']."</td>".                                                                                                                                                                                                                     
                                        "<td>$btn_confirm</td></tr>";  
                                           
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
      function edit_claim(){
          $error = '0';
          $msj = "";
          $fields = "";
          $clave = trim($_POST['clave']);
          $domroot = $_POST['domroot'];
          include("cn_usuarios.php");
          $conexion->autocommit(FALSE);
          
          $sql = "SELECT A.iConsecutivo,A.iConsecutivoCompania,eDanoMercancia,eDanoTerceros,eDanoFisico,sMensaje,sDescripcionSuceso, ".
                 "DATE_FORMAT(dHoraIncidente,'%H:%i') AS dHoraIncidente, DATE_FORMAT(dFechaIncidente,'%m/%d/%Y') AS dFechaIncidente,sCiudad,sEstado,".
                 "IF(B.iConsecutivo != '',CONCAT(B.iConsecutivo, '-', sNombre),A.sNombreOperador) AS sDriver,".
                 "IF(C.iConsecutivo != '', CONCAT(C.iConsecutivo, '-', sVIN), A.sVINUnidad) AS sUnitTrailer ".
                 "FROM cb_claims A ".
                 "LEFT JOIN ct_operadores B ON A.iConsecutivoOperador = B.iConsecutivo ".
                 "LEFT JOIN ct_unidades C ON A.iConsecutivoUnidad = C.iConsecutivo ".
                 "WHERE A.iConsecutivo = '$clave'";
          $result = $conexion->query($sql); 
          $rows = $result->num_rows; 
          if($rows > 0){ 
            $data = $result->fetch_assoc();
            $llaves  = array_keys($data);
            $datos   = $data;
            foreach($datos as $i => $b){ 
                if($i == 'sDescripcionSuceso'){
                   $descripcion = utf8_decode(utf8_encode($datos[$i])); 
                }else{
                   $fields .= "\$('#$domroot :input[id=".$i."]').val('".htmlentities($datos[$i])."');\n"; 
                   
                   if($i == "eDanoTerceros" && $datos[$i] == "YES"){
                       $fields.= "\$('#edit_form .company_policies input[type=checkbox].AL').prop('checked',true);";
                   }else if($i == "eDanoMercancia" && $datos[$i] == "YES"){
                       $fields.= "\$('#edit_form .company_policies input[type=checkbox].MTC').prop('checked',true);";
                   }else if($i == "eDanoFisico" && $datos[$i] == "YES"){
                       $fields.= "\$('#edit_form .company_policies input[type=checkbox].PD').prop('checked',true);"; 
                   }
                }
            }
            
            
            //Cargar polizas y marcar en las que aplica la poliza:
            $query  = "SELECT A.iConsecutivo, sNumeroPoliza, C.sName AS BrokerName,D.iConsecutivo AS iTipoPoliza, sDescripcion, E.sName AS InsuranceName ".
                      "FROM ct_polizas          AS A ".
                      "LEFT JOIN ct_brokers     AS C ON A.iConsecutivoBrokers = C.iConsecutivo ". 
                      "LEFT JOIN ct_tipo_poliza AS D ON A.iTipoPoliza = D.iConsecutivo ".
                      "LEFT JOIN ct_aseguranzas AS E ON A.iConsecutivoAseguranza = E.iConsecutivo ".
                      "WHERE A.iConsecutivoCompania = '".$data['iConsecutivoCompania']."' AND A.iDeleted = '0' AND dFechaCaducidad >= CURDATE()";
            $result = $conexion->query($query);
            $rows   = $result->num_rows; 
            
            if($rows > 0){ 
                while ($items = $result->fetch_assoc()){
                   switch($items['iTipoPoliza']){
                       case '1' : 
                            $tipoPoliza = "PD";
                            $fields    .= "\$('#$domroot #eDanoFisico').removeProp('disabled').removeClass('readonly');"; 
                       break;
                       case '2' : 
                            $tipoPoliza = "MTC";
                            $fields    .= "\$('#$domroot #eDanoMercancia').removeProp('disabled').removeClass('readonly');"; 
                       break;
                       case '3' : 
                            $tipoPoliza = "AL"; 
                            $fields    .= "\$('#$domroot #eDanoTerceros').removeProp('disabled').removeClass('readonly');";
                       break;
                       case '5' : 
                            $tipoPoliza = "MTC"; 
                            $fields    .= "\$('#$domroot #eDanoMercancia').removeProp('disabled').removeClass('readonly');";
                       break;
                   } 
                   #checkbox
                   $check_items .= "<input class=\"num_policies $tipoPoliza\" type=\"checkbox\" value=\"".$items['iConsecutivo']."\">".
                                   "<label class=\"check-label\"> ".$items['sNumeroPoliza']." / ".$items['sDescripcion']."</label><br>"; 
                }
            }
          }
          $conexion->rollback();
          $conexion->close(); 
          $response = array("msj"=>"$msj","error"=>"$error","fields"=>"$fields","descripcion"=>"$descripcion","checkbox"=>"$check_items");   
          echo json_encode($response);
      }
      function save_claim(){
          $error = '0';  
          $msj = "";  
          
          //Conexion:
          include("cn_usuarios.php");  
          $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
          $transaccion_exitosa = true;
          
          //arrays para guardar campos:
          $valores = array();
          $campos  = array();
          
          //Convertir campos de ciudades, drivers, etc a mayusculas.
          $_POST['sDriver']         = strtoupper(trim($_POST['sDriver']));
          $_POST['sUnitTrailer']    = strtoupper(trim($_POST['sUnitTrailer']));
          $_POST['sCiudad']         = strtoupper(trim($_POST['sCiudad']));
          $_POS['sMensaje']         = utf8_encode(trim($_POST['sMensaje']));
          $_POST['dFechaIncidente'] = format_date(trim($_POST['dFechaIncidente']));
          
          
          #PASO 1 REVISAR SI EL DRIVER EXISTE o SE ALMACENA SOLO EL NOMBRE    
          if($_POST['sDriver'] != ''){  
              
                //Si la cadena tiene un - quiere decir que fue de la lista del autocomplete.
                if(strpos($_POST['sDriver'],"-") > 0){
                    $driverdata = explode('-',$_POST['sDriver']); 
                    $driverID   = $driverdata[0];
                    $driverName = $driverdata[1];
                    
                    if($driverID != ''){
                    
                        $query  = "SELECT iConsecutivo,iNumLicencia,eTipoLicencia,siConsecutivosPolizas,inPoliza ".
                                  "FROM ct_operadores WHERE iConsecutivo='$driverID'";
                        $result = $conexion->query($query);
                        $rows   = $result->num_rows;
                        if($rows > 0 ){$driver = $result->fetch_assoc();}
                        else{$error = '1';$mensaje = "Error: The data driver was not found"; }
                        
                    }
                    else{$error = '1';$mensaje = "Error: The data driver was not found";} 
                }
                else{$driverName = $_POST['sDriver'];}
          }else{$driverID="";}
          
          #PASO 2 REVISAR SI LA UNIDAD EXISTE y Traer Datos:
          if($error == "0"){     
            if($_POST['sUnitTrailer'] != ''){  
              
                if(strpos($_POST['sUnitTrailer'],"-") > 0){
                     
                     $unitdata = explode('-',$_POST['sUnitTrailer']); 
                     $unitID   = $unitdata[0];
                     $unitName = $unitdata[1];
                     
                     if($unitID != ''){
                        $query  = "SELECT iConsecutivo,iConsecutivoRadio,iYear,iModelo,sVIN,siConsecutivosPolizas,inPoliza ".
                                  "FROM ct_unidades ".
                                  "WHERE iConsecutivo='$unitID'";
                        $result = $conexion->query($query);
                        $rows   = $result->num_rows;
                        
                        if($rows > 0 ){$unittrailer = $result->fetch_assoc(); }
                        else{$error = '1';$mensaje = "Error: The data Unit / Trailer was not found"; }
                        
                     }else{$error = '1';$mensaje = "Error: The data Unit / Trailer are invalid";}  
                }else{$unitName = $_POST['sUnitTrailer'];}
                 
            }else{$unitID = "";}
          }
          
          if(($unitID != "" || $unitName != "") && ($driverID != "" || $driverName != "") && $error == "0"){ 
              if($_POST['edit_mode'] == 'true'){
                #UPDATE DATA:
                foreach($_POST as $campo => $valor){
                    if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivo" and $campo != "sDriver" and $campo != "sUnitTrailer" && $campo != "iConsecutivoPolizas"){ //Estos campos no se insertan a la tabla
                        array_push($valores,"$campo='".trim($valor)."'"); 
                    }
                }
                
                #ACTUALIZA DATOS: 
                array_push($valores ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
                array_push($valores ,"sIP='".$_SERVER['REMOTE_ADDR']."'");
                array_push($valores ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'");
                
                #Driver:
                if($driverID != "")       {array_push($valores ,"iConsecutivoOperador='".trim($driverID)."'");
                                           array_push($valores ,"sNombreOperador=''");}
                else if($driverName != ""){array_push($valores ,"sNombreOperador='".trim($driverName)."'");
                                           array_push($valores ,"iConsecutivoOperador=null");}
                
                #Unit:
                if($unitID != "")       {array_push($valores ,"iConsecutivoUnidad='".trim($unitID)."'");
                                         array_push($valores ,"sVINUnidad=''");} 
                else if($unitName != ""){array_push($valores ,"sVINUnidad='".trim($unitName)."'");
                                         array_push($valores ,"iConsecutivoUnidad=null");} 
                
                #Categoria del Claim:
                if(($driverID != "" || $driverName != "") && ($unitID != "" || $unitName != "")){array_push($valores ,"eCategoria='BOTH'");}
                else if($driverID != "" || $driverName != "")                                   {array_push($valores ,"eCategoria='DRIVER'");}
                else if($unitID != "" || $unitName != "")                                       {array_push($valores ,"eCategoria='UNIT/TRAILER'");} 
                
                $sql     = "UPDATE cb_claims SET ".implode(",",$valores)." WHERE iConsecutivo = '".trim($_POST['iConsecutivo'])."'";
                $mensaje = "The data was updated successfully.";
                
              }else{
                #INSERT DATA:
                foreach($_POST as $campo => $valor){
                    if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivo" and $campo != "sDriver" and $campo != "sUnitTrailer" && $campo != "iConsecutivoPolizas"){ //Estos campos no se insertan a la tabla
                        array_push($campos,$campo); 
                        array_push($valores,trim($valor));
                    }   
                }
                #INSERTAR DATOS:
                array_push($campos,"iConsecutivoCompania");
                array_push($valores,$company);
                array_push($campos,"dFechaIngreso");
                array_push($valores,date("Y-m-d H:i:s"));
                array_push($campos,"sIP");
                array_push($valores,$_SERVER['REMOTE_ADDR']);
                array_push($campos,"sUsuarioIngreso");
                array_push($valores,$_SESSION['usuario_actual']);
                
                #Driver:
                if($driverID != "")       {array_push($campos,"iConsecutivoOperador"); array_push($valores,trim($driverID));}
                else if($driverName != ""){array_push($campos,"sNombreOperador"); array_push($valores,trim($driverName));}
                
                #Unidad:
                if($unitID != "")       {array_push($campos,"iConsecutivoUnidad"); array_push($valores,trim($unitID));}
                else if($unitName != ""){array_push($campos,"sVINUnidad"); array_push($valores,trim($unitName));}  
                
                #Categoria Claim:
                if(($driverID != "" || $driverName != "") && ($unitID != "" || $unitName != "")){array_push($campos,"eCategoria"); array_push($valores,'BOTH');}
                else if($driverID != "" || $driverName != ""){array_push($campos,"eCategoria"); array_push($valores,'DRIVER');}
                else if($unitID != "" || $unitName != "")    {array_push($campos,"eCategoria"); array_push($valores,'UNIT/TRAILER');} 
                
                $sql     = "INSERT INTO cb_claims (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')";
                $mensaje = "The data was saved successfully.";  
              }

              //TRANSACTION...
              if($sql != ""){
                  $success = $conexion->query($sql); 
                  if($success){ 
                  
                      $_POST['edit_mode'] != 'true' ? $iConsecutivoClaim = $conexion->insert_id :  $iConsecutivoClaim = trim($_POST['iConsecutivo']);
                      
                      //Actualizar tabla de polizas:
                      if($_POST['iConsecutivoPolizas'] != ""){
                          
                          if($_POST['edit_mode'] == 'true'){ 
                              $query   = "DELETE FROM cb_claim_poliza WHERE iConsecutivoClaim = '$iConsecutivoClaim'";
                              $success = $conexion->query($query);
                              if(!($success)){$transaccion_exitosa = false;}
                          }  
                          if($transaccion_exitosa){ 
                              $polizas = explode("|",$_POST['iConsecutivoPolizas']);
                              $count   = count($polizas);
                              
                              for($x=0;$x < $count;$x++){
                                  $query   = "INSERT INTO cb_claim_poliza (iConsecutivoClaim,iConsecutivoPoliza) ".
                                             "VALUES('$iConsecutivoClaim','".$polizas[$x]."')"; 
                                  $success = $conexion->query($query);
                                  if(!($success)){$transaccion_exitosa = false;}
                              }
                          }
                      }    
                      

                  }else{$error = "1";$mensaje = "The data was not saved properly, please try again.";}
              }else{$error = '1';$mensaje = "Error: Please try again later.";  }   
          }
          else{$error = '1';$mensaje = "Error: Please select a driver or unit/trailer of your lists.";}
          
          if($transaccion_exitosa && $error == "0"){$conexion->commit();$conexion->close();}
          else{$conexion->rollback();$conexion->close();}
      
          $response = array("error"=>"$error","msj"=>"$mensaje");
          echo json_encode($response);
          
      }
      function upload_files(){
      
          $error = "0";
          include("cn_usuarios.php");
          $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
          $transaccion_exitosa = true;
          $oFichero   = fopen($_FILES['userfile']["tmp_name"], 'r'); 
          $sContenido = fread($oFichero, filesize($_FILES['userfile']["tmp_name"]));  
          $sContenido = $conexion->real_escape_string($sContenido);
          $iConsecutivoClaim = trim($_POST['iConsecutivoClaim']);
          //Revisamos el tamaño del archivo:
          if($_FILES['userfile']["size"] <= 921600 && $iConsecutivoClaim != ''){  //900 KB
          
            $name_file = $_FILES['userfile']["name"];
            $type_file = $_FILES['userfile']["type"];
            $size_file = $_FILES['userfile']["size"];
            
            $sql = "INSERT INTO cb_claims_files (iConsecutivoClaim, sNombreArchivo, sTipoArchivo, iTamanioArchivo, hContenidoDocumentoDigitalizado, dFechaIngreso, sIP, sUsuarioIngreso) ".
                   "VALUES('$iConsecutivoClaim','$name_file','$type_file','$size_file','$sContenido','".date("Y-m-d H:i:s")."', '".$_SERVER['REMOTE_ADDR']."', '".$_SESSION['usuario_actual']."')";
            if($conexion->query($sql)){
                $conexion->commit();
                $conexion->close();
                $mensaje = "The file was uploaded successfully.";  
            }else{
                $conexion->rollback();
                $conexion->close();
                $mensaje = "A general system error ocurred : Please try again later.";
                $error = "1";
            }       
          }else{             
             $mensaje = "Error: The file you are trying to upload exceeds the maximum size (900KB) allowed by the system, please check it and try again.";
             $error = "1"; 
          }
          
          $response = array("mensaje"=>"$mensaje","error"=>"$error"); 
          echo json_encode($response);  
      }
      function get_files(){
            include("cn_usuarios.php");
            $iConsecutivoClaim = trim($_POST['iConsecutivoClaim']);
            $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
            $transaccion_exitosa = true;
            $registros_por_pagina = $_POST["registros_por_pagina"];
            $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
            $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
                
            //Filtros de informacion //
            $filtroQuery = " WHERE iConsecutivoClaim = '".$iConsecutivoClaim."' ";
            /*$array_filtros = explode(",",$_POST["filtroInformacion"]);
            foreach($array_filtros as $key => $valor){
                if($array_filtros[$key] != ""){
                    $campo_valor = explode("|",$array_filtros[$key]);
                    $campo_valor[0] == 'iConsecutivo' ? $filtroQuery.= " AND  ".$campo_valor[0]."='".$campo_valor[1]."' " : $filtroQuery == "" ? $filtroQuery.= " AND  ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'": $filtroQuery.= " AND ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'";
                }
            } */
            // ordenamiento//
            $ordenQuery = " ORDER BY ".$_POST["ordenInformacion"]." ".$_POST["sortInformacion"];

            //contando registros // 
            $query_rows = "SELECT COUNT(iConsecutivo) AS total FROM cb_claims_files ".$filtroQuery;
                          
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
              $sql = "SELECT iConsecutivo, sTipoArchivo,sNombreArchivo,iTamanioArchivo ".
                     "FROM cb_claims_files ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
              $result = $conexion->query($sql);
              $rows = $result->num_rows; 
                 
                if ($rows > 0) {    
                    while ($items = $result->fetch_assoc()) { 
                       if($items["iConsecutivo"] != ""){

                             $htmlTabla .= "<tr>
                                                <td id=\"".$items['iConsecutivo']."\">".$items['sNombreArchivo']."</td>".
                                               "<td>".$items['sTipoArchivo']."</td>".
                                               "<td>".$items['iTamanioArchivo']."</td>". 
                                               "<td>".
                                                    "<div class=\"btn-icon edit btn-left\" title=\"Open file in a new window\" onclick=\"window.open('open_pdf.php?idfile=".$items['iConsecutivo']."&type=claims');\"><i class=\"fa fa-picture-o\"></i><span></span></div>". 
                                                    "<div class=\"btn_delete_file btn-icon trash btn-left\" title=\"Delete file\"><i class=\"fa fa-trash\"></i><span></span></div>".
                                               "</td></tr>";  
                                               
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
      function delete_file(){
          
          $iConsecutivo = trim($_POST['clave']);
          $error = '0'; 
          $msj = "";  
          //Conexion:
          include("cn_usuarios.php"); 
          $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
          $transaccion_exitosa = true;
          
          #borar archivos de drivers sin un id de driver asignado:
          $query = "DELETE FROM cb_claims_files WHERE iConsecutivo = '$iConsecutivo'";
          if($conexion->query($query)){
            $conexion->commit();
            $conexion->close();
            $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The files has been deleted succesfully!</p>';
          }else{
            $conexion->rollback();
            $conexion->close();
            $msj = "A general system error ocurred : internal error";
            $error = "1";
          }
            
          $response = array("msj"=>"$msj","error"=>"$error");   
          echo json_encode($response);
      }
      function save_estatus(){
          $error = '0';  
          $msj = "";  
          $eStatus = trim($_POST['eStatus']);
          $iConsecutivoClaim = trim($_POST['iConsecutivoClaim']);
          //Conexion:
          include("cn_usuarios.php");  
          $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
          $transaccion_exitosa = true;
          
          $query = "UPDATE cb_claims SET eStatus = '$eStatus' WHERE iConsecutivo = '$iConsecutivoClaim'";
          if($conexion->query($query)){
                $conexion->commit();
                $conexion->close();
                $mensaje = "The data has been saved successfully, Thank you!";
          }else{
                $conexion->rollback();
                $conexion->close(); 
                $error = "1";
                $mensaje = "The data was not saved properly, please try again.";
          }
          
          
          $response = array("error"=>"$error","msj"=>"$mensaje");
          echo json_encode($response);
          
      }
      
      /*--- E-MAILS ---*/
      function preview_email(){
          
          $error              = '0';
          $msj                = "";
          $fields             = "";
          $iConsecutivoClaim  = trim($_POST['iConsecutivoClaim']);
          $insurances_policy  = trim($_POST['insurances_policy']);
          $sMensaje           = trim(utf8_decode($_POST['sMensaje']));
          $openMode           = trim($_POST['mode']);
          $Emails             = get_email_data($iConsecutivoClaim,$sMensaje,$insurances_policy,$openMode);
          $count              = count($Emails);
          $htmlTabla          = "";
          
          for($x=0;$x < $count;$x++){
              if($Emails[$x]['html']!= ""){
                  $htmlTabla  .= "<table style=\"font-size:12px;border:1px solid #6191df;border-radius:3px;padding:10px;width:95%; margin:5px auto;font-family: Arial, Helvetica, sans-serif;\">";
                  $htmlTabla  .= "<tr><td><h3 style=\"color:#6191df;\">E-mail ".($x+1)."</h3></td></tr>"; 
                  $htmlTabla  .= "<tr><td><b>Subject: </b>".$Emails[$x]['subject']."</td></tr>";
                  $htmlTabla  .= "<tr><td><b>To: </b>".$Emails[$x]['emails']."</td></tr>"; 
                  $htmlTabla .= "<tr><td><hr></td></tr>"; 
                  $htmlTabla .= "<tr><td>".$Emails[$x]['html']."</td></tr>"; 
                  $htmlTabla  .= "</table>";
              } 
          }
          
          $response = array("msj"=>"$msj","error"=>"$error","tabla" => "$htmlTabla");   
          echo json_encode($response);
      }
      function send_email(){ 
          
          $error    = '0';
          $msj      = "";
          $fields   = "";
          $clave    = trim($_POST['iConsecutivoClaim']);
          $emails   = trim($_POST['insurances_policy']);
          $policies = trim($_POST['policies']);   
          $sMensaje = trim(utf8_decode($_POST['sMensaje']));
          $Emails   = get_email_data($clave,$sMensaje,$emails); 
          

          if($Emails['error'] == "0"){
             
             include("cn_usuarios.php");  
             $conexion->autocommit(FALSE);  
             
             #Building Email Body:                                   
             require_once("./lib/phpmailer_master/class.phpmailer.php");
             require_once("./lib/phpmailer_master/class.smtp.php"); 
             $count = (count($Emails)-1);
             
             for($x=0;$x<$count;$x++){
                
                $subject   = $Emails[$x]['subject']; //email subject.
                //Email - body:
                $htmlEmail  = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"\"http://www.w3.org/TR/html4/strict.dtd\"><html><head><meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\"><title>claim from solo-trucking insurance</title></head>";
                $htmlEmail .= "<body>".$Emails[$x]['html']."</body>";             
                $htmlEmail .= "</html>"; 
                
                //Configuration to email:
                if(valida_email($_SESSION['usuario_actual'])){$Replyto = trim($_SESSION['usuario_actual']);}else{$Replyto = "claims@solo-trucking.com";}
                
                #EMAIL TO SEND:
                $mail = new PHPMailer();   
                $mail->IsSMTP(); // telling the class to use SMTP
                $mail->Host       = "mail.solo-trucking.com"; // SMTP server
                $mail->SMTPAuth   = true;                  // enable SMTP authentication
                $mail->SMTPSecure = "TLS";                 // sets the prefix to the servier
                $mail->Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
                $mail->Port       = 587;                   // set the SMTP port for the GMAIL server
                $mail->Username   = "systemsupport@solo-trucking.com";  // GMAIL username
                $mail->Password   = "SL09100242"; 
                $mail->SetFrom('systemsupport@solo-trucking.com', 'Solo-Trucking Insurance');
                $mail->AddReplyTo($Replyto,'Claims Solo-Trucking Insurance');
                $mail->Subject    = $subject;
                $mail->AltBody    = "To view the message, please use an HTML compatible email viewer!";  // optional, comment out and test
                $mail->MsgHTML($htmlEmail);
                $mail->IsHTML(true); 
                
                #Emails to send:
                $sEmail = explode(',',$Emails[$x]['emails']);
                $cEmail = count($sEmail);
                for($i=0; $i < $cEmail; $i++){$mail->AddAddress(trim($sEmail[$i]));}
                
                #Revisar si se necesitan enviar archivos adjuntos:
                
                $select_files = "SELECT * FROM cb_claims_files WHERE iConsecutivoClaim ='$clave'";
                $result       = $conexion->query($select_files);
                $rows         = $result->num_rows;
                
                if ($rows > 0) {    
                    while ($files = $result->fetch_assoc()) { 
                       if($files['sNombreArchivo'] != ""){ 
                            $file_tmp = fopen('tmp/'.$files["sNombreArchivo"],"w") or die("Error when creating the file. Please check."); 
                            fwrite($file_tmp,$files["hContenidoDocumentoDigitalizado"]); 
                            fclose($file_tmp);     
                            $archivo = "tmp/".$files["sNombreArchivo"];  
                            $mail->AddAttachment($archivo);
                            $delete_files .= "unlink(\"tmp/\".".$files["sNombreArchivo"].");"; 
                       }    
                    }                                                                                                                                                                      
                }
                
                $mail_error = false;
                if(!$mail->Send()){$mail_error = true; $mail->ClearAddresses();}
                if(!$mail_error){$msj = "Mail confirmation to the user $usuario was successfully sent.";}
                else{$msj = "Error: The e-mail cannot be sent.";$error = "1";}
                
                #deleting files attachment:
                $mail->ClearAttachments();
                eval($delete_files);
             
                 
             }//end for...
          }
          else{
             $error = "1";
             $mensaje = "Please check that the follow email address are valid: ".$Emails['error']; 
          }

          #VERIFICAR SI SE ENVIARON LOS CORREOS CORRECTAMENTE PARA ACTUALIZAR EL STATUS DEL CLAIM:
          if($error == '0'){
      
            $sql     = "UPDATE cb_claims SET eStatus = 'INPROCESS', dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."', sUsuarioActualizacion='".$_SESSION['usuario_actual']."' ".
                       "WHERE iConsecutivo = '$clave'";
            $success = $conexion->query($sql); 
      
            if($success){
                
                  #DELETE OTROS CORREOS GUARDADOS PARA ESE CLAIM:
                  $query   = "DELETE FROM cb_claims_email WHERE iConsecutivoClaim = '$clave'";
                  $success = $conexion->query($query);
                  
                  if(!($success)){$transaccion_exitosa = false;$mensaje = "The data was not saved properly, please try again."; }
                  else{
                      $emailstosave = explode(";",$policies);
                      $count        = (count($emailstosave)-1);
                      
                      for($x=0;$x<$count;$x++){
                          $datos    = explode("|",$emailstosave[$x]);
                          $idPoliza = $datos[0];
                          $sEmails  = strtolower($datos[1]);
                          
                          #INSERTAR A TABLA DE CORREOS:
                          $query   = "INSERT INTO cb_claims_email (iConsecutivoClaim,sMensajeEmail,sEmail,sIPIngreso,sUsuarioIngreso) ".
                                     "VALUES('$clave','$sMensaje','$sEmails','".$_SERVER['REMOTE_ADDR']."','".$_SESSION['usuario_actual']."')";
                          $success = $conexion->query($query); 
                          
                          if(!($success)){$transaccion_exitosa = false;$mensaje = "Error updating mail data, please try again."; }
                          else{
                              $idEmail = $conexion->insert_id;
                              #INSERTAR A TABLA DE POLIZAS - CLAIMS:
                              $query   = "UPDATE cb_claim_poliza SET iConsecutivoEmail = '$idEmail', eStatus='INPROCESS' ".
                                         "WHERE iConsecutivoPoliza ='$idPoliza' AND iConsecutivoClaim='$iConsecutivoClaim'";
                              $success = $conexion->query($query); 
                              if(!($success)){$transaccion_exitosa = false;$mensaje = "Error updating claim data, please try again.";}             
                          }
                      }
                  } 
            }
            else{$error = '1';$msj = "The data of claim was not updated properly, please try again.";} 

          }
          
          if($error == '0'){$conexion->commit();$msj = "The claim was sent successfully, please check your email (".trim($_SESSION['usuario_actual']).") waiting for their response.";}else{$conexion->rollback();}
          $conexion->close();
          $response = array("error"=>"$error","msj"=>"$msj");
          echo json_encode($response); 
      }
      function save_claim_email(){
          

          $error             = '0';  
          $msj               = ""; 
          $eStatus           = trim($_POST['eStatus']);
          $iConsecutivoClaim = trim($_POST['iConsecutivoClaim']);
          $insurances_policy = trim($_POST['insurances_policy']);
          $sMensaje          = trim(utf8_decode($_POST['sMensaje']));
       
          
          //Conexion:
          include("cn_usuarios.php");  
          $conexion->autocommit(FALSE);                                                                                                                                                                                                                                       
          $transaccion_exitosa = true;
          
          //arrays para guardar campos:
          $valores = array();
          $campos  = array();
          
          
          #DELETE OTROS CORREOS GUARDADOS PARA ESE CLAIM:
          $query   = "DELETE FROM cb_claims_email WHERE iConsecutivoClaim = '$iConsecutivoClaim'";
          $success = $conexion->query($query);
          
          if(!($success)){$transaccion_exitosa = false;$mensaje = "The data was not saved properly, please try again."; }
          else{
              $emailstosave = explode(";",$insurances_policy);
              $count        = (count($emailstosave)-1);
              
              for($x=0;$x<$count;$x++){
                  $datos    = explode("|",$emailstosave[$x]);
                  $idPoliza = $datos[0];
                  $sEmails  = strtolower($datos[1]);
                  
                  #INSERTAR A TABLA DE CORREOS:
                  $query   = "INSERT INTO cb_claims_email (iConsecutivoClaim,sMensajeEmail,sEmail,sIPIngreso,sUsuarioIngreso) ".
                             "VALUES('$iConsecutivoClaim','$sMensaje','$sEmails','".$_SERVER['REMOTE_ADDR']."','".$_SESSION['usuario_actual']."')";
                  $success = $conexion->query($query); 
                  
                  if(!($success)){$transaccion_exitosa = false;$mensaje = "Error updating mail data, please try again."; }
                  else{
                      $idEmail = $conexion->insert_id;
                      
                      $eStatus != "" ? $updateEstatus = ",eStatus='$eStatus'" : $updateEstatus = "";
                      #INSERTAR A TABLA DE POLIZAS - CLAIMS:
                      $query   = "UPDATE cb_claim_poliza SET iConsecutivoEmail = '$idEmail' $updateEstatus ".
                                 "WHERE iConsecutivoPoliza ='$idPoliza' AND iConsecutivoClaim='$iConsecutivoClaim'";
                      $success = $conexion->query($query); 
                      if(!($success)){$transaccion_exitosa = false;$mensaje = "Error updating claim data, please try again.";}
                      else{
                          $mensaje = "The data was saved successfully."; 
                      }             
                  }
              }
          }
          
           
          
          //TRANSACTION...
          if(!($transaccion_exitosa)){$error = "1";}
          $response = array("error"=>"$error","msj"=>"$mensaje");        

              
          $transaccion_exitosa ? $conexion->commit() : $conexion->rollback();
          $conexion->close();
          echo json_encode($response);
       
      }
      function get_email_data($iConecutivoClaim,$sMensaje,$insurances_policy,$openMode='send'){
          
          include("cn_usuarios.php");
          $Emails = array();
          $error  = "";
          
          #DATA OF CLAIM:
          $sql    = "SELECT A.iConsecutivo,A.iConsecutivoCompania,sMensaje,DATE_FORMAT(dHoraIncidente,'%h:%i %p') AS dHoraIncidente, DATE_FORMAT(dFechaIncidente,'%m/%d/%Y') AS dFechaIncidente,sNombre AS sDriver, sVIN AS sUnitTrailer, ".
                    "iYear AS UnitYear, sTipo AS UnitType, E.sDescripcion AS UnitMake, iNumLicencia, (CASE WHEN eTipoLicencia = '1' THEN 'Federal/B1' WHEN eTipoLicencia = '2' THEN 'Commercial/CDL - A' END) AS eTipoLicencia, ".
                    "B.siConsecutivosPolizas AS polizas_operador, C.siConsecutivosPolizas AS polizas_unidad, sNombreCompania, A.sEstado, A.sCiudad, eCategoria, sMensaje, eStatus, sDescripcionSuceso, sVINUnidad, sNombreOperador ".
                    "FROM cb_claims A ".
                    "LEFT JOIN ct_operadores B ON A.iConsecutivoOperador = B.iConsecutivo ".
                    "LEFT JOIN ct_unidades   C ON A.iConsecutivoUnidad   = C.iConsecutivo ".
                    "LEFT JOIN ct_companias  D ON A.iConsecutivoCompania = D.iConsecutivo ".
                    "LEFT JOIN ct_unidad_modelo E ON C.iModelo = E.iConsecutivo ".
                    "WHERE A.iConsecutivo = '$iConecutivoClaim'"; 
          $result = $conexion->query($sql); 
          $rows   = $result->num_rows; 
          $dClaim = $result->fetch_assoc();  //<-- Datos del Claim.  
          
          $CompanyName = $dClaim['sNombreCompania'];
          $DateofLoss  = $dClaim['dFechaIncidente'];
          $HourofLoss  = $dClaim['dHoraIncidente'];
          $ClaimCity   = $dClaim['sCiudad'];
          $ClaimState  = $dClaim['sEstado'];
          $ClaimAppend = $dClaim['sDescripcionSuceso'];
          
          #CALCULATE DE EMAILS FOR SEND:
          if($openMode == "openemail"){
             $query  = "SELECT sMensajeEmail,sEmail FROM cb_claims_email WHERE iConsecutivoClaim ='$clave'";
             $result = $conexion->query($query); 
             $rows   = $result->num_rows; 
             $data   = $result->fetch_assoc();
             
             $sMensaje = "<b>This message was sent to:</b> ".$data['sEmail']."<br><br>".$data['sMensajeEmail']."<br>";
          }else{
             $insurances= explode(";",$insurances_policy); 
          }
          
          
          $count     = (count($insurances)-1);
          for($x = 0;$x < $count; $x++){
              
              $email         = array();
              $insurancedata = explode("|",$insurances[$x]);
              $PolicyType    = $insurancedata[0];
              $PolicyNumber  = $insurancedata[1];
              $PolicyEmail   = $insurancedata[2];
  
              #SUBJECT
              $email["subject"] = "$CompanyName - $PolicyNumber - $PolicyType - $DateofLoss"; 
              
              #EMAILS TO SEND (VALIDATE)
              $emailRegex   = "/^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/"; 
              $emailstosend = explode(",",$PolicyEmail);
              $countemails  = count($emailstosend);
              $emailerror   = "";
              
              for($z = 0; $z < $countemails; $z++){ 
                  if($emailstosend[$z] != ""){
                      $validaemail = preg_match($emailRegex,trim($emailstosend[$z]));
                      if(!($validaemail)){$emailerror .= $emailstosend[$z]."<br>";}
                  }
              }
              if($emailerror == ""){$email["emails"] = $PolicyEmail;$email["error"] = "0";}
              else{
                 $email["emails"]  = $emailerror;
                 $email["error"]   = "1"; 
              }
              
              switch($PolicyType){
                  case 'AL'  : $PolicyTypeDesc = "Auto-Liability";    break;
                  case 'PD'  : $PolicyTypeDesc = "Physical Damage";   break; 
                  case 'MTC' : $PolicyTypeDesc = "Motor Truck Cargo"; break; 
              }
              
              //Email:
              $htmlTabla  = "<table style=\"width:100%;\">";
              if($sMensaje != ""){
                 $htmlTabla .= "<tr><td>$sMensaje</td></tr>";
                 $htmlTabla .= "<tr><td></td></tr>";  
              }
              
              $styleheader = "style=\"\"";
              $htmlTabla .= "<tr><td>".
                            "<table style=\"width:100%;border:1px solid #6191df;\">".
                                "<tr></tr>".
                            "</table>".
                            "</td></tr>";
              $htmlTabla .= "<tr><td>"."<span style=\"font-weight: bold;\">Company: </span>$CompanyName</td>"."</tr>";
              $htmlTabla .= "<tr>"."<td><span style=\"font-weight: bold;\">Policy: </span>$PolicyNumber</td>"."</tr>";
              $htmlTabla .= "<tr>"."<td><span style=\"font-weight: bold;\">Type of Claim: </span>$PolicyTypeDesc</td></tr>";
              
              $htmlTabla .= "<tr><td></td></tr>"; 
              $htmlTabla .= "<tr>"."<td style=\"text-align:center;font-weight: bold;\">DATA OF INCIDENT</td>"."</tr>"; 
              $htmlTabla .= "<tr><td></td></tr>";
              $htmlTabla .= "<tr>"."<td><span style=\"font-weight: bold;\">Date and Hour: </span>$DateofLoss at $HourofLoss</td>"."</tr>";
              $htmlTabla .= "<tr>"."<td><span style=\"font-weight: bold;\">State and City: </span>$ClaimCity, $ClaimState</td>"."</tr>";
              $htmlTabla .= "<tr>"."<td><span style=\"font-weight: bold;\">What happend?: </span></td>"."</tr><tr><td>$ClaimAppend</td></tr>"; 
              
              #DATA OF DRIVER:
              if($dClaim['sDriver'] != '' && ($dClaim['eCategoria'] = 'BOTH' || $dClaim['eCategoria'] = 'DRIVER')){  
                  
                  $DriverName     = $dClaim['sDriver'];
                  $DriverLicense  = $dClaim['iNumLicencia'];
                  $DriverLicenseT = $dClaim['eTipoLicencia'];
                  
                  $htmlTabla .= "<tr><td><hr></td></tr>"; 
                  $htmlTabla .= "<tr>"."<td style=\"text-align:center;font-weight: bold;\">DATA OF DRIVER</td>"."</tr>";
                  $htmlTabla .= "<tr><td>";
                  $htmlTabla .= "<span style=\"font-weight: bold;\">Name: </span>$DriverName<br>".
                                "<span style=\"font-weight: bold;\">License Number: </span>$DriverLicense<br>".
                                "<span style=\"font-weight: bold;\">License Type: </span>$DriverLicenseT<br>";
                  
              }
              else if($dClaim['sNombreOperador'] != ""){
                  $DriverName = $dClaim['sNombreOperador'];
                  $htmlTabla .= "<tr><td><hr></td></tr>"; 
                  $htmlTabla .= "<tr>"."<td style=\"text-align:center;font-weight: bold;\">DATA OF DRIVER</td>"."</tr>";
                  $htmlTabla .= "<tr><td>";
                  $htmlTabla .= "<span style=\"font-weight: bold;\">Name: </span>$DriverName<br>";
                  
              }
              
              #DATA OF UNIT/TRAILER:
              if($dClaim['sUnitTrailer'] != '' && ($dClaim['eCategoria'] = 'BOTH' || $dClaim['eCategoria'] = 'UNIT/TRAILER')){
                  
                  $dClaim['UnitType'] == 'UNIT' ? $type = 'UNIT' : $type = 'TRAILER';
                  $UnitVIN    = $dClaim['sUnitTrailer'];
                  $UnitYear   = $dClaim['UnitYear'];
                  $UnitMake   = $dClaim['UnitMake'];
                  $htmlTabla .= "<tr><td><hr></td></tr>"; 
                  $htmlTabla .= "<tr>"."<td style=\"text-align:center;font-weight: bold;\">DATA OF $type</td>"."</tr>";
                  $htmlTabla .= "<tr><td>";
                  $htmlTabla .= "<span style=\"font-weight: bold;\">VIN#: </span>$UnitVIN<br>".
                                "<span style=\"font-weight: bold;\">Year: </span>$UnitYear<br>".
                                "<span style=\"font-weight: bold;\">Make: </span>$UnitMake<br>";
                  $htmlTabla .= "</td></tr>"; 
              }
              else if($dClaim['sVINUnidad'] != ""){
                  
                  $UnitVIN    = $dClaim['sVINUnidad'];
                  $htmlTabla .= "<tr><td><hr></td></tr>"; 
                  $htmlTabla .= "<tr>"."<td style=\"text-align:center;font-weight: bold;\">DATA OF UNIT/TRAILER</td>"."</tr>";
                  $htmlTabla .= "<tr><td><span style=\"font-weight: bold;\">VIN#: </span>$UnitVIN<br></td></tr>"; 
              }
              
              $htmlTabla .= "</table>";
              
              $email['html'] = $htmlTabla;
              
              $Emails[$x] = $email;
              if($email["error"] == "1"){$error .= $email["emails"];}
              
          }//end for...
          $error != "" ? $Emails['error'] = $error : $Emails['error'] = "0";
          $conexion->close(); 
          return $Emails;
          
      }
      
      /*--- EXTRAS ---*/  
      //Traer las polizas del claim:
      function get_claim_policies(){
          $error = '0';
          $msj = "";
          $fields = "";
          $clave = trim($_POST['clave']);
          $domroot = $_POST['domroot'];
          include("cn_usuarios.php");
          $conexion->autocommit(FALSE);
          
          $sql = "SELECT eCategoria,iConsecutivoOperador, iConsecutivoUnidad, B.siConsecutivosPolizas AS polizas_operador, C.siConsecutivosPolizas AS polizas_unidad ". 
                 "FROM cb_claims A ".
                 "LEFT JOIN ct_operadores B ON A.iConsecutivoOperador = B.iConsecutivo ".
                 "LEFT JOIN ct_unidades C ON A.iConsecutivoUnidad = C.iConsecutivo ".
                 "WHERE A.iConsecutivo = '$clave'";
          $result = $conexion->query($sql); 
          $rows = $result->num_rows; 
          if($rows > 0){ 
            $data = $result->fetch_assoc();
            if($data['eCategoria'] == 'DRIVER'){$polizas = explode(',',$data['polizas_operador']);}
            else if($data['eCategoria'] == 'UNIT/TRAILER'){$polizas = explode(',',$data['polizas_unidad']);}
            else if($data['eCategoria'] == 'BOTH'){ 
                if($data['polizas_operador'] == $data['polizas_unidad']){
                    $polizas = explode(',',$data['polizas_operador']);
                }else{
                    $polizas_operador = explode(',',$data['polizas_operador']);
                    $polizas_unidad = explode(',',$data['polizas_unidad']);
                    $error = '1';
                    $msj = "Error: The driver or unit is not added to the same policies. Please check the information in the driver and unit list.";
                }
            }
            $select = "<option value=\"\">Select an option...</option>";
            for($i=0; $i < count($polizas); $i++) {
                 $policy_query = "SELECT A.iConsecutivo, sNumeroPoliza, D.sDescripcion, D.iConsecutivo AS TipoPoliza, sName ".
                                 "FROM ct_polizas A ".
                                 "LEFT JOIN ct_tipo_poliza D ON A.iTipoPoliza = D.iConsecutivo ".
                                 "LEFT JOIN ct_brokers C ON A.iConsecutivoBrokers = C.iConsecutivo ".
                                 "WHERE A.iConsecutivo = '".$polizas[$i]."' AND A.iDeleted ='0' ORDER BY sName ASC";
                 $result_policy = $conexion->query($policy_query); 
                 $rows = $result_policy->num_rows;
                 if($rows>0){
                     $poliza = $result_policy->fetch_assoc();
                     $select .= "<option value=\"".$poliza['iConsecutivo']."\">".$poliza['sName']." - ".$poliza['sDescripcion']." /  ".$poliza['sNumeroPoliza']."</option>";
                 }  
            } 
            $select .= "<option value=\"ALL\">ALL</option>";
            $fields .= "\$('#$domroot :input[id=iConsecutivo]').val('$clave');\n";
          }else{
              $error = '1';
              $msj = "Error: When loading information, please try again.";
          }
          $conexion->rollback();
          $conexion->close(); 
          $response = array("msj"=>"$msj","error"=>"$error","fields"=>"$fields","select" => "$select");   
          echo json_encode($response);
      }
      function get_company_policies(){
          
          include("cn_usuarios.php");     
          $error       = "0";
          $mensaje     = "";
          $check_items = "";
          $clave       = trim($_POST['clave']);
          $domroot     = trim($_POST['domroot']);
          
          //Consultar ID de la compania:
          $sql    = "SELECT A.iConsecutivo AS iConsecutivoClaim, A.iConsecutivoCompania,C.iConsecutivoPoliza, B.sMensajeEmail, B.iConsecutivo AS iConsecutivoEmail, B.sEmail ".
                    "FROM cb_claims            AS A ".
                    "LEFT JOIN cb_claim_poliza AS C ON A.iConsecutivo      = C.iConsecutivoClaim ".
                    "LEFT JOIN cb_claims_email AS B ON C.iConsecutivoEmail = B.iConsecutivo WHERE A.iConsecutivo = '$clave'";
          $result = $conexion->query($sql); 
          $rows   = $result->num_rows; 
          
          if($rows > 0){ 
              $data = $result->fetch_assoc();
              while ($item = $result->fetch_assoc()){
                  if($item['iConsecutivoEmail'] != ""){
                  
                      $llaves  = array_keys($item);
                      $datos   = $item;
                      
                      foreach($datos as $i => $b){ 
                        if($i == 'sMensajeEmail'){
                            $fields .= "\$('#$domroot :input[id=".$i."]').val('".utf8_decode(utf8_encode($datos[$i]))."');\n";
                        }
                        else if($i == 'sEmail'){
                           $fields .= "\$('#$domroot :input[class=idpolicy_".$item['iConsecutivoPoliza']."]').val('".$datos[$i]."');\n"; 
                        }
                        else if($i != "iConsecutivoPoliza" && $i != "iConsecutivoEmail" && $i != "iConsecutivoCompania"){
                           $fields .= "\$('#$domroot :input[id=".$i."]').val('".htmlentities($datos[$i])."');\n"; 
                        }
                      }
                      
                  }
              }
              
          
              $query  = "SELECT A.iConsecutivo, sNumeroPoliza,D.iConsecutivo AS iTipoPoliza, sDescripcion, E.sName AS InsuranceName, E.sEmailClaims ".
                        "FROM ct_polizas          AS A ".
                        "LEFT JOIN ct_tipo_poliza AS D ON A.iTipoPoliza = D.iConsecutivo ".
                        "LEFT JOIN ct_aseguranzas AS E ON A.iConsecutivoAseguranza = E.iConsecutivo ".
                        "WHERE A.iConsecutivoCompania = '".$data['iConsecutivoCompania']."' AND A.iDeleted = '0' AND dFechaCaducidad >= CURDATE()";
              $result = $conexion->query($query);
              $rows   = $result->num_rows;   
              
              if($rows > 0){    
                while ($items = $result->fetch_assoc()){
                     
                   #table
                   if($items["sNumeroPoliza"] != ""){
                        
                       $tipoPoliza = get_policy_type($items['iTipoPoliza']); 
                       $htmlTabla .= "<tr>".
                                       "<td style=\"border: 1px solid #dedede;\">".$items['sNumeroPoliza']."</td>".
                                       "<td style=\"border: 1px solid #dedede;\">".$items['sDescripcion']."</td>".
                                       "<td style=\"border: 1px solid #dedede;\">".$items['InsuranceName']."</td>". 
                                       "<td style=\"border: 1px solid #dedede;\"><input class=\"idpolicy_".$items['iConsecutivo']."\" id=\"epolicy_".$tipoPoliza."_".$items['sNumeroPoliza']."\" type=\"text\" value=\"".$items['sEmailClaims']."\" text=\"".$items['sEmailClaims']."\" style=\"width: 94%;\" title=\"If you need to write more than one email, please separate them by comma symbol (,).\"/></td>".
                                       "</tr>";
                                           
                   }else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";}    
                }
              }else{
                  $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";
              }
          } 

          $response = array("checkboxes"=>"$check_items","fields"=>"$fields","error"=>"$error","policies_information"=>"$htmlTabla");   
          echo json_encode($response);
          
      }
?>
