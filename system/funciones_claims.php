<?php
  session_start();
  // Generic functions lib 
  include("functiones_genericas.php"); 
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId 
  
  /*----------------------------------------------------------------------   CLAIMS   ------------------------------------*/
  function get_data_grid(){
        include("cn_usuarios.php");
        $company = $_SESSION['company'];
        $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
        $transaccion_exitosa = true;
        $registros_por_pagina = $_POST["registros_por_pagina"];
        $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
        $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
            
        //Filtros de informacion //
        $filtroQuery = " WHERE iConsecutivoCompania = '".$company."' AND bEliminado = '0' ";
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
        $query_rows = "SELECT COUNT(iConsecutivo) AS total FROM cb_claims ".$filtroQuery;
                      
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
          $sql = "SELECT iConsecutivo, DATE_FORMAT(dFechaIncidente,'%m/%d/%Y') AS dFechaIncidente, ".
                 "DATE_FORMAT(dHoraIncidente,'%h:%i %p') AS dHoraIncidente, DATE_FORMAT(dFechaAplicacion,'%m/%d/%Y %H:%i') AS dFechaAplicacion, ".
                 "sEstado, sCiudad, eCategoria, sMensaje, eStatus ".
                 "FROM cb_claims ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
          $result = $conexion->query($sql);
          $rows = $result->num_rows; 
             
            if ($rows > 0) {    
                while ($items = $result->fetch_assoc()) { 
                   if($items["iConsecutivo"] != ""){
                        $btn_confirm = "";
                        $class = "";
                        switch($items["eStatus"]){
                             case 'EDITABLE':
                                $statusTitle  = "EDITABLE: Data can still be edited and has not yet been sent to solo-trucking employees."; 
                                $btn_confirm  = "<div class=\"btn_edit btn-icon edit       btn-left\" title=\"Edit Data\"><i class=\"fa fa-pencil-square-o\"></i> <span></span></div>";
                                $btn_confirm .= "<div class=\"btn_send btn-icon send-email btn-left\" title=\"Send Claim To Solo-Trucking\"><i class=\"fa fa-envelope\"></i><span></span></div>"; 
                                $btn_confirm .= "<div class=\"delete   btn-icon trash      btn-left\" title=\"Delete Claim\"><i class=\"fa fa-trash\"></i><span></span></div>"; 
                             break;
                             case 'SENT':
                                $statusTitle       = "SENT TO SOLO-TRUCKING: The data can be edited by you or by the employees of just-trucking.";
                                $items["eStatus"] .= " TO SOLO-TRUCKING"; 
                                $class             = "class = \"blue\""; 
                                $btn_confirm       = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"Edit Data\"><i class=\"fa fa-pencil-square-o\"></i><span></span></div>".
                                                     "<div class=\"btn_unsent btn-icon trash btn-left\" title=\"Cancel Sending\"><i class=\"fa fa-times\"></i><span></span></div>";  
                             break;
                             case 'INPROCESS':
                                $statusTitle  = "INPROCESS: Your claim has been sent to the insurers and is in process."; 
                                $class        = "class = \"yellow\""; 
                                $btn_confirm .= "<div class=\"btn-icon send-email btn-left\" title=\"Open claim data\" onclick=\"fn_claims.open_claim('".$items['iConsecutivo']."');\"><i class=\"fa fa-external-link\"></i><span></span></div>";  
                             break;
                             case 'APPROVED': 
                                $statusTitle = "APPROVED: Your claim has been approved successfully.";
                                $class       = "class = \"green\"";
                                $btn_confirm = ""; 
                                break;
                             case 'CANCELED': 
                                $statusTitle = "CANCELED: Your claim has been approved canceled, please see the reasons on the edit button."; 
                                $class       = "class = \"red\"";
                                $btn_confirm = "";    
                             break;
                         }

                         $htmlTabla .= "<tr $class>
                                            <td>".$items['iConsecutivo']."</td>".
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
  function get_company_policies(){
      include("cn_usuarios.php");   
      $company     = $_SESSION['company']; 
      $error       = "0";
      $mensaje     = "";
      $check_items = "";
      
      $query  = "SELECT A.iConsecutivo, sNumeroPoliza, C.sName AS BrokerName,D.iConsecutivo AS iTipoPoliza, sDescripcion, E.sName AS InsuranceName ".
                "FROM ct_polizas          AS A ".
                "LEFT JOIN ct_brokers     AS C ON A.iConsecutivoBrokers = C.iConsecutivo ". 
                "LEFT JOIN ct_tipo_poliza AS D ON A.iTipoPoliza = D.iConsecutivo ".
                "LEFT JOIN ct_aseguranzas AS E ON A.iConsecutivoAseguranza = E.iConsecutivo ".
                "WHERE A.iConsecutivoCompania = '$company' AND A.iDeleted = '0' AND dFechaCaducidad >= CURDATE()";
      $result = $conexion->query($query);
      $rows   = $result->num_rows;   
      
      if($rows > 0){    
        while ($items = $result->fetch_assoc()){
            
           switch($items['iTipoPoliza']){
               case '1' : $tipoPoliza = "PD"; break;
               case '2' : $tipoPoliza = "MTC"; break;
               case '3' : $tipoPoliza = "AL"; break;
               case '5' : $tipoPoliza = "MTC"; break;
           } 
           #checkbox
           $check_items .= "<input class=\"num_policies $tipoPoliza\" type=\"checkbox\" value=\"".$items['iConsecutivo']."\" style=\"display:none;\">".
                           "<label class=\"check-label  $tipoPoliza\" style=\"display:none;\"> ".$items['sNumeroPoliza']." / ".$items['sDescripcion']."</label><br>"; 
           #table
           if($items["sNumeroPoliza"] != ""){
                 $htmlTabla .= "<tr>".
                               "<td style=\"border: 1px solid #dedede;\">".$items['sNumeroPoliza']."</td>".
                               "<td style=\"border: 1px solid #dedede;\">".$items['BrokerName']."</td>".
                               "<td style=\"border: 1px solid #dedede;\">".$items['InsuranceName']."</td>". 
                               "<td style=\"border: 1px solid #dedede;\">".$items['sDescripcion']."</td>".
                               "</tr>";
                                   
           }else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";}    
        }
      }else{
          $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";
      }

      $response = array("checkboxes"=>"$check_items","mensaje"=>"$mensaje","error"=>"$error","policies_information"=>"$htmlTabla");   
      echo json_encode($response);
      
  }
  function cargar_tablaUD(){
      
      include("cn_usuarios.php");   
      $company = $_SESSION['company']; 
      $error = "0";
      $mensaje = "";
      $sFiltroQuery = trim($_POST['sFiltroQuery']);
      $iConsecutivosPolizas = explode(',',trim($_POST['iConsecutivosPolizas']));  
      $count = count($iConsecutivosPolizas);
      for ($i = 0; $i < $count; $i++) {
        $parametros == '' ? $parametros .= " siConsecutivosPolizas LIKE '%".$iConsecutivosPolizas[$i]."%' " : $parametros .= " OR siConsecutivosPolizas LIKE '%".$iConsecutivosPolizas[$i]."%' "; 
      }
     
      #1 - Seleccionamos parametros para hacer query:
      if($sFiltroQuery == 'D' || $sFiltroQuery == 'DU'){
           $query = "SELECT * FROM ct_operadores WHERE iConsecutivoCompania = '$company' AND inPoliza = '1' AND ($parametros)";
           $result = $conexion->query($query);
           $rows = $result->num_rows;   
           if($rows > 0){ 
           $htmlTabla .= "<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">DRIVER LIST</td><tr>";   
            while ($items = $result->fetch_assoc()){
               $htmlTabla .= "<tr>".
                             "<td><input class=\"list_tablaUD\" type=\"checkbox\" value=\"".$items['iConsecutivo']."\" style=\"width: 15px;height: 15px;\"><label class=\"check-label\" style=\"font-weight: normal;font-size: 11px;color: #464646;\"> ".$items['sNombre']."</label></td>".
                             "<tr>"; 
            }
           }else{
               $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>"; 
           }
      }
      if($sFiltroQuery == 'U' || $sFiltroQuery == 'DU'){
           $query = "SELECT * FROM ct_unidades WHERE iConsecutivoCompania = '$company' AND inPoliza = '1' AND ($parametros)";
           $result = $conexion->query($query);
           $rows = $result->num_rows;   
           if($rows > 0){ 
           $htmlTabla .= "<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">UNIT/TRAILER LIST</td><tr>";   
            while ($items = $result->fetch_assoc()){
               $htmlTabla .= "<tr>".
                             "<td><input class=\"list_tablaUD\" type=\"checkbox\" value=\"".$items['iConsecutivo']."\" style=\"width: 15px;height: 15px;\"><label class=\"check-label\" style=\"font-weight: normal;font-size: 11px;color: #464646;\"> ".$items['sVIN']."</label></td>".
                             "<tr>"; 
            }
           }else{
              $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";  
           }
      }
      
      $response = array("tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error");   
      echo json_encode($response); 
      
  }
  function get_drivers(){
    include("cn_usuarios.php");   
    $company = $_SESSION['company']; 
    $error = "0";
    $mensaje = "";
    $sFiltroQuery = trim($_POST['sFiltroQuery']);
    $iConsecutivosPolizas = explode(',',trim($_POST['iConsecutivosPolizas']));  
    $count = count($iConsecutivosPolizas);
    for ($i = 0; $i < $count; $i++) {
        $parametros == '' ? $parametros .= " siConsecutivosPolizas LIKE '%".$iConsecutivosPolizas[$i]."%' " : $parametros .= " OR siConsecutivosPolizas LIKE '%".$iConsecutivosPolizas[$i]."%' "; 
    }  

    $query = "SELECT * FROM ct_operadores WHERE iConsecutivoCompania = '$company' AND inPoliza = '1' AND ($parametros)"; 
    $result = $conexion->query($query);
    $rows = $result->num_rows; 
    $r = 0;  
    if($rows > 0){while ($response = $result->fetch_assoc()){$respuesta == '' ? $respuesta .= '"'.$response["iConsecutivo"].'-'.utf8_encode($response["sNombre"]).'"' : $respuesta .= ',"'.$response["iConsecutivo"].'-'.utf8_encode($response["sNombre"]).'"';}}
    $respuesta = "[".$respuesta."]";
    echo $respuesta; 
  }
  function get_units(){
    include("cn_usuarios.php");   
    $company = $_SESSION['company']; 
    $error = "0";
    $mensaje = "";
    $sFiltroQuery = trim($_POST['sFiltroQuery']);
    $iConsecutivosPolizas = explode(',',trim($_POST['iConsecutivosPolizas']));  
    $count = count($iConsecutivosPolizas);
    for ($i = 0; $i < $count; $i++) {
        $parametros == '' ? $parametros .= " siConsecutivosPolizas LIKE '%".$iConsecutivosPolizas[$i]."%' " : $parametros .= " OR siConsecutivosPolizas LIKE '%".$iConsecutivosPolizas[$i]."%' "; 
    }  

    $query = "SELECT iConsecutivo,iModelo,sVIN,sTipo,iYear FROM ct_unidades WHERE iConsecutivoCompania = '$company' AND inPoliza = '1' AND ($parametros) AND sVIN != ''"; 
    $result = $conexion->query($query);
    $rows = $result->num_rows; 
    $r = 0;  
    if($rows > 0){  
        while ($response = $result->fetch_assoc()){
            $respuesta == '' ? $respuesta .= '"'.$response["iConsecutivo"].'-'.$response["sVIN"].'/'.utf8_encode($response["iYear"]).'"' : $respuesta .= ',"'.$response["iConsecutivo"].'-'.$response["sVIN"].'/'.utf8_encode($response["iYear"]).'"';
        } 
    }
    $respuesta = "[".$respuesta."]";
    echo $respuesta; 
  }
  
  
  #ABC - CLAIMS...
  function save_claim(){
      $error = '0';  
      $msj = "";  
      
      //Conexion:
      include("cn_usuarios.php"); 
      $company = $_SESSION['company']; 
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
            else{
                $driverName = $_POST['sDriver'];
            }
            
        
      }
      else{$driverID="";}
      
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
          }
          else{$unitID = "";}
      }
                       
      
      
      if(($unitID != "" || $unitName != "") && ($driverID != "" || $driverName != "") && $error == "0"){
          if($_POST['edit_mode'] == 'true'){
            #UPDATE DATA:
            foreach($_POST as $campo => $valor){
                if($campo != "accion" && $campo != "edit_mode" && $campo != "iConsecutivo" && $campo != "sDriver" && $campo != "sUnitTrailer" && $campo != "iConsecutivoPolizas"){ //Estos campos no se insertan a la tabla
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
            
            $sql     = "UPDATE cb_claims SET ".implode(",",$valores)." WHERE iConsecutivo = '".trim($_POST['iConsecutivo'])."' ".
                       "AND iConsecutivoCompania = '$company'";
            $mensaje = "The data was updated successfully.";
            
          }else{
            #INSERT DATA:
            foreach($_POST as $campo => $valor){
                if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivo" and $campo != "sDriver" and $campo != "sUnitTrailer" and $campo != "iConsecutivoPolizas"){ //Estos campos no se insertan a la tabla
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
            
            $sql = "INSERT INTO cb_claims (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')";
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
          
              }else{ 
                 $error = "1";
                 $mensaje = "The data was not saved properly, please try again.";
              }
              
          }else{
            $error = '1';
            $mensaje = "Error: Please try again later.";  
          }   
      }
      else{$error = '1';$mensaje = "Error: Please select a valid driver or unit/trailer of your lists.";}
      
      if($transaccion_exitosa && $error == "0"){$conexion->commit();$conexion->close();}
      else{$conexion->rollback();$conexion->close();}
      
      $response = array("error"=>"$error","msj"=>"$mensaje", "iConsecutivoClaim" => "$iConsecutivoClaim");
      echo json_encode($response);
      
  }
  function edit_claim(){
      $error = '0';
      $msj = "";
      $fields = "";
      $clave = trim($_POST['clave']);
      $domroot = $_POST['domroot'];
      include("cn_usuarios.php");
      $company = $_SESSION['company'];
      $conexion->autocommit(FALSE);
      
      $sql = "SELECT A.iConsecutivo,A.iConsecutivoCompania,sMensaje,DATE_FORMAT(dHoraIncidente,'%H:%i') AS dHoraIncidente, DATE_FORMAT(dFechaIncidente,'%m/%d/%Y') AS dFechaIncidente,".
             "sCiudad,sEstado,IF(B.iConsecutivo != '',CONCAT(B.iConsecutivo, '-', sNombre),A.sNombreOperador) AS sDriver,".
             "IF(C.iConsecutivo != '', CONCAT(C.iConsecutivo, '-', sVIN), A.sVINUnidad) AS sUnitTrailer, ".
             "sDescripcionSuceso, eDanoTerceros, eDanoFisico, eDanoMercancia ".   
             "FROM cb_claims A ".
             "LEFT JOIN ct_operadores B ON A.iConsecutivoOperador = B.iConsecutivo ".
             "LEFT JOIN ct_unidades C ON A.iConsecutivoUnidad = C.iConsecutivo ".
             "WHERE A.iConsecutivo = '$clave' AND A.iConsecutivoCompania = '$company'";
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
      }
      $conexion->rollback();
      $conexion->close(); 
      $response = array("msj"=>"$msj","error"=>"$error","fields"=>"$fields","descripcion"=>"$descripcion");   
      echo json_encode($response);
  }
  function upload_files(){
      
      $error = "0";
      include("cn_usuarios.php");
      $company = $_SESSION['company'];
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
  function send_claim(){
      
      $iConsecutivo = trim($_POST['clave']);
      $error = '0'; 
      $msj = ""; 
      $valores = array(); 
      
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      #borar archivos de drivers sin un id de driver asignado:
      array_push($valores ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
      array_push($valores ,"sIP='".$_SERVER['REMOTE_ADDR']."'");
      array_push($valores ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'");
      array_push($valores ,"eStatus='SENT'");
      array_push($valores ,"dFechaAplicacion='".date("Y-m-d H:i:s")."'");
      
      $query = "UPDATE cb_claims SET ".implode(",",$valores)." WHERE iConsecutivo = '$iConsecutivo'";
      if($conexion->query($query)){
        $conexion->commit();
        $conexion->close();
        $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The claim has been sent succesfully!</p>';
      }else{
        $conexion->rollback();
        $conexion->close();
        $msj = "A general system error ocurred : internal error";
        $error = "1";
      }
        
      $response = array("msj"=>"$msj","error"=>"$error");   
      echo json_encode($response); 
  }
  function unsent_claim(){
      
      $iConsecutivo = trim($_POST['clave']);
      $error = '0'; 
      $msj = ""; 
      $valores = array(); 
      
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      #borar archivos de drivers sin un id de driver asignado:
      array_push($valores ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
      array_push($valores ,"sIP='".$_SERVER['REMOTE_ADDR']."'");
      array_push($valores ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'");
      array_push($valores ,"eStatus='EDITABLE'");
      array_push($valores ,"dFechaAplicacion=NULL");
      
      $query = "UPDATE cb_claims SET ".implode(",",$valores)." WHERE iConsecutivo = '$iConsecutivo'";
      if($conexion->query($query)){
        $conexion->commit();
        $conexion->close();
        $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The claim has been canceled succesfully!</p>';
      }else{
        $conexion->rollback();
        $conexion->close();
        $msj = "A general system error ocurred : internal error";
        $error = "1";
      }
        
      $response = array("msj"=>"$msj","error"=>"$error");   
      echo json_encode($response); 
  }
  function delete_claim(){
       $error = "0";
       $msj   = "";
       $clave = trim($_POST['iConsecutivo']);
       include("cn_usuarios.php");
       $company = $_SESSION['company'];
       $conexion->autocommit(FALSE);
       
       $query   = "UPDATE cb_claims SET bEliminado = '1' WHERE iConsecutivo = '$clave' AND iConsecutivoCompania = '$company'"; 
       $success = $conexion->query($query); 
       
       if(!($success)){
           $msj   = "Error deleting the record, please try again.";
           $error = "1";
           $conexion->rollback();
       }else{
           $conexion->commit(); 
           $msj = "The record has been successfully deleted.";
       }
       
       $conexion->close();  
       $response = array("mensaje"=>"$msj","error"=>"$error");   
       echo json_encode($response);
  }
  function open_claim(){
      
      $error              = '0';
      $msj                = "";
      $fields             = "";
      $iConsecutivoClaim  = trim($_POST['iConsecutivoClaim']);
      $company            = $_SESSION['company']; 
      $htmlTabla          = ""; 
      $styleheader        = "style=\"height: 30px;\"";
      $styleheader2       = "style=\"padding:5px!important;font-size:12px\"";
      $styleheader3       = "style=\"text-align:left;font-weight: bold;width:110px;\""; 
      $style              = "margin-bottom: 3px;color: #016bba!important;text-transform: uppercase;"; 
      
      include("cn_usuarios.php"); 
      
      #DATA OF CLAIM:
      $sql    = "SELECT A.iConsecutivo,A.iConsecutivoCompania,sMensaje,DATE_FORMAT(dHoraIncidente,'%h:%i %p') AS dHoraIncidente, DATE_FORMAT(dFechaIncidente,'%m/%d/%Y') AS dFechaIncidente,sNombre AS sDriver, sVIN AS sUnitTrailer, ".
                "iYear AS UnitYear, sTipo AS UnitType, E.sDescripcion AS UnitMake, iNumLicencia, (CASE WHEN eTipoLicencia = '1' THEN 'Federal/B1' WHEN eTipoLicencia = '2' THEN 'Commercial/CDL - A' END) AS eTipoLicencia, ".
                "B.siConsecutivosPolizas AS polizas_operador, C.siConsecutivosPolizas AS polizas_unidad, sNombreCompania, A.sEstado, A.sCiudad, eCategoria, sMensaje, eStatus, sDescripcionSuceso, sVINUnidad, sNombreOperador ".
                "FROM cb_claims A ".
                "LEFT JOIN ct_operadores B ON A.iConsecutivoOperador = B.iConsecutivo ".
                "LEFT JOIN ct_unidades   C ON A.iConsecutivoUnidad   = C.iConsecutivo ".
                "LEFT JOIN ct_companias  D ON A.iConsecutivoCompania = D.iConsecutivo ".
                "LEFT JOIN ct_unidad_modelo E ON C.iModelo = E.iConsecutivo ".
                "WHERE A.iConsecutivo = '$iConsecutivoClaim'"; 
      $result = $conexion->query($sql); 
      $dClaim = $result->fetch_assoc();  //<-- Datos del Claim. 
      
      $CompanyName = $dClaim['sNombreCompania'];
      $DateofLoss  = $dClaim['dFechaIncidente'];
      $HourofLoss  = $dClaim['dHoraIncidente'];
      $ClaimCity   = $dClaim['sCiudad'];
      $ClaimState  = $dClaim['sEstado'];
      $ClaimAppend = $dClaim['sDescripcionSuceso']; 
      
      $htmlTabla  .= "<table style=\"font-size:12px;border:1px solid #6191df;border-radius:3px;padding:5px;width:100%; margin:5px auto;font-family: Arial, Helvetica, sans-serif;\">"; 
      
      //HTML - Claim Data:
      $htmlTabla .= "<tr><td colspan=\"100%\">".
                    "<table style=\"width:100%;font-family: Arial, Helvetica, sans-serif!important;\">".
                        "<tr class=\"grid-head2\" $styleheader><td colspan=\"2\" class=\"etiqueta_grid\" $styleheader2>DATA OF INCIDENT</td></tr>".
                        "<tr><td $styleheader3>Date and Hour:</td><td>$DateofLoss at $HourofLoss</td></tr>".
                        "<tr><td $styleheader3>State and City:</td><td>$ClaimCity, $ClaimState</td></tr>". 
                        "<tr><td $styleheader3>What happend?:</td><td>$ClaimAppend</td></tr>". 
                    "</table><br>".
                    "</td></tr>";
      $htmlTabla .= "<tr><td></td></tr>";
      
      //HTML - Driver:
      $htmlDriver = "";
      if($dClaim['sDriver'] != '' && ($dClaim['eCategoria'] = 'BOTH' || $dClaim['eCategoria'] = 'DRIVER')){  
          
          $DriverName     = $dClaim['sDriver'];
          $DriverLicense  = $dClaim['iNumLicencia'];
          $DriverLicenseT = $dClaim['eTipoLicencia'];
          
          $htmlDriver .= "<table style=\"width:100%;font-family: Arial, Helvetica, sans-serif!important;\">".
                         "<tr class=\"grid-head2\" $styleheader><td colspan=\"2\" class=\"etiqueta_grid\" $styleheader2>DATA OF DRIVER</td></tr>".
                         "<tr><td $styleheader3>Name:</td><td>$DriverName</td></tr>";
                        
          if($DriverLicense != ""){
             $htmlDriver .= "<tr><td $styleheader3>License Number:</td><td>$DriverLicense</td></tr>";
          }
          
          if($DriverLicenseT != ""){
             $htmlDriver .= "<tr><td $styleheader3>License Type?:</td><td>$DriverLicenseT</td></tr>"; 
          }
  
          $htmlDriver .= "</table>";
      }
      else if($dClaim['sNombreOperador'] != "" && ($dClaim['eCategoria'] = 'BOTH' || $dClaim['eCategoria'] = 'DRIVER')){
          $DriverName = $dClaim['sNombreOperador'];

          $htmlDriver .= "<table style=\"width:100%;font-family: Arial, Helvetica, sans-serif!important;\">".
                         "<tr class=\"grid-head2\" $styleheader><td colspan=\"2\" class=\"etiqueta_grid\" $styleheader2>DATA OF DRIVER</td></tr>".
                         "<tr><td $styleheader3>Name:</td><td>$DriverName</td></tr>".
                         "</table>";
      }
      
      //HTML - Unidad/Trailer:
      $htmlUnit = "";
      if($dClaim['sUnitTrailer'] != '' && ($dClaim['eCategoria'] = 'BOTH' || $dClaim['eCategoria'] = 'UNIT/TRAILER')){
          
          $dClaim['UnitType'] == 'UNIT' ? $type = 'UNIT' : $type = 'TRAILER';
          $UnitVIN    = $dClaim['sUnitTrailer'];
          $UnitYear   = $dClaim['UnitYear'];
          $UnitMake   = $dClaim['UnitMake'];
          
          $htmlUnit .= "<table style=\"width:100%;font-family: Arial, Helvetica, sans-serif!important;\">".
                        "<tr class=\"grid-head2\" $styleheader><td colspan=\"2\" class=\"etiqueta_grid\" $styleheader2>DATA OF $type</td></tr>".
                        "<tr><td $styleheader3>VIN#:</td><td>$UnitVIN</td></tr>";
          
          if($UnitYear != ""){$htmlUnit .= "<tr><td $styleheader3>Year:</td><td>$UnitYear</td></tr>";}
          if($UnitMake)      {$htmlUnit .= "<tr><td $styleheader3>Make:</td><td>$UnitMake</td></tr>";}
             
          $htmlUnit .= "</table>";
      }
      else if($dClaim['sVINUnidad'] != "" && ($dClaim['eCategoria'] = 'BOTH' || $dClaim['eCategoria'] = 'UNIT/TRAILER')){
          
          $UnitVIN    = $dClaim['sVINUnidad'];

          $htmlUnit .= "<table style=\"width:100%;font-family: Arial, Helvetica, sans-serif!important;\">".
                        "<tr class=\"grid-head2\" $styleheader><td colspan=\"2\" class=\"etiqueta_grid\" $styleheader2>DATA OF UNIT/TRAILER</td></tr>".
                        "<tr><td $styleheader3>VIN#:</td><td>$UnitVIN</td></tr>".
                        "</table>";
          
      }
      
      $htmlTabla .= "<tr>";
      if($htmlDriver != ""){$htmlTabla .= "<td style=\"vertical-align: top;\">$htmlDriver</td>";}
      if($htmlUnit != "")  {$htmlTabla .= "<td style=\"vertical-align: top;\">$htmlUnit</td>";}
      $htmlTabla .= "</tr>";
      $htmlTabla .= "<tr><td colspan=\"100%\"></td></tr>"; // pasar linea. 
 
      //Consultar Polizas del Claim:
      $query   =  "SELECT A.iConsecutivoPoliza, B.sNumeroPoliza, B.iTipoPoliza, A.eStatus, A.sNombreAjustador, A.sNumeroClaimAseguranza, A.sEmailAjustador, ".
                  "CONCAT(A.sTelefonoAjustador,' Ext. ',A.sTelefonoExtAjustador) AS sTelefonoAjustador, A.sComentarios, C.sDescripcion, sName AS InsuranceName, A.sComentarios ".
                  "FROM cb_claim_poliza       AS A ".
                  "INNER JOIN ct_polizas      AS B ON A.iConsecutivoPoliza = B.iConsecutivo ".
                  "LEFT JOIN  ct_tipo_poliza  AS C ON B.iTipoPoliza = C.iConsecutivo ".
                  "LEFT JOIN  ct_aseguranzas  AS D ON B.iConsecutivoAseguranza = D.iConsecutivo ".
                  "WHERE A.iConsecutivoClaim = '$iConsecutivoClaim'";
      $result   = $conexion->query($query); 

      $htmlTabla .= "<tr><td colspan=\"100%\">".
                    "<table style=\"width:100%;border-collapse: collapse;\">";
      while ($items = $result->fetch_assoc()) {
          
         $sNumPoliza = $items['sNumeroPoliza'];
         $sDescPoliza= $items['sDescripcion']; 
         $insurance  = $items['InsuranceName'];
         $noClaim    = $items['sNumeroClaimAseguranza'];
         $Status     = $items['eStatus']; 
         $Comments   = $items['sComentarios'];
         $Name       = $items['sNombreAjustador'];
         $email      = $items['sEmailAjustador'];
         $phone      = $items['sTelefonoAjustador'];
      
         $htmlTabla .= "<tr class=\"grid-head2\">".
                       "<td colspan=\"100%\" style=\"font-size:11px!important;\">".
                       "<table style=\"width:100%;border-collapse: collapse;\">".
                       "<tr>".
                       "<td class=\"etiqueta_grid\" style=\"width:25%;border:0px!important;padding:5px!important;font-size:12px!important;\">"."<span style=\"$style\">Policy No: </span>$sNumPoliza"."</td>".
                       "<td class=\"etiqueta_grid\" style=\"width:40%;border:0px!important;padding:5px!important;font-size:12px!important;\">"."<span style=\"$style\">Policy Type:</span> $sDescPoliza"."</td>".  
                       "<td class=\"etiqueta_grid\" style=\"width:33%;border:0px!important;padding:5px!important;font-size:12px!important;\">"."<span style=\"$style\">Insurance:</span> $insurance". "</td>".  
                       "</tr>".
                       "</table>".
                       "</td>".
                       "</tr>";
         
         //encabezado2:
         $encabezado2= "style=\"width:50%;color: #fff;text-align: center;height:auto!important;padding:5px!important;\"";              
         $htmlTabla .= "<tr class=\"grid-head1\" style=\"height: 30px!important;\">".
                       "<td $encabezado2>CLAIM DATA</td>".
                       "<td $encabezado2>ADJUSTER DATA</td>". 
                       "</tr>";
                                  
         //Claim Data:
         $htmlTabla .= "<tr>";
         $htmlTabla .= "<td style=\"vertical-align:top;\">".
                       "<table style=\"width:100%\">".
                            "<tr><td $styleheader3>No. Claim:</td><td>$noClaim</td><tr>".
                            "<tr><td $styleheader3>Status: </td><td>$Status</td><tr>". 
                            "<tr><td $styleheader3>Comments:</td><td>$Comments</td><tr>". 
                       "</table>".
                       "</td>";
       //Adjuster Data:
       $htmlTabla  .= "<td style=\"vertical-align:top;\">".
                      "<table style=\"width:100%\">".
                            "<tr><td $styleheader3>Name:</td><td>$Name</td><tr>".
                            "<tr><td $styleheader3>Phone: </td><td>$phone</td><tr>". 
                            "<tr><td $styleheader3>E-mail:</td><td>$email</td><tr>". 
                      "</table>". 
                      "</td>";
       
       $htmlTabla .= "</tr>";
       //Salto de linea:              
       $htmlTabla .= "<tr><td colspan=\"100%\"></td></tr>"; 
          
          
      }
      
      $htmlTabla .= "</table></td></tr>";
      $htmlTabla .= "</table>";
      
      $conexion->close(); 
      $response = array("error"=>"$error","mensaje"=>"$mensaje","html"=>"$htmlTabla");
      echo json_encode($response); 
       
  }
  
  #Funcion para solo-trucking users:
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
  
?>
