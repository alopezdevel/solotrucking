<?php
      session_start();
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
                                $class = "class = \"blue\""; 
                                $btn_confirm = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"Edit Data\"><i class=\"fa fa-pencil-square-o\"></i><span></span></div>".
                                               "<div class=\"btn_send_claim btn-icon send-email btn-left\" title=\"Send Claim by E-mail\"><i class=\"fa fa-envelope\"></i><span></span></div>";  
                             break;
                             case 'INPROCESS': 
                                $class = "class = \"yellow\""; 
                                $btn_confirm = ""; 
                             break;
                             case 'APPROVED': 
                                $class = "class = \"green\"";
                                $btn_confirm =""; 
                                break;
                             case 'CANCELED': 
                                $class = "class = \"red\"";
                                $btn_confirm = "";    
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
                                        "<td>".$items['eStatus']."</td>".
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
          
          $sql = "SELECT A.iConsecutivo,A.iConsecutivoCompania,sMensaje,DATE_FORMAT(dHoraIncidente,'%H:%i') AS dHoraIncidente, DATE_FORMAT(dFechaIncidente,'%m/%d/%Y') AS dFechaIncidente,sCiudad,sEstado,CONCAT(B.iConsecutivo,'-',sNombre) AS sDriver, CONCAT(C.iConsecutivo,'-',sVIN) AS sUnitTrailer ".
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
                if($i == 'sMensaje'){
                   $descripcion = utf8_decode($datos[$i]); 
                }else{
                   $fields .= "\$('#$domroot :input[id=".$i."]').val('".htmlentities($datos[$i])."');\n"; 
                }
            }
          }
          $conexion->rollback();
          $conexion->close(); 
          $response = array("msj"=>"$msj","error"=>"$error","fields"=>"$fields","descripcion"=>"$descripcion");   
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
          
          
          #PASO 1 REVISAR SI EL DRIVER O UNIDAD EXISTE y Traer Datos:
          if($_POST['sDriver'] != ''){  //<--- Driver
                $driverdata = explode('-',$_POST['sDriver']); 
                $driverID   = $driverdata[0];
                $driverName = $driverdata[1];
                
                if($driverID != ''){
                    
                    $query = "SELECT iConsecutivo,iNumLicencia,eTipoLicencia,siConsecutivosPolizas,inPoliza ".
                             "FROM ct_operadores ".
                             "WHERE iConsecutivo='$driverID'";
                    $result = $conexion->query($query);
                    $rows = $result->num_rows;
                    if($rows > 0 ){
                       $driver = $result->fetch_assoc(); 
                    }else{
                       $error = '1';
                       $mensaje = "Error: The data driver was not found"; 
                    }
                    
                }else{
                    $error = '1';
                    $mensaje = "Error: The data driver are invalid";
                }  
          }else{$driverID="";}
          if($_POST['sUnitTrailer'] != ''){  //<--- UnitTrailer
                $unitdata = explode('-',$_POST['sUnitTrailer']); 
                $unitID   = $unitdata[0];
                $unitName = $unitdata[1];
                
                if($unitID != ''){
                    
                    $query = "SELECT iConsecutivo,iConsecutivoRadio,iYear,iModelo,sVIN,siConsecutivosPolizas,inPoliza ".
                             "FROM ct_unidades ".
                             "WHERE iConsecutivo='$unitID'";
                    $result = $conexion->query($query);
                    $rows = $result->num_rows;
                    if($rows > 0 ){
                       $unittrailer = $result->fetch_assoc(); 
                    }else{
                       $error = '1';
                       $mensaje = "Error: The data Unit / Trailer was not found"; 
                    }
                    
                }else{
                    $error = '1';
                    $mensaje = "Error: The data Unit / Trailer are invalid";
                }  
          }else{$unitID = "";}
          
          if($unitID != "" || $driverID != ""){
              if($_POST['edit_mode'] == 'true'){
                #UPDATE DATA:
                foreach($_POST as $campo => $valor){
                    if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivo" and $campo != "sDriver" and $campo != "sUnitTrailer"){ //Estos campos no se insertan a la tabla
                        array_push($valores,"$campo='".trim($valor)."'"); 
                    }
                }
                
                #ACTUALIZA DATOS: 
                array_push($valores ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
                array_push($valores ,"sIP='".$_SERVER['REMOTE_ADDR']."'");
                array_push($valores ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'");
                if($driverID != "") array_push($valores ,"iConsecutivoOperador='".trim($driverID)."'");
                if($unitID != "")   array_push($valores ,"iConsecutivoUnidad='".trim($unitID)."'"); 
                
                if($driverID != '' && $unitID != ''){array_push($valores ,"eCategoria='BOTH'");}
                else if($driverID != ''){array_push($valores ,"eCategoria='DRIVER'");}
                else if($unitID != ''){array_push($valores ,"eCategoria='UNIT/TRAILER'"); } 
                
                $sql = "UPDATE cb_claims SET ".implode(",",$valores)." WHERE iConsecutivo = '".trim($_POST['iConsecutivo'])."' AND iConsecutivoCompania = '$company'";
                $mensaje = "The data was updated successfully.";
                
              }else{
                #INSERT DATA:
                foreach($_POST as $campo => $valor){
                    if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivo" and $campo != "sDriver" and $campo != "sUnitTrailer"){ //Estos campos no se insertan a la tabla
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
                if($driverID != ""){array_push($campos,"iConsecutivoOperador"); array_push($valores,trim($driverID));}
                if($unitID != "")  {array_push($campos,"iConsecutivoUnidad"); array_push($valores,trim($unitID));}
                if($driverID != '' && $unitID != ''){array_push($campos,"eCategoria"); array_push($valores,'BOTH');}
                else if($driverID != ''){array_push($campos,"eCategoria"); array_push($valores,'DRIVER');}
                else if($unitID != ''){array_push($campos,"eCategoria"); array_push($valores,'UNIT/TRAILER');} 
                
                $sql = "INSERT INTO cb_claims (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')";
                $mensaje = "The data was saved successfully.";  
              }
              
              //TRANSACTION...
              if($sql != ""){
                  if($conexion->query($sql)){
                      $conexion->commit();
                      $conexion->close();
                  }else{
                     $conexion->rollback();
                     $conexion->close(); 
                     $error = "1";
                     $mensaje = "The data was not saved properly, please try again.";
                  }
                  
              }else{
                $error = '1';
                $mensaje = "Error: Please try again later.";  
              }   
          }else{
              $error = '1';
              $mensaje = "Error: Please select a driver or unit/trailer of your lists.";
          }
          
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
            if($data['eCategoria'] == 'DRIVER'){
                
            }else if($data['eCategoria'] == 'UNIT/TRAILER'){  
                
            }else if($data['eCategoria'] == 'BOTH'){ 
                
                
            }
            
            /*$llaves  = array_keys($data);
            $datos   = $data;
            foreach($datos as $i => $b){ 
                if($i == 'sMensaje'){
                   $descripcion = utf8_decode($datos[$i]); 
                }else{
                   $fields .= "\$('#$domroot :input[id=".$i."]').val('".htmlentities($datos[$i])."');\n"; 
                }
            } */
          }else{
              $error = '1';
              $msj = "Error: The Data ";
          }
          $conexion->rollback();
          $conexion->close(); 
          $response = array("msj"=>"$msj","error"=>"$error","fields"=>"$fields","descripcion"=>"$descripcion");   
          echo json_encode($response);
      }
?>
