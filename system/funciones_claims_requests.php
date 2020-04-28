<?php
      session_start();
      header('content-type: text/html; charset: UTF-8');
      // Generic functions lib 
      include("functiones_genericas.php"); 
      #Ejecutar Funciones POST/GET
      isset($_POST["accion"]) &&  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : "";
      isset($_GET["accion"])  &&  $_GET["accion"] != "" ? call_user_func_array($_GET["accion"],array())  : "";
      
      define('USER',$_SESSION['usuario_actual']); // Constante UserId 
      
      function get_data_grid(){
        include("cn_usuarios.php");
        $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
        $transaccion_exitosa = true;
        
        $registros_por_pagina = $_POST["registros_por_pagina"];
        $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
        $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
            
        //Filtros de informacion //
        $filtroQuery = " WHERE eStatus != 'EDITABLE' AND bEliminado='0' ";
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
          $sql = "SELECT A.iConsecutivo,A.iConsecutivoCompania, DATE_FORMAT(dFechaIncidente,'%m/%d/%Y') AS dFechaIncidente, ".
                 "DATE_FORMAT(dHoraIncidente,'%h:%i %p') AS dHoraIncidente, DATE_FORMAT(dFechaAplicacion,'%m/%d/%Y') AS dFechaAplicacion, ".
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
                                $estado      = '<i class="fa fa-circle-o icon-estatus " aria-hidden=\"true\"></i><span style="font-size: 10px;">NEW</span>';
                                $btn_confirm = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"Edit Data\"><i class=\"fa fa-pencil-square-o\"></i><span></span></div>".
                                               "<div class=\"btn_send_claim btn-icon send-email btn-left\" title=\"Send Claim by E-mail\"><i class=\"fa fa-envelope\"></i><span></span></div>".
                                               "<div class=\"btn_delete btn-icon trash btn-left\" title=\"Delete Claim\"><i class=\"fa fa-trash\"></i> <span></span></div>";   
                             break;
                             case 'INPROCESS': 
                                $statusTitle  = "INPROCESS: Your claim has been sent to the insurers and is in process."; 
                                 $estado      = '<i class="fa fa-share-square-o status-process icon-estatus " aria-hidden=\"true\"></i><span style="font-size: 10px;">SENT TO BROKERS</span>';
                                $class        = "class = \"yellow\""; 
                                $btn_confirm  = "<div class=\"btn_change_status btn-icon edit btn-left\" title=\"Change the status of claim\"><i class=\"fa fa-pencil-square-o\"></i><span></span></div>";
                                $btn_confirm .= "<div class=\"btn-icon send-email btn-left\" title=\"See the e-mail sent\" onclick=\"fn_claims.preview_email('".$items['iConsecutivo']."');\"><i class=\"fa fa-external-link\"></i><span></span></div>";  
                             break;
                             case 'APPROVED':
                                $statusTitle  = "APPROVED: Your claim has been approved successfully.";    
                                $class        = "class = \"green\"";
                                $estado      = '<i class="fa fa-check-circle status-success icon-estatus " aria-hidden=\"true\"></i><span style="font-size: 10px;">APPROVED</span>';
                                $btn_confirm  = "<div class=\"btn_change_status btn-icon edit btn-left\" title=\"Change the status of claim\"><i class=\"fa fa-pencil-square-o\"></i><span></span></div>"; 
                                $btn_confirm .= "<div class=\"btn-icon send-email btn-left\" title=\"See the e-mail sent\" onclick=\"fn_claims.preview_email('".$items['iConsecutivo']."');\"><i class=\"fa fa-external-link\"></i><span></span></div>";
                                break;
                             case 'CANCELED': 
                                $statusTitle  = "CANCELED: Your claim has been approved canceled, please see the reasons on the edit button.";
                                $estado      = '<i class="fa fa-times status-error icon-estatus " aria-hidden=\"true\"></i><span style="font-size: 10px;">CANCELED</span>';
                                $class        = "class = \"red\"";
                                $btn_confirm  = "<div class=\"btn_change_status btn-icon edit btn-left\" title=\"Change the status of claim\"><i class=\"fa fa-pencil-square-o\"></i><span></span></div>"; 
                                $btn_confirm .= "<div class=\"btn-icon send-email btn-left\" title=\"See the e-mail sent\" onclick=\"fn_claims.preview_email('".$items['iConsecutivo']."');\"><i class=\"fa fa-external-link\"></i><span></span></div>";    
                             break;
                         }

                         $htmlTabla .= "<tr $class>".
                                        "<td id=\"idClaim_".$items['iConsecutivo']."\">".$items['sNombreCompania']."</td>".
                                        "<td id=\"idComp_".$items['iConsecutivoCompania']."\">".$items['eCategoria']."</td>".
                                        "<td class=\"text-center\">".$items['dFechaIncidente']."</td>". 
                                        "<td >".$items['dHoraIncidente']."</td>".  
                                        "<td>".$items['sCiudad']."</td>".
                                        "<td>".$items['sEstado']."</td>".
                                        "<td title=\"$statusTitle\">".$estado."</td>".
                                        "<td class=\"text-center\">".$items['dFechaAplicacion']."</td>".                                                                                                                                                                                                                     
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
          $error   = '0';
          $msj     = "";
          $fields  = "";
          $clave   = trim($_POST['clave']);
          $domroot = $_POST['domroot'];
          include("cn_usuarios.php");
          $conexion->autocommit(FALSE);
          
          $sql = "SELECT A.iConsecutivo,A.iConsecutivoCompania,eDanoMercancia,eDanoTerceros,eDanoFisico,sMensaje,sDescripcionSuceso, ".
                 "DATE_FORMAT(dHoraIncidente,'%H:%i') AS dHoraIncidente, DATE_FORMAT(dFechaIncidente,'%m/%d/%Y') AS dFechaIncidente,sCiudad,sEstado,".
                 "IF(B.iConsecutivo != '',CONCAT(B.iConsecutivo, '-', sNombre),A.sNombreOperador) AS sDriver,".
                 "IF(C.iConsecutivo != '', CONCAT(C.iConsecutivo, '-', sVIN), A.sVINUnidad) AS sUnitTrailer, iConsecutivoOperador, iConsecutivoUnidad, C.sVIN AS sVINUnidad, B.sNombre AS sNombreOperador, B.iNumLicencia ".
                 "FROM      cb_claims     AS A ".
                 "LEFT JOIN ct_operadores AS B ON A.iConsecutivoOperador = B.iConsecutivo ".
                 "LEFT JOIN ct_unidades   AS C ON A.iConsecutivoUnidad   = C.iConsecutivo ".
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
                }
            }
            
            $check_items    = "";
            $polizas_select = "";
            
            //Cargar polizas y marcar en las que aplica la poliza:
            $query  = "SELECT iConsecutivoPoliza FROM cb_claim_poliza WHERE iConsecutivoClaim = '".$clave."'";
            $result = $conexion->query($query);
            $rows   = $result->num_rows; 
            
            if($rows > 0){ 
                while ($items = $result->fetch_assoc()){
                    $check_items .= "\$('#$domroot #info_policies :input[value=".$items['iConsecutivoPoliza']."]').prop('checked',true);"; 
                    
                    $polizas_select == "" ? $polizas_select = $items['iConsecutivoPoliza'] : $polizas_select += "|".$items['iConsecutivoPoliza'];  
                }
                
                $fields .= "\$('#$domroot :input[id=iConsecutivoPolizas]').val('".$polizas_select."');\n";
            }
          }
          $conexion->rollback();
          $conexion->close(); 
          $response = array("msj"=>"$msj","error"=>"$error","fields"=>"$fields","descripcion"=>"$descripcion","checkbox"=>"$check_items");   
          echo json_encode($response);
      }
      function save_claim(){
          
          $error             = '0';  
          $mensaje           = "";
          $iConsecutivoClaim = "";  
          
          //Conexion:
          include("cn_usuarios.php");  
          $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
          $transaccion_exitosa = true;
          
          //arrays para guardar campos:
          $valores = array();
          $campos  = array();
          
          //Convertir campos de ciudades, drivers, etc a mayusculas.
          $_POST['sNombreOperador'] = strtoupper(trim($_POST['sNombreOperador']));
          $_POST['sVINUnidad']      = strtoupper(trim($_POST['sVINUnidad']));
          $_POST['sCiudad']         = strtoupper(trim($_POST['sCiudad']));
          $_POS['sMensaje']         = utf8_encode(trim($_POST['sMensaje']));
          $_POST['dFechaIncidente'] = format_date(trim($_POST['dFechaIncidente']));
          $_POST['dFechaAplicacion']= format_date(trim($_POST['dFechaAplicacion']));
          
          #REVISAR DRIVER: Si no existe hay que agregarlo primero.
          if($_POST['iConsecutivoOperador'] == ""){
              if($_POST['iNumLicencia'] == ''){$error = '1'; $mensaje = "Please check de license number from the driver.";}
              else{
                  //Verificamos si ya existe algun driver con el mismo numero de licencia:
                  $query = "SELECT iConsecutivo,sNombre FROM ct_operadores WHERE iNumLicencia='".$_POST['iNumLicencia']."' AND iConsecutivoCompania='".$_POST['iConsecutivoCompania']."'";
                  $result= $conexion->query($query);
                  $rows  = $result->num_rows;
                  if($rows > 0 ){
                      $driver = $result->fetch_assoc();
                      $_POST['iConsecutivoOperador'] = $driver['iConsecutivo'];
                      $_POST['sNombreOperador']      = $driver['sNombre'];
                  }
                  else{
                      //Si no existe lo registramos:
                      $query   = "INSERT INTO ct_operadores (sNombre, iConsecutivoCompania, iNumLicencia) VALUES('".trim($_POST['sNombreOperador'])."','".$_POST['iConsecutivoCompania']."','".trim($_POST['iNumLicencia'])."')";
                      $success = $conexion->query($query);
                      if(!($success)){$error = '1'; $mensaje = "Error to save the driver data, please try again.";}
                      else{
                         $_POST['iConsecutivoOperador'] = $conexion->insert_id;
                      }
                  }
              }
          }
          
          #REVISAR VEHICLE: Si no existe hay que agregarlo primero.
          if($_POST['iConsecutivoUnidad'] == ""){
              
              //Verificamos si ya existe algun driver con el mismo numero de licencia:
              $query = "SELECT iConsecutivo,sVIN FROM ct_unidades WHERE sVIN='".$_POST['sVINUnidad']."' AND iConsecutivoCompania='".$_POST['iConsecutivoCompania']."'";
              $result= $conexion->query($query);
              $rows  = $result->num_rows;
              if($rows > 0 ){
                  $unidad = $result->fetch_assoc();
                  $_POST['iConsecutivoUnidad'] = $unidad['iConsecutivo'];
                  $_POST['sVINUnidad']         = $unidad['sVIN'];
              }
              else{
                  //Si no existe lo registramos:
                  $query   = "INSERT INTO ct_unidades (sVIN, iConsecutivoCompania) VALUES('".trim($_POST['sVINUnidad'])."','".trim($_POST['iConsecutivoCompania'])."')";
                  $success = $conexion->query($query);
                  if(!($success)){$error = '1'; $mensaje = "Error to save the vehicle data, please try again.";}
                  else{
                     $_POST['iConsecutivoUnidad'] = $conexion->insert_id;
                  }
              }
              
          }
          
          if($error == '0'){
            #UPDATE:  
            if($_POST['edit_mode'] == 'true'){
                foreach($_POST as $campo => $valor){
                    if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivo" and $campo != "iNumLicencia" && $campo != "iConsecutivoPolizas"){ //Estos campos no se insertan a la tabla
                        if($campo == 'sDescripcionSuceso'){
                            $valor = trim(fix_string($valor));
                        }
                        array_push($valores,"$campo='".trim($valor)."'"); 
                    }
                }
                
                #ACTUALIZA DATOS: 
                array_push($valores ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
                array_push($valores ,"sIP='".$_SERVER['REMOTE_ADDR']."'");
                array_push($valores ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'");
                
            
                #Categoria del Claim:
                if($_POST['iConsecutivoOperador'] != ""  && $_POST['iConsecutivoUnidad'] != ""){array_push($valores ,"eCategoria='BOTH'");}
                else if($_POST['iConsecutivoOperador'] != "")                                  {array_push($valores ,"eCategoria='DRIVER'");}
                else if($_POST['iConsecutivoUnidad'])                                          {array_push($valores ,"eCategoria='UNIT/TRAILER'");} 
                
                $sql     = "UPDATE cb_claims SET ".implode(",",$valores)." WHERE iConsecutivo = '".trim($_POST['iConsecutivo'])."'";
                $mensaje = "The data was updated successfully.";
                
            }
            #INSERT
            else{
                #INSERT DATA:
                foreach($_POST as $campo => $valor){
                    if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivo" and $campo != "iNumLicencia" && $campo != "iConsecutivoPolizas"){ //Estos campos no se insertan a la tabla
                        
                        if($campo == 'sDescripcionSuceso'){
                            $valor = trim(fix_string($valor));
                        }
                        array_push($campos,$campo); 
                        array_push($valores,trim($valor));
                    }   
                }
                #INSERTAR DATOS:
                array_push($campos,"dFechaIngreso");
                array_push($valores,date("Y-m-d H:i:s"));
                array_push($campos,"sIP");
                array_push($valores,$_SERVER['REMOTE_ADDR']);
                array_push($campos,"sUsuarioIngreso");
                array_push($valores,$_SESSION['usuario_actual']);
                array_push($campos,"eStatus");
                array_push($valores,'SENT');
                
                #Categoria Claim:
                if($_POST['iConsecutivoOperador'] != ""  && $_POST['iConsecutivoUnidad'] != ""){array_push($campos,"eCategoria"); array_push($valores,'BOTH');}else 
                if($_POST['iConsecutivoOperador'] != ""){array_push($campos,"eCategoria"); array_push($valores,'DRIVER');}else 
                if($_POST['iConsecutivoUnidad'])        {array_push($campos,"eCategoria"); array_push($valores,'UNIT/TRAILER');} 
                
                $sql     = "INSERT INTO cb_claims (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')";
                $mensaje = "The data was saved successfully.";  
              }   
              
            #POLIZAS
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
                  

              }else{$error = "1";$mensaje = "The claim data was not saved properly, please try again.";}
            }
            else{$error = '1';$mensaje = "Error: Please try again later.";  }    
          }
          
          
          
          ////
          
          
          #PASO 1 REVISAR SI EL DRIVER EXISTE o SE ALMACENA    
          /*if($_POST['sDriver'] != ''){  
              
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
                
              }
              else{
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
          else{$error = '1';$mensaje = "Error: Please select a driver or unit/trailer of your lists.";} */
          
          if($transaccion_exitosa && $error == "0"){$conexion->commit();$conexion->close();}
          else{$conexion->rollback();$conexion->close();}
      
          $response = array("error"=>"$error","msj"=>"$mensaje","idClaim"=>"$iConsecutivoClaim");
          echo json_encode($response);
          
      }
      function delete_claim(){
          //Conexion:
          include("cn_usuarios.php"); 
          $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
          $success= true;
          $error  = '0';  
          $msj    = ""; 
          $clave  = trim($_POST['clave']); 
          
  
          //BORRAMOS CLAIM.
          //Lo marcamos como eliminado mas no se elimina fisicamente de la BDD.
          $query = "UPDATE cb_claims SET bEliminado='1' WHERE iConsecutivo = '$clave'"; 
          $conexion->query($query);
          $conexion->affected_rows ? $transaccion_exitosa = true : $transaccion_exitosa = false;
         
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
      
      /*---- ARCHIVOS ----*/
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
          if($_FILES['userfile']["size"] <= 4294967296 && $iConsecutivoClaim != ''){  //900 KB
          
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
             $mensaje = "Error: The file you are trying to upload exceeds the maximum size (5MB) allowed by the system, please check it and try again.";
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
      
      /*----- ESTATUS ---*/
      function cargar_estatus_claims(){
          
          include("cn_usuarios.php");
          $error             = "0";
          $mensaje           = "";
          $fields            = "";
          $domroot           = trim($_POST['domroot']);
          $iConsecutivoClaim = trim($_POST['iConsecutivoClaim']);
          $style             = "margin-bottom: 3px;color: #016bba!important;text-transform: uppercase;font-size: 1.1em;";
          
          $query  = "SELECT iConsecutivoPoliza, B.iConsecutivoCompania, sNumeroPoliza, iTipoPoliza, sDescripcion, sName AS InsuranceName, sEmailClaims, A.eStatus, sComentarios, sNumeroClaimAseguranza,sNombreAjustador,sTelefonoAjustador,sTelefonoExtAjustador,sEmailAjustador ".
                    "FROM       cb_claim_poliza AS A ".
                    "INNER JOIN ct_polizas      AS B ON A.iConsecutivoPoliza = B.iConsecutivo ".
                    "LEFT JOIN  ct_tipo_poliza  AS C ON B.iTipoPoliza = C.iConsecutivo ".
                    "LEFT JOIN  ct_aseguranzas  AS D ON B.iConsecutivoAseguranza = D.iConsecutivo ".
                    "WHERE A.iConsecutivoClaim = '$iConsecutivoClaim' AND B.iDeleted = '0' AND B.dFechaCaducidad >= CURDATE()"; 
          $result = $conexion->query($query);
          $rows   = $result->num_rows;
          
          if($rows > 0){
             $htmlTabla = "";
             while($data = $result->fetch_assoc()){
                   
                   $tipoPoliza = get_policy_type($data['iTipoPoliza']);
                   $sNumPoliza = $data['sNumeroPoliza'];
                   $sDescPoliza= $data['sDescripcion']; 
                   $insurance  = $data['InsuranceName'];
                   
                   
                   #HTML TABLA:
                   $htmlTabla .= "<tr class=\"grid-head2\">".
                                 "<td colspan=\"100%\" class=\"etiqueta_grid\" style=\"font-size:11px!important;padding: 5px;\">".
                                 "<table style=\"width:100%\">".
                                 "<tr>".
                                 "<td style=\"width: 25%;border:0px!important;\">"."<span style=\"$style\">Policy No: </span>$sNumPoliza"."</td>".
                                 "<td style=\"width: 50%;border:0px!important;\">"."<span style=\"$style\">Policy Type:</span> $sDescPoliza"."</td>".  
                                 "<td style=\"width: 25%;border:0px!important;\">"."<span style=\"$style\">Insurance:</span> $insurance". "</td>".  
                                 "</tr>".
                                 "</table>".
                                 "</td>".
                                 "</tr>"; 
                   //encabezado2:
                   $encabezado2 = "style=\"width:50%;color: #fff;text-align: center;\"";
                   
                   $htmlTabla .= "<tr class=\"grid-head1\">".
                                 "<td $encabezado2>CLAIM DATA</td>".
                                 "<td $encabezado2>ADJUSTER DATA</td>". 
                                 "</tr>";
   
                   $label  = "style=\"display: block;float: left;width: 18%;margin: 2px 0px;padding: 8px 0px;\"";
                   $input  = "style=\"float: right;width: 80%;clear: none;margin: 2px!important;height: 25px!important;resize: none;\"";
                   $select = "style=\"float: right;width: 553px!important;clear: none;margin: 2px!important;height: 30px!important;\"";
                   $phone  = "style=\"float: left;width:350px;clear: none;margin: 2px!important;height: 25px;resize: none;\"";
                   $ext    = "style=\"float: right;width:120px;clear: none;margin: 2px!important;height: 25px;resize: none;\"";
                   $div    = "style=\"clear:both;height:30px;\""; 
                   
                   $htmlTabla .= "<tr id=\"dataPolicy_".$data['iConsecutivoPoliza']."\" class=\"data_policy\">"; 
                   //Claim Data:
                   $htmlTabla .= "<td style=\"vertical-align:top;\">".
                                 "<div $div>".
                                    "<label $label>Claim No:</label>".
                                    "<input $input type=\"text\" maxlength=\"15\" name=\"sNumeroClaimAseguranza\" title=\"This number is the one granted by the insurer for the claim.\" placeholder=\"Claim No:\">".
                                 "</div>".
                                 "<div $div>".
                                    "<label $label>Status:</label>".
                                    "<select $select id=\"eStatus_".$data['iConsecutivoPoliza']."\"  name=\"eStatus\">".
                                    "<option value=\"INPROCESS\">IN PROCESS</option>".
                                    "<option value=\"APPROVED\">APPROVED</option>".
                                    "<option value=\"CANCELED\">CANCELED/DENIED</option>".
                                    "</select>".
                                 "</div>".
                                 "<div $div>".
                                    "<label $label>Comments:</label><textarea $input id=\"sComentarios_".$data['iConsecutivoPoliza']."\" name=\"sComentarios\" maxlength=\"1000\" title=\"Max. 1000 characters.\"></textarea>".
                                 "</div>".
                                 "</td>";
                   //Adjuster Data:
                   $htmlTabla .= "<td style=\"vertical-align:top;\">".
                                 "<div $div>".
                                    "<label $label>Name:</label>".
                                    "<input $input type=\"text\" maxlength=\"100\" name=\"sNombreAjustador\" placeholder=\"Adjuster Name:\">".
                                 "</div>".
                                 "<div $div>".
                                    "<label $label>Phone:</label>".
                                    "<input $phone type=\"text\" maxlength=\"10\" name=\"sTelefonoAjustador\" placeholder=\"Phone No:\" title=\"max. 10 digits\">".
                                    "<input $ext type=\"text\" maxlength=\"5\" name=\"sTelefonoExtAjustador\" placeholder=\"Ext:\">".
                                 "</div>".
                                 "<div $div>".
                                    "<label $label>E-mail:</label>".
                                    "<input $input type=\"text\" maxlength=\"100\" name=\"sEmailAjustador\" placeholder=\"Ex: username@domain.com\">".
                                 "</div>".
                                 "</td>";              
                   $htmlTabla .= "</tr>";  
                   
                   //Salto de linea:              
                   $htmlTabla .= "<tr><td colspan=\"100%\"></td></tr>";                          

                   #FIELDS:
                   $llaves  = array_keys($data);
                   $datos   = $data;
                      
                   foreach($datos as $i => $b){
                   
                    
                        if($i == "sComentarios" || $i == "eStatus" || $i == "sNumeroClaimAseguranza" || $i == "sNombreAjustador" ||  $i == "sTelefonoAjustador" || $i == "sTelefonoExtAjustador" || $i == "sEmailAjustador"){
                          if($i == 'sComentarios'){$value = utf8_decode(utf8_encode($datos[$i]));}else{$value = $datos[$i];}
                          $fields .= "\$('#$domroot tr#dataPolicy_".$data['iConsecutivoPoliza']." :input[name=".$i."]').val('$value');\n";  
                        }
                        
                        
                   }
                   
              
             }  
             #consultar comentarios del claim:
             $query  = "SELECT eStatus, sMensaje FROM cb_claims WHERE iConsecutivo = '$iConsecutivoClaim' ";
             $result = $conexion->query($query);
             $rows   = $result->num_rows;
             if($rows > 0){
                 $data    = $result->fetch_assoc();
                 $fields .= "\$('#$domroot :input[name=sMensaje]').val('".$data['sMensaje']."');\n";   
             }
          }else{
              $error      = "1";
              $htmlTabla .= "<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";
          }
          
          $response = array("fields"=>"$fields","error"=>"$error","html"=>"$htmlTabla");   
          echo json_encode($response);
           
          
      }
      function save_estatus(){
          
          $error = '0';  
          $msj = ""; 
          $ComentariosClaim  = trim($_POST['sMensaje']);
          $iConsecutivoClaim = trim($_POST['iConsecutivoClaim']);
          $PolizasEstatus    = trim($_POST['polizas']);
          
          //Conexion:
          include("cn_usuarios.php");  
          $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
          $transaccion_exitosa = true;
          
          //Revisar si hay que actualizar polizas:
          $array = explode(";",$PolizasEstatus);
          $count = count($array)-1;
          
          if($count > 0){
              
              for($x=0;$x < $count; $x++){
                  $actualiza = "";
                  $poliza    = explode("|",$array[$x]);
                  $polizaID  = $poliza[0];
                  
                  $actualiza .= " eStatus ='".trim($poliza[1])."' "; 
                  $actualiza != "" ? $actualiza .= ", sComentarios ='".trim($poliza[2])."'"           : $actualiza = "sComentarios ='".trim($poliza[2])."'";
                  $actualiza != "" ? $actualiza .= ", sNumeroClaimAseguranza ='".trim($poliza[3])."'" : $actualiza = "sNumeroClaimAseguranza ='".trim($poliza[3])."'";
                  $actualiza != "" ? $actualiza .= ", sNombreAjustador ='".trim($poliza[4])."'"       : $actualiza = "sNombreAjustador ='".trim($poliza[4])."'";
                  $actualiza != "" ? $actualiza .= ", sTelefonoAjustador ='".trim($poliza[5])."'"     : $actualiza = "sTelefonoAjustador ='".trim($poliza[5])."'";
                  $actualiza != "" ? $actualiza .= ", sTelefonoExtAjustador ='".trim($poliza[6])."'"  : $actualiza = "sTelefonoExtAjustador ='".trim($poliza[6])."'";  
                  $actualiza != "" ? $actualiza .= ", sEmailAjustador ='".trim($poliza[7])."'"        : $actualiza = "sEmailAjustador ='".trim($poliza[7])."'";  
                  
                  $poStatus  = $poliza[1];
                  $poCommen  = $poliza[2];
                  
                  if($actualiza != "" && $polizaID != ""){
                     $query  = "UPDATE cb_claim_poliza SET $actualiza WHERE iConsecutivoPoliza ='$polizaID' AND iConsecutivoClaim = '$iConsecutivoClaim'"; 
                     $success = $conexion->query($query);
          
                     if(!($success)){$transaccion_exitosa = false;$mensaje = "The data was not updated properly, please try again."; }
                  }
                  
              }  
          }  
          
          if($transaccion_exitosa){
              
              $actualiza = "";
              
              #Verificamos si hay que actualizar el estatus general:
              $query  = "SELECT COUNT(iConsecutivoClaim) AS total FROM cb_claim_poliza ".
                        "WHERE iConsecutivoClaim ='".$iConsecutivoClaim."' AND eStatus='APPROVED'";
              $result = $conexion->query($query);
              $claimSt= $result->fetch_assoc();
              
              if($claimSt['total'] == $count){$actualiza .= "eStatus='APPROVED', ";}
          
              if($ComentariosClaim != ""){$actualiza .= "sMensaje='".utf8_encode($ComentariosClaim)."'";}
              
              if($actualiza != ""){
                  $query   = "UPDATE cb_claims SET $actualiza WHERE iConsecutivo = '$iConsecutivoClaim'"; 
                  $success = $conexion->query($query); 
                  
                  if(!($success)){$transaccion_exitosa = false;$mensaje = "The data was not saved properly, please try again.";}
              }
          }
          
          if($transaccion_exitosa){
                $conexion->commit();
                $conexion->close();
                $mensaje = "The data has been saved successfully, Thank you!";
          }
          else{
                $conexion->rollback();
                $conexion->close(); 
                $error = "1";
          }
          
          $response = array("error"=>"$error","msj"=>"$mensaje");
          echo json_encode($response);
          
      }
      
      # E-MAILS
      function get_claim_policies_email(){
          
          include("cn_usuarios.php");
          $conexion->autocommit(FALSE);
          
          $error          = '0';
          $pd_information = "false";
          $company        = trim($_POST['iConsecutivoCompania']);
          $clave          = trim($_POST['clave']); 
          $domroot        = trim($_POST['domroot']); 
          $fields         = "\$('#$domroot :input[name=iConsecutivoClaim]').val('".$clave."');\n"; 
           
          $sql = "SELECT A.iConsecutivo, sNumeroPoliza, C.sName AS sInsuranceName, sDescripcion, D.iConsecutivo AS TipoPoliza, D.sAlias, C.sEmailClaims AS sEmail, B.sEmail AS sEmailClaim,sMensajeEmail, sEmailAjustador ".
                 "FROM      cb_claim_poliza AS B
                  LEFT JOIN ct_polizas      AS A ON B.iConsecutivoPoliza     = A.iConsecutivo
                  LEFT JOIN ct_aseguranzas  AS C ON A.iConsecutivoAseguranza = C.iConsecutivo 
                  LEFT JOIN ct_tipo_poliza  AS D ON A.iTipoPoliza            = D.iConsecutivo ".
                 "WHERE iConsecutivoCompania = '".$company."' AND B.iConsecutivoClaim ='".$clave."'".
                 "AND A.iDeleted = '0' AND dFechaCaducidad >= CURDATE() AND (D.iConsecutivo != '4' AND D.iConsecutivo != '6' AND D.iConsecutivo != '7' AND D.iConsecutivo != '8' AND D.iConsecutivo != '9') ".
                    "ORDER BY sNumeroPoliza ASC";
          $result = $conexion->query($sql);
          $rows   = $result->num_rows;
          
          if($rows > 0) {  
                 
                while ($items = $result->fetch_assoc()) { 
                   
                   // polizas a las que aplica PD 
                   if($items['TipoPoliza'] == '1' || $items['TipoPoliza'] == '11' || $items['TipoPoliza'] == '12' || $items['TipoPoliza'] == '14' || $items['TipoPoliza'] == '17'){
                        $pd_information = 'true'; 
                   }
                   $tipoPoliza = ($items['sAlias']); 
                   $htmlTabla .= "<tr>".
                                 "<td style=\"border: 1px solid #dedede;\">".$items['sNumeroPoliza']."</td>".
                                 "<td style=\"border: 1px solid #dedede;\">".$items['sDescripcion']."</td>". 
                                 "<td style=\"border: 1px solid #dedede;\">".$items['sInsuranceName']."</td>".
                                 "<td style=\"border: 1px solid #dedede;\"><input class=\"idpolicy_".$items['iConsecutivo']."\" id=\"epolicy_".$tipoPoliza."_".$items['sNumeroPoliza']."\" type=\"text\" value=\"".$items['sEmail']."\" text=\"".$items['sEmail']."\" style=\"width: 94%;\" title=\"If you need to write more than one email, please separate them by comma symbol (,).\"/></td>".
                                 "</tr>";
                                 
                                 
                   if($items['sEmailClaim'] != ""){
                     $fields .= "\$('#$domroot :input[id=epolicy_".$tipoPoliza."_".$items['sNumeroPoliza']."]').val('".$items['sEmailClaim']."');\n";    
                   }
          
                   if($items['sMensajeEmail'] != ""){
                     $fields .= "\$('#$domroot :input[name=sMensajeEmail]').val('".$items['sMensajeEmail']."');\n";    
                   }
                        
                } 
                                                                                                                                                                                      
          }
          else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";}
          
          $conexion->rollback();
          $conexion->close();
          $response = array("mensaje"=>"$mensaje","error"=>"$error", "policies_information"=>"$htmlTabla","pd_data"=>"$pd_information","fields"=>"$fields");   
          echo json_encode($response);
          
      }       
      function preview_email(){
          
          $error              = '0';
          $msj                = "";
          $fields             = "";
          $iConsecutivoClaim  = trim($_POST['iConsecutivoClaim']);
          $openMode           = trim($_POST['mode']);
          
          if($openMode != "openemail"){
              $insurances_policy  = trim($_POST['insurances_policy']);
              $sMensaje           = trim(utf8_decode($_POST['sMensaje']));
          }
          else{
              
              $insurances_policy = "";
              //Conexion:
              include("cn_usuarios.php");
              $query = "SELECT iConsecutivoPoliza, eStatus, sEmail, sMensajeEmail, C.iTipoPoliza, C.sNumeroPoliza,B.sAlias ".
                       "FROM      cb_claim_poliza AS A ".
                       "LEFT JOIN ct_polizas      AS C ON A.iConsecutivoPoliza = C.iConsecutivo ".
                       "LEFT JOIN ct_tipo_poliza  AS B ON C.iTipoPoliza        = B.iConsecutivo ".
                       "WHERE A.iConsecutivoClaim = '$iConsecutivoClaim' AND C.iDeleted = '0' AND dFechaCaducidad >= CURDATE() ";
              $result = $conexion->query($query); 
 
              while ($data = $result->fetch_assoc()){ 
                  $sMensaje   = $data['sMensajeEmail'];
                  $sNumPoliza = $data['sNumeroPoliza'];
                  $sEmails    = $data['sEmail'];
                  $tipoPoliza = $data['sAlias'];
                  
                  $insurances_policy .= $tipoPoliza."|".$sNumPoliza."|".$sEmails.";"; 
              }
                  
          } 
          
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
                  
                  //Atachments:
                  $files = $Emails[$x]['files'];
                  if($files != ""){
                      
                      $htmlTabla .= "<tr><td>".
                                    "<table style=\"font-size:12px;border-top:1px solid #dedede;padding:10px;width:95%; margin:5px auto;font-family: Arial, Helvetica, sans-serif;\">";
                      $htmlTabla .= "<tr><td colspan=\"100%;\"><h3>Attachments</h3><td></tr>";
                      
                      $countFiles = count($files);
                      for($f=0; $f < $countFiles; $f++){
                            $htmlTabla .= "<tr>".
                                          "<td>".$files[$f]['name']."</td>".
                                          "<td>".$files[$f]['type']."</td>".
                                          "<td>".$files[$f]['size']."</td>". 
                                          "<td>".
                                          "<div class=\"btn-icon edit btn-left\" title=\"Open file in a new window\" onclick=\"window.open('open_pdf.php?idfile=".$files[$f]['id']."&type=claims');\"><i class=\"fa fa-external-link\"></i><span></span></div>". 
                                          "</td></tr>";    
                      }
                      
                      $htmlTabla .= "</table></td></tr>";
                  }
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
                $htmlEmail .= "<body>";
                $htmlEmail .= "<table style=\"font-size:12px;border:1px solid #6191df;border-radius:3px;padding:10px;width:95%; margin:5px auto;font-family: Arial, Helvetica, sans-serif;\">";
                $htmlEmail .= "<tr><td>".$Emails[$x]['html']."</td></tr>";
                $htmlEmail .= "</table>";
                $htmlEmail .= "</body>";             
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
                
                #VERIFICAR SERVIDOR DONDE SE ENVIAN CORREOS:
                if($_SERVER["HTTP_HOST"]=="stdev.websolutionsac.com" || $_SERVER["HTTP_HOST"]=="www.stdev.websolutionsac.com"){
                  $mail->Username   = "systemsupport@solo-trucking.com";  // GMAIL username
                  $mail->Password   = "SL09100242";  
                  $mail->SetFrom('systemsupport@solo-trucking.com', 'Claims Solo-Trucking Insurance');
                }else if($_SERVER["HTTP_HOST"] == "solotrucking.laredo2.net" || $_SERVER["HTTP_HOST"] == "st.websolutionsac.com" || $_SERVER["HTTP_HOST"] == "www.solo-trucking.com"){
                  $mail->Username   = "claims@solo-trucking.com";  // GMAIL username
                  $mail->Password   = "1104W3bSTruck";
                  $mail->SetFrom('claims@solo-trucking.com', 'Claims Solo-Trucking Insurance');   
                }
                
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
                //Atachments:
                $files        = $Emails[$x]['files'];
                $delete_files = "";
                
                if($files != ""){
                   $countFiles = count($files);
                   for($f=0; $f < $countFiles; $f++){
                       
                       //Revisamos si es PDF:
                       include("./lib/fpdf153/fpdf.php");//libreria fpdf
                       
                       $file_tmp = fopen('tmp/'.$files[$f]["name"],"w") or die("Error when creating the file. Please check."); 
                       fwrite($file_tmp,$files[$f]["content"]); 
                       fclose($file_tmp);     
                       $archivo = "tmp/".$files[$f]["name"];  
                       $mail->AddAttachment($archivo);
                       $delete_files .= "unlink('".$archivo."');";
                   
                   } 
                } 
                
                $mail_error = false;
                if(!$mail->Send()){$mail_error = true; $mail->ClearAddresses();$error = '1';}
                if(!$mail_error){$msj = "Mail confirmation to the user $usuario was successfully sent.";}
                else{$msj = "Error: The e-mail cannot be sent.";$error = "1";}
                
                #deleting files attachment:
                $mail->ClearAttachments();
                eval($delete_files);
             
                #VERIFICAR SI SE ENVIARON LOS CORREOS CORRECTAMENTE PARA ACTUALIZAR EL STATUS DEL CLAIM:
                if($error == '0'){
              
                    $idPoliza = $Emails[$x]['policy_id'];
                        
                    #ACTUALIZAR TABLA DE:
                    $query   = "UPDATE cb_claim_poliza SET eStatus='INPROCESS' WHERE iConsecutivoPoliza ='$idPoliza' AND iConsecutivoClaim='$clave'";
                    $success = $conexion->query($query); 
                    if(!($success)){$transaccion_exitosa = false;$mensaje = "Error updating claim data, please try again.";} 

                }
                 
             }//end for...
          }
          else{
             $error = "1";
             $mensaje = "Please check that the follow email address are valid: ".$Emails['error']; 
          }
          
          if($error == '0'){
              $sql     = "UPDATE cb_claims SET eStatus = 'INPROCESS',dFechaAplicacion='".date("Y-m-d H:i:s")."', dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."', sUsuarioActualizacion='".$_SESSION['usuario_actual']."' ".
                         "WHERE iConsecutivo = '$clave'";
              $success = $conexion->query($sql); 
              
              if(!($success)){$error = '1';$msj = "The data of claim was not updated properly, please try again.";}
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
          $valores      = array();
          $campos       = array();
          $emailstosave = explode(";",$insurances_policy);
          $count        = (count($emailstosave)-1);
              
          for($x=0;$x<$count;$x++){
              $datos    = explode("|",$emailstosave[$x]);
              $idPoliza = $datos[0];
              $sEmails  = strtolower($datos[1]);
              
              #INSERTAR A TABLA DE CORREOS:
              $query   = "UPDATE cb_claim_poliza SET sMensajeEmail='".utf8_encode($_POST['sMensaje'])."', sEmail='".trim($sEmails)."' ".
                         "WHERE iConsecutivoClaim='".$iConsecutivoClaim."' AND iConsecutivoPoliza='".$idPoliza."'";
              $success = $conexion->query($query); 
              
              if(!($success)){$transaccion_exitosa = false;$mensaje = "Error updating mail data, please try again."; }
              else{
                  $mensaje = "The data was saved successfully."; 
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
          $sql    = "SELECT A.iConsecutivo,A.iConsecutivoCompania,sMensaje,DATE_FORMAT(dHoraIncidente,'%h:%i %p') AS dHoraIncidente, DATE_FORMAT(dFechaIncidente,'%m/%d/%Y') AS dFechaIncidente, ".
                    "A.sNombreOperador AS sDriver, A.sVINUnidad AS sUnitTrailer, iYear AS UnitYear, sTipo AS UnitType, E.sDescripcion AS UnitMake, iNumLicencia, ".
                    "(CASE WHEN eTipoLicencia = '1' THEN 'Federal/B1' WHEN eTipoLicencia = '2' THEN 'Commercial/CDL - A' END) AS eTipoLicencia, sNombreCompania, A.sEstado, A.sCiudad, eCategoria, ".
                    "sMensaje, eStatus, sDescripcionSuceso, sVINUnidad, sNombreOperador  ".
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

          $insurances = explode(";",$insurances_policy); 
          $count      = (count($insurances)-1);
          for($x = 0;$x < $count; $x++){
              
              $email         = array();
              $insurancedata = explode("|",$insurances[$x]);
              $PolicyID      = $insurancedata[3];
              $PolicyType    = $insurancedata[0];
              $PolicyNumber  = $insurancedata[1];
              $PolicyEmail   = $insurancedata[2];
              
              $email["policy_id"] = $PolicyID;
  
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
              
              $queryP         = "SELECT * FROM ct_tipo_poliza WHERE sAlias='".$PolicyType."'";
              $result         = $conexion->query($queryP);
              $polizaType     = $result->fetch_assoc(); 
              $PolicyTypeDesc = $polizaType['sDescripcion'];
              
              //Email:
              $htmlTabla  = "<table style=\"width:100%;font-family: Arial, Helvetica, sans-serif!important; \">";
              if($sMensaje != ""){
                 $htmlTabla .= "<tr><td>$sMensaje</td></tr>";
                 $htmlTabla .= "<tr><td></td></tr>";  
              }
              
              $styleheader  = "style=\"background-color:#d6d6d6;text-align:center;padding:2px;color:#484848;font-weight: bold;\"";
              $styleheader2 = "style=\"text-align:center;font-weight: bold;border-bottom:1px solid #484848;\"";
              $styleheader3 = "style=\"text-align:left;font-weight: bold;width:110px;\""; 
              
              $htmlTabla .= "<tr><td>".
                            "<table style=\"width:100%;font-family: Arial, Helvetica, sans-serif!important;\">".
                                "<tr>".
                                    "<td $styleheader>COMPANY</td>".
                                    "<td $styleheader>POLICY #</td>". 
                                    "<td $styleheader>TYPE</td>". 
                                "</tr>".
                                "<tr>".
                                    "<td>$CompanyName</td>".
                                    "<td>$PolicyNumber</td>". 
                                    "<td>$PolicyTypeDesc</td>". 
                                "</tr>".
                            "</table><br>".
                            "</td></tr>";
              $htmlTabla .= "<tr><td></td></tr>";
              
              $htmlTabla .= "<tr><td>".
                            "<table style=\"width:100%;font-family: Arial, Helvetica, sans-serif!important;\">".
                                "<tr><td colspan=\"2\" $styleheader>DATA OF INCIDENT</td></tr>".
                                "<tr><td $styleheader3>Date and Hour:</td><td>$DateofLoss at $HourofLoss</td></tr>".
                                "<tr><td $styleheader3>State and City:</td><td>$ClaimCity, $ClaimState</td></tr>". 
                                "<tr><td $styleheader3>What happend?:</td><td>$ClaimAppend</td></tr>". 
                            "</table><br>".
                            "</td></tr>";
              $htmlTabla .= "<tr><td></td></tr>";
              
              
              #DATA OF DRIVER:
              if($dClaim['sDriver'] != '' && ($dClaim['eCategoria'] = 'BOTH' || $dClaim['eCategoria'] = 'DRIVER')){  
                  
                  $DriverName     = $dClaim['sDriver'];
                  $DriverLicense  = $dClaim['iNumLicencia'];
                  $DriverLicenseT = $dClaim['eTipoLicencia'];
                  
                  $htmlTabla .= "<tr><td>".
                                "<table style=\"width:100%;font-family: Arial, Helvetica, sans-serif!important;\">".
                                "<tr><td colspan=\"2\" $styleheader>DATA OF DRIVER</td></tr>".
                                "<tr><td $styleheader3>Name:</td><td>$DriverName</td></tr>".
                                "<tr><td $styleheader3>License Number:</td><td>$DriverLicense</td></tr>". 
                                "<tr><td $styleheader3>License Type?:</td><td>$DriverLicenseT</td></tr>". 
                                "</table><br>".
                                "</td></tr>";
                  $htmlTabla .= "<tr><td></td></tr>";
                  
              }
              else if($dClaim['sNombreOperador'] != "" && ($dClaim['eCategoria'] = 'BOTH' || $dClaim['eCategoria'] = 'DRIVER')){
                  $DriverName = $dClaim['sNombreOperador'];

                  $htmlTabla .= "<tr><td>".
                                "<table style=\"width:100%;font-family: Arial, Helvetica, sans-serif!important;\">".
                                "<tr><td colspan=\"2\" $styleheader>DATA OF DRIVER</td></tr>".
                                "<tr><td $styleheader3>Name:</td><td>$DriverName</td></tr>".
                                "</table><br>".
                                "</td></tr>";
                  $htmlTabla .= "<tr><td></td></tr>";
                  
              }
              
              #DATA OF UNIT/TRAILER:
              if($dClaim['sUnitTrailer'] != '' && ($dClaim['eCategoria'] = 'BOTH' || $dClaim['eCategoria'] = 'UNIT/TRAILER')){
                  
                  $dClaim['UnitType'] == 'UNIT' ? $type = 'UNIT' : $type = 'TRAILER';
                  $UnitVIN    = $dClaim['sUnitTrailer'];
                  $UnitYear   = $dClaim['UnitYear'];
                  $UnitMake   = $dClaim['UnitMake'];
                  
                  $htmlTabla .= "<tr><td>".
                                "<table style=\"width:100%;font-family: Arial, Helvetica, sans-serif!important;\">".
                                "<tr><td colspan=\"2\" $styleheader>DATA OF $type</td></tr>".
                                "<tr><td $styleheader3>VIN#:</td><td>$UnitVIN</td></tr>".
                                "<tr><td $styleheader3>Year:</td><td>$UnitYear</td></tr>". 
                                "<tr><td $styleheader3>Make:</td><td>$UnitMake</td></tr>". 
                                "</table><br>".
                                "</td></tr>";
                  $htmlTabla .= "<tr><td></td></tr>";
              }
              else if($dClaim['sVINUnidad'] != "" && ($dClaim['eCategoria'] = 'BOTH' || $dClaim['eCategoria'] = 'UNIT/TRAILER')){
                  
                  $UnitVIN    = $dClaim['sVINUnidad'];

                  $htmlTabla .= "<tr><td>".
                                "<table style=\"width:100%;font-family: Arial, Helvetica, sans-serif!important;\">".
                                "<tr><td colspan=\"2\" $styleheader>DATA OF UNIT/TRAILER</td></tr>".
                                "<tr><td $styleheader3>VIN#:</td><td>$UnitVIN</td></tr>".
                                "</table><br>".
                                "</td></tr>";
                  $htmlTabla .= "<tr><td></td></tr>"; 
              }
              
              $htmlTabla .= "</table>";
              
              $email['html'] = $htmlTabla;
              
              #CONSULTAR ARCHIVOS:
              $file = array();
              //Buscamos archivos
              $query  = "SELECT iConsecutivo, sNombreArchivo, hContenidoDocumentoDigitalizado, sTipoArchivo,iConsecutivoPoliza, iTamanioArchivo ".
                        "FROM cb_claims_files WHERE iConsecutivoClaim = '$iConecutivoClaim'"; 
              $result = $conexion->query($query) or die($conexion->error);
              $rows   = $result->num_rows; 
              if($rows > 0){
                    while ($files = $result->fetch_assoc()){
                       #Here will constructed the temporary files: 
                       if($files['sNombreArchivo'] != ""){ 
                         $archivo['id']     = $files['iConsecutivo'];
                         $archivo['name']   = $files['sNombreArchivo'];
                         $archivo['content']= $files['hContenidoDocumentoDigitalizado'];
                         $archivo['size']   = $files['iTamanioArchivo'];
                         $archivo['type']   = $files['sTipoArchivo'];
                         array_push($file,$archivo);
                       }
                    }
              }
              if(count($file)==0){$file="";} 
              
              $email['files'] = $file;
              
              $Emails[$x] = $email;
              if($email["error"] == "1"){$error .= $email["emails"];}
              
          }//end for...
                 
          $error != "" ? $Emails['error'] = $error : $Emails['error'] = "0";
          $conexion->close(); 
          return $Emails;
          
      }
      function mark_email_sent(){
          
          $clave = trim($_POST['iConsecutivoClaim']);
          $error = 0;
           
          include("cn_usuarios.php");
          $conexion->autocommit(FALSE);
          
          //Actualizar Estatus:
          $query   = "UPDATE cb_claim_poliza SET iEnviadoFuera='1', eStatus='INPROCESS' WHERE iConsecutivoClaim='$clave'"; 
          $success = $conexion->query($query); 
          
          if($conexion->affected_rows > 0){
              //Actualizar endoso general:
              $query   = "UPDATE cb_claims SET eStatus='INPROCESS' WHERE iConsecutivo='$clave'"; 
              $success = $conexion->query($query);
              
              if($conexion->affected_rows > 0){$mensaje = "The data was updated successfully!";}else{$error = 1;$mensaje = "Error to update the status data, please try again.";}
          }else{$error = 1; $mensaje = "Error to update the policy/claim status data, please try again.";}  
          
          $error == 0 ? $conexion->commit() : $conexion->rollback(); 
          $conexion->close();
          
          $response = array("msj"=>$mensaje,"error"=>"$error");   
          echo json_encode($response);      
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
          $rows   = $result->num_rows; 
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
          $conexion->autocommit(FALSE);
          $error          = '0';
          $pd_information = "false";
          $clave          = trim($_POST['iConsecutivoCompania']);
          
          $sql = "SELECT A.iConsecutivo, sNumeroPoliza, C.sName AS BrokerName, sDescripcion, D.iConsecutivo AS TipoPoliza, D.sAlias ".
                 "FROM      ct_polizas      AS A
                  LEFT JOIN ct_brokers      AS C ON A.iConsecutivoBrokers = C.iConsecutivo 
                  LEFT JOIN ct_tipo_poliza  AS D ON A.iTipoPoliza         = D.iConsecutivo ".
                 "WHERE iConsecutivoCompania = '".$clave."' ".
                 "AND A.iDeleted = '0' AND dFechaCaducidad >= CURDATE() AND (D.iConsecutivo != '4' AND D.iConsecutivo != '6' AND D.iConsecutivo != '7' AND D.iConsecutivo != '8' AND D.iConsecutivo != '9') ".
                 "ORDER BY sNumeroPoliza ASC"; 
          $result = $conexion->query($sql);
          $rows   = $result->num_rows;
          
          if($rows > 0) {   
                while ($items = $result->fetch_assoc()) { 
                   
                   // polizas a las que aplica PD 
                   if($items['TipoPoliza'] == '1' || $items['TipoPoliza'] == '11' || $items['TipoPoliza'] == '12' || $items['TipoPoliza'] == '14' || $items['TipoPoliza'] == '17'){
                        $pd_information = 'true'; 
                   }
                   
                   $htmlTabla .= "<tr>".
                                 "<td style=\"border: 1px solid #dedede;\">".
                                 "<input id=\"chk_policies_".$items['iConsecutivo']."\" name=\"chk_policies\" type=\"checkbox\" value=\"".$items['iConsecutivo']."\" class=\"".$items['sAlias']."\" onchange=\"fn_claims.revisar_tipos_polizas();\"/>".
                                 "<label class=\"check-label\">".$items['sNumeroPoliza']."</label>".
                                 "</td>".
                                 "<td style=\"border: 1px solid #dedede;\">".$items['BrokerName']."</td>". 
                                 "<td style=\"border: 1px solid #dedede;\">".$items['sDescripcion']."</td>".
                                 "</tr>";
          
                        
                }                                                                                                                                                                       
          }else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";}
          
          $conexion->rollback();
          $conexion->close();
          $response = array("mensaje"=>"$mensaje","error"=>"$error", "policies_information"=>"$htmlTabla","pd_data"=>"$pd_information",);   
          echo json_encode($response);
          
      }   
      
      function get_drivers(){
          
          include("cn_usuarios.php");
          $conexion->autocommit(FALSE);
      
          $iConsecutivoCompania = urldecode($_GET['iConsecutivoCompania']);
          $term                 = urldecode($_GET['term']);
          $term                 = utf8_decode(trim($term));
          
          $query    = "SELECT DISTINCT A.iConsecutivo AS id, A.sNombre AS nombre, A.iNumLicencia AS clave, iConsecutivoCompania, B.iConsecutivoPoliza AS poliza ".
                      "FROM      ct_operadores      AS A ".
                      "LEFT JOIN cb_poliza_operador AS B ON A.iConsecutivo = B.iConsecutivoOperador ".  
                      "WHERE  A.iDeleted='0' AND iConsecutivoCompania = '".$iConsecutivoCompania."' ".
                      "AND (sNombre LIKE '%" . $term . "%' OR iNumLicencia LIKE '%" . $term . "%') ".
                      "GROUP BY iConsecutivoOperador ORDER BY sNombre LIMIT 25 ";
          $result   = $conexion->query($query) or die($conexion->error);
          $rows     = $result->num_rows; 
          $respuesta= array();
          
          if($rows > 0){  
            $r = 0;
            while ($response = $result->fetch_assoc()){ 
                  $respuesta[$r]["id"]     = $response["id"];
                  $respuesta[$r]["clave"]  = $response["clave"];
                  $respuesta[$r]["nombre"] = utf8_encode($response["nombre"]);
                  $r++;
            }
          }
          $conexion->rollback();
          $conexion->close();
          echo json_encode($respuesta);
      }
      
      function get_vehicles(){
          include("cn_usuarios.php");
          $conexion->autocommit(FALSE);
      
          $iConsecutivoCompania = urldecode($_GET['iConsecutivoCompania']);
          $term                 = urldecode($_GET['term']);
          $term                 = utf8_decode(trim($term));
          
          $query    = "SELECT DISTINCT A.iConsecutivo AS id, A.sVIN AS nombre, iConsecutivoCompania, B.iConsecutivoPoliza AS poliza  ".
                      "FROM      ct_unidades        AS A ".
                      "LEFT JOIN cb_poliza_unidad   AS B ON A.iConsecutivo = B.iConsecutivoUnidad  ".  
                      "WHERE  A.iDeleted='0' AND iConsecutivoCompania = '".$iConsecutivoCompania."' ".
                      "AND sVIN LIKE '%".$term."%' ".
                      "GROUP BY iConsecutivoUnidad ORDER BY sVIN LIMIT 25 ";
          $result   = $conexion->query($query) or die($conexion->error);
          $rows     = $result->num_rows; 
          $respuesta= array();
          
          if($rows > 0){  
            $r = 0;
            while ($response = $result->fetch_assoc()){ 
                  $respuesta[$r]["id"]     = $response["id"];
                  $respuesta[$r]["nombre"] = utf8_encode($response["nombre"]);
                  $r++;
            }
          }
          $conexion->rollback();
          $conexion->close();
          echo json_encode($respuesta);    
      }
?>
