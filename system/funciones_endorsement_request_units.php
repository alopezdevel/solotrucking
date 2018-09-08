<?php
  session_start();
  // Generic functions lib 
  include("functiones_genericas.php"); 
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId 
  
  function get_endorsements(){
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $registros_por_pagina = $_POST["registros_por_pagina"];
      $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
      $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
        
     //Filtros de informacion //
     $filtroQuery = " WHERE A.eStatus != 'E' AND A.iConsecutivoTipoEndoso = '1' ";
     $array_filtros = explode(",",$_POST["filtroInformacion"]);
     foreach($array_filtros as $key => $valor){
        if($array_filtros[$key] != ""){
            $campo_valor = explode("|",$array_filtros[$key]);
            if($campo_valor[0] == 'iConsecutivo'){ 
                $filtroQuery.= " AND  ".$campo_valor[0]."='".$campo_valor[1]."' ";
            }else{
                if($campo_valor[0] == 'eStatus'){
                     $filtroQuery .= " AND  ".$campo_valor[0]." = '".$campo_valor[1]."'";
                }else{
                     $filtroQuery.= " AND  ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'";
                }   
            } 
        }
     }
     // ordenamiento//
     $ordenQuery = " ORDER BY ".$_POST["ordenInformacion"]." ".$_POST["sortInformacion"];

    //contando registros // 
    $query_rows = "SELECT COUNT(A.iConsecutivo) AS total ".
                  "FROM cb_endoso AS A ".
                  "LEFT JOIN ct_tipo_endoso AS C ON A.iConsecutivoTipoEndoso = C.iConsecutivo ".
                  "LEFT JOIN ct_companias   AS D ON A.iConsecutivoCompania = D.iConsecutivo ".
                  "LEFT JOIN ct_unidades    AS F ON A.iConsecutivoUnidad = F.iConsecutivo ".$filtroQuery;
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
      $sql = "SELECT A.iConsecutivo,D.sNombreCompania,DATE_FORMAT( A.dFechaAplicacion, '%m/%d/%Y %H:%i' ) AS dFechaIngreso,C.sDescripcion,A.eStatus,eAccion,D.iOnRedList,sVIN ".
             "FROM cb_endoso AS A ".
             "LEFT JOIN ct_tipo_endoso AS C ON A.iConsecutivoTipoEndoso = C.iConsecutivo ".
             "LEFT JOIN ct_companias   AS D ON A.iConsecutivoCompania = D.iConsecutivo ".
             "LEFT JOIN ct_unidades    AS F ON A.iConsecutivoUnidad = F.iConsecutivo ".
             $filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
      $result = $conexion->query($sql);
      $rows = $result->num_rows; 
         
        if ($rows > 0) {    
            while ($usuario = $result->fetch_assoc()) { 
               if($usuario["iConsecutivo"] != ""){
                     $btn_confirm = "";
                     $estado = "";
                     $class = "";
                     $descripcion = ""; 
                     switch($usuario["eStatus"]){
                         case 'S': 
                            $estado = 'NEW APPLICATION';
                            $class = "class = \"blue\"";
                            $btn_confirm = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"View and Edit Endorsement Status\"><i class=\"fa fa-pencil-square-o\"></i> <span></span>".
                                           "</div><div class=\"btn_edit_estatus btn-icon send-email btn-left\" title=\"Send e-mail to the brokers\"><i class=\"fa fa-envelope\"></i><span></span></div>"; 
                         break;
                         case 'A': 
                            $estado = 'APPROVED';
                            $class = "class = \"green\"";
                            $btn_confirm = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"View and Edit Endorsement Status\"><i class=\"fa fa-pencil-square-o\"></i> <span></span>"; 
                         break;
                         case 'D': 
                            $estado = 'DENIED';
                            $class = "class = \"red\"";
                            $btn_confirm = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"View and Edit Endorsement Status\"><i class=\"fa fa-pencil-square-o\"></i> <span></span>";
                                        
                         break;
                         case 'SB': 
                            $estado = 'SENT TO BROKERS';
                            $class = "class = \"yellow\"";
                            $btn_confirm = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"View and Edit Endorsement Status\"><i class=\"fa fa-pencil-square-o\"></i> <span></span>"; 
                         break;
                         case 'P': 
                            $estado = 'IN PROCESS';
                            $class = "class = \"orange\"";
                            $btn_confirm = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"View and Edit Endorsement Status\"><i class=\"fa fa-pencil-square-o\"></i> <span></span>".
                                           "</div><div class=\"btn_send_email btn-icon send-email btn-left\" title=\"Send e-mail to the brokers\"><i class=\"fa fa-envelope\"></i><span></span></div>"; 
                         break;
                     } 
                     $color_action = "";
                     $action = "";
                     switch($usuario["eAccion"]){
                         case 'A': 
                            $action = 'ADD';
                            $color_action = "color:#00970d"; 
                         break;
                         case 'D': 
                            $action = 'DELETE'; 
                            $color_action = "color:#ab0000"; 
                         break;
                     }
                     
                     
                      //Redlist:
                     $usuario['iOnRedList'] == '1' ? $redlist_icon = "<i class=\"fa fa-star\" style=\"color:#e8051b;margin-right:4px;\"></i>" : $redlist_icon = ""; 
                     $htmlTabla .= "<tr $class>
                                        <td>".$usuario['iConsecutivo']."</td>".
                                       "<td>".$redlist_icon.$usuario['sNombreCompania']."</td>".
                                       "<td>".strtoupper($usuario['sVIN'])."</td>". 
                                       "<td style=\"$color_action\">".$action."</td>".
                                       "<td class=\"text-center\">".$usuario['dFechaIngreso']."</td>". 
                                       "<td class=\"text-center\">".$estado."</td>".                                                                                                                                                                                                                       
                                       "<td> $btn_confirm</td></tr>";
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
  function get_endorsement(){
      #Err flags:
      $error = '0';
      $msj   = "";
      #Variables
      $fields   = "";
      $clave    = trim($_POST['clave']);
      $idPoliza = trim($_POST['idPoliza']);
      $domroot  = $_POST['domroot'];
      
      #Function Begin
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                 
      $sql    = "SELECT A.iConsecutivo, iConsecutivoCompania, iConsecutivoTipoEndoso, A.eStatus, iReeferYear,iTrailerExchange, iPDAmount, ". 
                "A.sComentarios, iPDApply, iConsecutivoUnidad, eAccion   ".  
                "FROM cb_endoso A ".
                "LEFT JOIN ct_tipo_endoso B ON A.iConsecutivoTipoEndoso = B.iConsecutivo ". 
                "WHERE A.iConsecutivo = '$clave'";
      $result = $conexion->query($sql);
      $items  = $result->num_rows; 
      if ($items > 0) {     
            
            $data    = $result->fetch_assoc(); //<---Endorsement Data Array.
            $llaves  = array_keys($data);
            $datos   = $data; 
            
            foreach($datos as $i => $b){ 
                if($i != 'eStatus' && $i != 'sComentarios' && $i != 'iConsecutivoPoliza' && $i != 'iConsecutivoTipoEndoso'){
                  $fields .= "\$('#$domroot :input[id=".$i."]').val('".$datos[$i]."');";  
                }else if($i == 'sComentarios'){
                  $comentarios = utf8_decode($datos[$i]);  
                }    
            }
            
            //CONSULTAMOS LA TABLA DE POLIZAS LIGADA AL ENDOSO               
            $query  = "SELECT iConsecutivoPoliza, B.iTipoPoliza ".
                      "FROM cb_endoso_estatus AS A INNER JOIN ct_polizas AS B ON A.iConsecutivoPoliza = B.iConsecutivo AND B.iDeleted = '0' ".
                      "WHERE iConsecutivoEndoso = '$clave'"; 
            $result = $conexion->query($query);
            $rows   = $result->num_rows; 
            if($rows > 0){
                while($policies = $result->fetch_assoc()){
                   $policies_checkbox .= "\$('#$domroot :checkbox[value=".$policies['iConsecutivoPoliza']."]').prop('checked',true);";
                   //SI LA POLIZA ES DE PD
                   if($policies['iTipoPoliza'] == '1'){
                       $policies_checkbox.= "\$('#$domroot :input[name=iPDAmount]').removeProp('readonly');"; 
                   }            
                }
                       
            }  
                
            //SI LA UNIDAD EXISTE EN EL CATALOGO:
            if($data['iConsecutivoUnidad'] != '' && $data['iConsecutivoTipoEndoso']== '1'){
                $sql2 = "SELECT iConsecutivo AS iConsecutivoUnidad, iConsecutivoRadio, iYear, iModelo, sVIN AS sUnitTrailer, sModelo, sTipo ".    
                        "FROM ct_unidades A ".
                        "WHERE A.iConsecutivo = '".$data['iConsecutivoUnidad']."'";
                $result = $conexion->query($sql2);
                $items2 = $result->num_rows;
                if ($items2 > 0) {
                    
                     $data2    = $result->fetch_assoc();
                     $llaves2  = array_keys($data2);
                     $datos2   = $data2;
                     
                     foreach($datos2 as $i => $b){     
                        $fields .= "\$('#$domroot :input[id=".$i."]').val('".$datos2[$i]."');";
                     }
                }
            }
            $eStatus    = $data['eStatus'];  
      }
      $conexion->rollback();
      $conexion->close(); 
      $response = array(
                    "msj"=>"$msj",
                    "error"=>"$error",
                    "fields"=>"$fields",
                    "policies"=>"$policies_checkbox",
                    "sComentarios" => "$comentarios"
                  );   
      echo json_encode($response);  
      
  }
  function get_policies(){
      
      include("cn_usuarios.php");
      $company = trim($_POST['iConsecutivoCompania']);
      $conexion->autocommit(FALSE);
      $error          = '0';
      $pd_information = "false";
      
      $sql = "SELECT A.iConsecutivo, sNumeroPoliza, C.sName AS BrokerName, sDescripcion, D.iConsecutivo AS TipoPoliza ".
             "FROM ct_polizas A ".
             "LEFT JOIN ct_brokers C ON A.iConsecutivoBrokers = C.iConsecutivo ".
             "LEFT JOIN ct_tipo_poliza D ON A.iTipoPoliza = D.iConsecutivo ".
             "WHERE iConsecutivoCompania = '".$company."' ".
             "AND  A.iDeleted = '0' AND dFechaCaducidad >= CURDATE() AND (D.iConsecutivo = '1' OR D.iConsecutivo = '3' OR D.iConsecutivo = '5' OR D.iConsecutivo = '2') ".
             "ORDER BY sNumeroPoliza ASC";  
      $result = $conexion->query($sql);
      $rows = $result->num_rows;
      
      if($rows > 0) {   
            while ($items = $result->fetch_assoc()) { 
                $pdvalid = "";
                switch($items['TipoPoliza']){
                     case '1' : 
                        $pd_information = 'true'; 
                        $pdvalid = "onchange=\"if(\$(this).prop('checked')){\$('#frm_endorsement_information input[name=iPDAmount]').removeProp('readonly').removeClass('readonly');}".
                                   "else{\$('#frm_endorsement_information input[name=iPDAmount]').prop('readonly','readonly').addClass('readonly');}\"";
                     break;
                }
               
               $htmlTabla .= "<tr>".
                             "<td style=\"border: 1px solid #dedede;\">".
                             "<input id=\"chk_policies_".$items['iConsecutivo']."\"name=\"chk_policies_endoso\" type=\"checkbox\" value=\"".$items['iConsecutivo']."\" $pdvalid/>".
                             "<label class=\"check-label\">".$items['sNumeroPoliza']."</label>".
                             "</td>".
                             "<td style=\"border: 1px solid #dedede;\">".$items['BrokerName']."</td>". 
                             "<td style=\"border: 1px solid #dedede;\">".$items['sDescripcion']."</td>".
                             "</tr>";
      
                    
            }                                                                                                                                                                       
        }else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";}
        $conexion->rollback();
        $conexion->close();
        $response = array(
                "mensaje"=>"$mensaje",
                "error"=>"$error",
                "policies_information"=>"$htmlTabla",
                "pd_data"=>"$pd_information",
                );   
        echo json_encode($response);
  }
  function save_endorsement(){
      
      //Conexion:
      include("cn_usuarios.php");  
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $success   = true;
      $error     = "0";  
      $mensaje   = "";
      $valoresE  = array();
      $camposE   = array();
      $valoresU  = array();
      $camposU   = array();
      $edit_mode = trim($_POST['edit_mode']); 
      
      //VARIABLES POST:
      $iConsecutivo         = trim($_POST['iConsecutivo']); 
      $eAccion              = trim($_POST['eAccion']);
      $iPDAmount            = $_POST['iPDAmount'] > 0 ? trim($_POST['iPDAmount']) : '0';
      $sComentarios         = $_POST['sComentarios'] != "" ? "'".utf8_decode(trim($_POST['sComentarios']))."'" : "''";
      $iConsecutivoUnidad   = trim($_POST['iConsecutivoUnidad']);
      $iConsecutivoCompania = trim($_POST['iConsecutivoCompania']);
      $sTipo                = trim($_POST['sTipo']);
      $iYear                = trim($_POST['iYear']);
      $iModelo              = $_POST['iModelo'] != "" ? "'".trim($_POST['iModelo'])."'" : 'NULL';
      $sVIN                 = trim($_POST['sUnitTrailer']);
      $iConsecutivoRadio    = $_POST['iConsecutivoRadio'] != "" ? "'".trim($_POST['iConsecutivoRadio'])."'" : 'NULL';
      $iConsecutivoCompania = trim($_POST['iConsecutivoCompania']);
      $sIP                  = $_SERVER['REMOTE_ADDR'];
      $sUsuario             = $_SESSION['usuario_actual'];
      $dFecha               = date("Y-m-d H:i:s");
      
      //REVISAMOS DATOS DE LA UNIDAD:
      if($iConsecutivoUnidad == ""){
         //Verificamos si la unidad ya existe: 
         $query  = "SELECT iConsecutivo FROM ct_unidades WHERE sVIN='$sVIN' AND iConsecutivoCompania = '$iConsecutivoCompania'";
         $result = $conexion->query($query);
         $items  = $result->fetch_assoc();
         if($items['iConsecutivo']!= ""){$iConsecutivoUnidad = trim($items['iConsecutivo']);}
      }
      
      if($iConsecutivoUnidad!= ""){
          
         $query   = "UPDATE ct_unidades SET sTipo='$sTipo',iYear='$iYear',iModelo=$iModelo,sVIN='$sVIN',iConsecutivoRadio=$iConsecutivoRadio, ".
                    "sIP='$sIP',sUsuarioActualizacion='$sUsuario',dFechaActualizacion='$dFecha' ".
                    "WHERE iConsecutivo ='$iConsecutivoUnidad' AND iConsecutivoCompania = '$iConsecutivoCompania'"; 
         $success = $conexion->query($query);
                  
      }else{
         $query   = "INSERT INFO ct_unidades (iConsecutivoCompania,sTipo,iYear,iModelo,sVIN,iConsecutivoRadio,sIP,sUsuarioIngreso,dFechaIngreso) ".
                    "VALUES('$iConsecutivoCompania','$sTipo','$iYear',$iModelo,'$sVIN',$iConsecutivoRadio,'$sIP','$sUsuario','$dFecha')";
         $success = $conexion->query($query);
         if($success){$iConsecutivoUnidad = $conexion->insert_id;}
      }

      if(!($success)){$error = '1';$mensaje = "Error to save the unit data, please try again later.";}
      else{
          //GUARDAMOS EL ENDOSO
          if($edit_mode == 'true'){
              //UPDATE
              $query   = "UPDATE cb_endoso SET iConsecutivoUnidad='$iConsecutivoUnidad',sComentarios=$sComentarios,iPDAmount='$iPDAmount', ".
                         "sIP='$sIP',sUsuarioActualizacion='$sUsuario',dFechaActualizacion='$dFecha' ".
                         "WHERE iConsecutivo='$iConsecutivo' AND iConsecutivoCompania='$iConsecutivoCompania'";
              $mensaje = "The data was updated successfully.";
          
          }else if($edit_mode == 'false'){
              //INSERT
              $query   = "INSERT cb_endoso (iConsecutivoCompania,iConsecutivoTipoEndoso,eStatus,iPDAmount,iConsecutivoUnidad,eAccion,dFechaAplicacion,sComentarios,sIP,sUsuarioIngreso,dFechaIngreso) ".
                         "VALUES('$iConsecutivoCompania','1','S','$iPDAmount','$iConsecutivoUnidad','$eAccion','$dFecha',$sComentarios,'$sIP','$sUsuario','$dFecha') ";
              $mensaje = "The data was saved successfully.";
          }
          
          $success = $conexion->query($query);
          if(!($success)){$error = '1';$mensaje = "Error to save the endorsement data, please try again later.";}
          else{
              if($edit_mode == 'false'){$iConsecutivo = $conexion->insert_id;} 
              //ELIMINAMOS POLIZAS GUARDADAS ANTERIORMENTE:
              $query   = "DELETE FROM cb_endoso_estatus WHERE iConsecutivoEndoso='$iConsecutivo'";
              $success = $conexion->query($query); 
              if(!($success)){$error = '1';$mensaje = "Error to update the endorsement status data, please try again later.";}
              else{
                  foreach($_POST as $campo => $valor){
                      if(!(strpos($campo,"chk_policies_") === false) && $valor == 1){
                          $poliza  = str_replace("chk_policies_","",$campo);
                          $query   = "INSERT INTO cb_endoso_estatus (iConsecutivoEndoso,iConsecutivoPoliza,eStatus,sIP,sUsuarioIngreso,dFechaIngreso) ".
                                     "VALUES('$iConsecutivo','$poliza','S','$sIP','$sUsuario','$dFecha')";
                          $success = $conexion->query($query);
                          if(!($success)){$error = '1';$mensaje = "Error to save the endorsement status data, please try again later.";} 
                      }
                  }   
              }
          } 
      }
      
      $success && $error == '0' ? $conexion->commit() : $conexion->rollback();
      $conexion->close();
      $response = array("error"=>"$error","msj"=>"$mensaje");
      echo json_encode($response);
  }
  
  #FUNCIONES EMAILS Y STATUS:
  function get_endorsement_estatus(){
      
      include("cn_usuarios.php");     
      $error       = "0";
      $clave       = trim($_POST['iConsecutivo']);
      $domroot     = trim($_POST['domroot']);
      
      #CONSULTAR DATOS DEL CLAIM Y COMPANIA
      $sql    = "SELECT iConsecutivoEndoso,iConsecutivoPoliza,B.sNumeroPoliza,D.sDescripcion AS sTipoPoliza, sMensajeEmail, ".
                "IF(A.sEmail != '',A.sEmail,C.sEmail) AS sEmail, C.iConsecutivo AS iConsecutivoBroker, C.sName AS sBrokerName, C.bEndosoMensual  ".
                "FROM cb_endoso_estatus   AS A ".
                "LEFT JOIN ct_polizas     AS B ON A.iConsecutivoPoliza  = B.iConsecutivo ".
                "LEFT JOIN ct_tipo_poliza AS D ON B.iTipoPoliza = D.iConsecutivo ".
                "LEFT JOIN ct_brokers     AS C ON B.iConsecutivoBrokers = C.iConsecutivo ".
                "WHERE iConsecutivoEndoso = '$clave' ";
      $result = $conexion->query($sql); 
      $rows   = $result->num_rows; 
      
      if($rows > 0){ 
          while ($data = $result->fetch_assoc()){
              
              //llenar tabla:
              if($data["sNumeroPoliza"] != ""){
                   
                   if($data['iConsecutivoBroker'] != ""){$broker_option = $data['sBrokerName'];$readonly="";}
                   else{
                       $readonly = "readonly=\"readonly\"";
                       $broker_option = "<span style=\"color:#ef2828;\">To send the endorsement, please configure first the policy correctly.</span><br>".
                                        "<a href=\"policies\" target=\"_blank\" style=\"color:#2a95e8;display: inline-block;padding: 1px;text-decoration: underline;\" onclick=\"fn_popups.cerrar_ventana('form_estatus');\">Click here!</a>";
                   }
                   
      
                   if($data['bEndosoMensual'] == '1'){
                       $readonly       = "readonly=\"readonly\"";
                       $opacity        = "style=\"opacity:0.5;\"";
                       $noapply        = "no_apply_endoso";
                       $broker_option .= "<br><span style=\"color:#ef2828;\">This broker only accepts an endorsement by month.</span><br>";
                   }
                   else{$opacity = "";$noapply="";}
                    
                   $tipoPoliza = get_policy_type($data['iTipoPoliza']); 
                   $htmlTabla .= "<tr $opacity>".
                                   "<td style=\"border: 1px solid #dedede;\">".$data['sNumeroPoliza']."</td>".
                                   "<td style=\"border: 1px solid #dedede;\">".$data['sTipoPoliza']."</td>".
                                   "<td style=\"border: 1px solid #dedede;\">".$broker_option."</td>". 
                                   "<td style=\"border: 1px solid #dedede;\"><input class=\"idpolicy_".$data['iConsecutivoPoliza']." $noapply\" id=\"epolicy_".$tipoPoliza."_".$data['sNumeroPoliza']."\" type=\"text\" value=\"".$data['sEmail']."\" text=\"".$data['sEmail']."\" style=\"width: 94%;\" title=\"If you need to write more than one email, please separate them by comma symbol (,).\" $readonly/></td>".
                                   "</tr>";
                                       
              }
                  
              $llaves  = array_keys($data);
              $datos   = $data;
                  
              foreach($datos as $i => $b){ 
                    if($i == 'sMensajeEmail'){
                        $fields .= "\$('#$domroot :input[id=".$i."]').val('".utf8_decode(utf8_encode($datos[$i]))."');\n";
                    }
                    else if($i == 'sEmail'){
                       $fields .= "\$('#$domroot :input[class=idpolicy_".$data['iConsecutivoPoliza']."]').val('".$datos[$i]."');\n"; 
                    }
                    else if($i != "iConsecutivoPoliza" && $i != "iConsecutivoEmail" && $i != "iConsecutivoCompania"){
                       $fields .= "\$('#$domroot :input[id=".$i."]').val('".htmlentities($datos[$i])."');\n"; 
                    }
              }
          }

      }
      else{$error = '1';} 

      $response = array("fields"=>"$fields","error"=>"$error","policies_information"=>"$htmlTabla");   
      echo json_encode($response);
  }
  function save_email(){
      
      //Conexion:
      include("cn_usuarios.php");  
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $success  = true;
      $error    = "0";  
      $mensaje  = "The data was saved successfully.";
      $valores  = array();
      $campos   = array();
      
      //Variables 
      $_POST['sMensaje'] != "" ? $sMensaje = utf8_decode(trim($_POST['sMensaje'])) : $sMensaje = "";
      $iConsecutivoEndoso = trim($_POST['iConsecutivoEndoso']);
      $sIP                = $_SERVER['REMOTE_ADDR'];
      $sUsuario           = $_SESSION['usuario_actual'];
      $dFecha             = date("Y-m-d H:i:s");
      
      #GUARDAR POR POLIZAS
      $polizas = trim($_POST['insurances_policy']);
      $polizas = explode(";",$polizas);
      $count   = count($polizas);
      
      for($x=0;$x<$count;$x++){
          $data    = explode("|",$polizas[$x]);
          $query   = "UPDATE cb_endoso_estatus SET sEmail='".$data[1]."', sMensajeEmail='".$sMensaje."',sIP='$sIP',sUsuarioActualizacion='$sUsuario',dFechaActualizacion='$dFecha' ".
                     "WHERE iConsecutivoEndoso='$iConsecutivoEndoso' AND iConsecutivoPoliza='".$data[0]."'";
          $success = $conexion->query($query) or die($conexion->error);
          if(!($success)){$error = '1';$mensaje = "Error to save data, please try again later.";}
      }
      
      $success && $error == '0' ? $conexion->commit() : $conexion->rollback();
      $conexion->close();
      $response = array("error"=>"$error","msj"=>"$mensaje");
      echo json_encode($response);
  }
  function preview_email(){
      
      $error              = '0';
      $msj                = "";
      $fields             = "";
      $iConsecutivo       = trim($_POST['iConsecutivoEndoso']);
      $insurances_policy  = "";
      $Emails             = "";
      
      //Armar Emails:
      $Emails    = get_email_data($iConsecutivo); 
      $count     = count($Emails);
      $htmlTabla = "";
      
      for($x=0;$x < $count;$x++){
          if($Emails[$x]['html']!= ""){
              $htmlTabla  .= "<table style=\"font-size:12px;border:1px solid #dedede;border-radius:3px;padding:10px;width:95%; margin:5px auto;font-family: Arial, Helvetica, sans-serif;\">";
              $htmlTabla  .= "<tr><td><h3 style=\"color:#6191df;\">E-mail ".($x+1)."</h3></td></tr>"; 
              $htmlTabla  .= "<tr><td><b style=\"display: inline-block;width: 80px;\">Subject: </b>".$Emails[$x]['subject']."</td></tr>";
              $htmlTabla  .= "<tr><td><b style=\"display: inline-block;width: 80px;\">To: </b>(".$Emails[$x]['broker'].") - ".$Emails[$x]['emails']."</td></tr>"; 
              $htmlTabla .= "<tr><td><hr></td></tr>"; 
              $htmlTabla .= "<tr><td>".$Emails[$x]['html']."</td></tr>"; 
              
              //Atachments:
              $files = $Emails[$x]['files'];
              if($files != ""){
                  $htmlTabla .= "<tr><td>".
                                "<table style=\"font-size:12px;border-top:1px solid #dedede;padding:10px;width:95%; margin:5px auto;font-family: Arial, Helvetica, sans-serif;\">";
                  $htmlTabla .= "<tr><td colspan=\"100%;\"><h3>Attachments</h3><td></tr>";
                  $htmlTabla .= "<tr>".
                                "<td>".$files['name']."</td>".
                                "<td>".$files['type']."</td>".
                                "<td>".$files['size']."</td>". 
                                "<td>".
                                   "<div class=\"btn-icon edit btn-left\" title=\"Open file in a new window\" onclick=\"window.open('open_pdf.php?idfile=".$files['id']."&type=endoso');\"><i class=\"fa fa-external-link\"></i><span></span></div>". 
                                "</td></tr>";
                  $htmlTabla .= "</table></td></tr>";
              }
              $htmlTabla  .= "</table>";
          } 
      }
      
      $response = array("msj"=>"$msj","error"=>"$error","tabla" => "$htmlTabla");   
      echo json_encode($response);
          
  }
  function send_email(){
      
      #Building Email Body:                                   
      require_once("./lib/phpmailer_master/class.phpmailer.php");
      require_once("./lib/phpmailer_master/class.smtp.php");
                    
      $error              = '0';
      $msj                = "";
      $fields             = "";
      $iConsecutivo       = trim($_POST['iConsecutivoEndoso']);
      $insurances_policy  = "";
      $Emails             = "";
      $success            = true;
      //Armar Emails:
      $Emails    = get_email_data($iConsecutivo);  
      $count     = count($Emails);
      $htmlTabla = "";
      
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);
      
      #ACTUALIZAMOS ENDOSO A SB..
      if($count > 0){
          #UPDATE ENDORSEMENT DETAILS:
          $query = "UPDATE cb_endoso SET eStatus = 'SB', dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."', sUsuarioActualizacion='".$_SESSION['usuario_actual']."' ".
                   "WHERE iConsecutivo = '$iConsecutivo'"; 
          $conexion->query($query);
          if($conexion->affected_rows < 1){$success = false;$mensaje="Error to update the endorsement status, please check with de system admin.";} 
      }
      
      for($x=0;$x < $count;$x++){
          if($Emails[$x]['html']!= ""){
                  
            #UPDATE ENDORSEMENT DETAILS:
            $query = "UPDATE cb_endoso_estatus SET eStatus = 'SB', dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."', sUsuarioActualizacion='".$_SESSION['usuario_actual']."' ".
                     "WHERE iConsecutivoEndoso = '$iConsecutivo' AND iConsecutivoPoliza = '".$Emails[$x]['idPoliza']."'"; 
            $conexion->query($query);
            if($conexion->affected_rows < 1){$success = false;$mensaje="Error to update the endorsement status, please check with de system admin.";}    
              
            #HTML:
            $htmlEmail  = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"\"http://www.w3.org/TR/html4/strict.dtd\"><html>".
                            "<head><meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\">".
                            "<title>Endorsement from Solo-Trucking Insurance</title></head>"; 
            $htmlEmail .= "<body>".$Emails[$x]['html']."</body>";   
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
            $mail->AddReplyTo('customerservice@solo-trucking.com','Customer service Solo-Trucking');
            $mail->Subject    = $Emails[$x]['subject'];
            $mail->AltBody    = "To view the message, please use an HTML compatible email viewer!";  // optional, comment out and test
            $mail->MsgHTML($htmlEmail);
            $mail->IsHTML(true); 
             
            //Receptores:
            $direcciones         = explode(",",trim($Emails[$x]['emails']));
            $nombre_destinatario = trim($Emails[$x]['broker']);
            foreach($direcciones as $direccion){
                $mail->AddAddress(trim($direccion),$nombre_destinatario);
            }
              
            //Atachments:
            $files        = $Emails[$x]['files'];
            $delete_files = "";
            if($files != ""){
               include("./lib/fpdf153/fpdf.php");//libreria fpdf
               $file_tmp = fopen('tmp/'.$files["name"],"w") or die("Error when creating the file. Please check."); 
               fwrite($file_tmp,$files["content"]); 
               fclose($file_tmp);     
               $archivo = "tmp/".$files["name"];  
               $mail->AddAttachment($archivo);
               $delete_files .= "unlink(\"tmp/\".".$files["name"].");"; 
            }
            
            $mail_error = false;
            if(!$mail->Send()){$mail_error = true; $mail->ClearAddresses();}
            if(!($mail_error)){$msj = "The mail has been sent to the brokers";}
            else{$msj = "Error: The e-mail cannot be sent.";$error = "1";}
            
            $mail->ClearAttachments();
            eval($delete_files);

          } 
      }
      
      
      $success && $error == '0' ? $conexion->commit() : $conexion->rollback();
      $conexion->close();
      
      $response = array("msj"=>"$msj","error"=>"$error","tabla" => "$htmlTabla");   
      echo json_encode($response);    
  }
  function get_email_data($iConsecutivo){
          
      include("cn_usuarios.php");
      $Emails  = array();
      $error   = "";
      $mensaje = "";
      
      #DATOS DEL ENDOSO:
      $query  = "SELECT A.*, B.sNombreCompania FROM   cb_endoso AS A ".
                "INNER JOIN ct_companias AS B ON A.iConsecutivoCompania = B. iConsecutivo ".
                "WHERE A.iConsecutivoTipoEndoso = '1' AND A.iConsecutivo = '$iConsecutivo' ";
      $result = $conexion->query($query) or die($conexion->error);
      $rows   = $result->num_rows; 
      
      if($rows == 0){$error = '1';$mensaje = "Error to query the endorsement data, please try again later.";}
      else{
          
          $Endoso = $result->fetch_assoc();
          #CONSULTAR DESCRIPCION DEL ENDOSO:
          $query  = "SELECT A.iConsecutivo, A.sVIN, A.iYear, A.sPeso,A.sTipo, A.iValue, A.iTotalPremiumPD, B.sDescripcion AS sModelo, B.sAlias AS sAliasModelo, C.sDescripcion AS sRadio ".
                    "FROM   ct_unidades AS A ".
                    "LEFT JOIN ct_unidad_modelo AS B ON A.iModelo = B.iConsecutivo ".
                    "LEFT JOIN ct_unidad_radio  AS C ON A.iConsecutivoRadio = C.iConsecutivo ".
                    "WHERE A.iConsecutivo = '".$Endoso['iConsecutivoUnidad']."' AND A.iConsecutivoCompania = '".$Endoso['iConsecutivoCompania']."' ";
          $result = $conexion->query($query) or die($conexion->error);
          $rows   = $result->num_rows;
          if($rows == 0){$error = '1';$mensaje = "Error to query the endorsement description data, please try again later.";}
          else{
              
             //Variables: 
             $Detalle    = $result->fetch_assoc(); 
             $UnidadTipo = strtolower($Detalle['sTipo']);
             $ComNombre  = $Endoso['sNombreCompania'];
             $PDAmount   = number_format($Endoso["iPDAmount"],2,'.','');
             $VIN        = $Detalle['sVIN'];
             $Radius     = $Detalle['sRadio'];
             $Peso       = $Detalle['sPeso'];
             $Year       = $Detalle['iYear'];
             
             if($Detalle['sAliasModelo'] != ''){$Make = $Detalle['sAliasModelo'];}
             else if($Detalle['sModelo'] != ''){$Make = $Detalle['sModelo']; }
             
             #CONSULTAR ARCHIVOS:
             $file = array();
             $Endoso["eAccion"] == 'A' ? $filtroArchivo = " AND eArchivo ='TITLE'" : $filtroArchivo = " AND (eArchivo='DA' OR eArchivo='BS' OR eArchivo='NOR' OR eArchivo='PTL')";
             //Buscamos archivos primero por endoso...
             $query  = "SELECT iConsecutivo, sNombreArchivo, eArchivo, hContenidoDocumentoDigitalizado, sTipoArchivo, iTamanioArchivo ".
                       "FROM cb_endoso_files WHERE iConsecutivoEndoso = '$iConsecutivo' $filtroArchivo"; 
             $result = $conexion->query($query) or die($conexion->error);
             $rows   = $result->num_rows; 
             if($rows > 0){
                    while ($files = $result->fetch_assoc()){
                       #Here will constructed the temporary files: 
                       if($files['sNombreArchivo'] != ""){ 
                         $file['id']     = $files['iConsecutivo'];
                         $file['name']   = $files['sNombreArchivo'];
                         $file['tipo']   = $files['eArchivo'];
                         $file['content']= $files['hContenidoDocumentoDigitalizado'];
                         $file['size']   = $files['iTamanioArchivo'];
                         $file['type']   = $files['sTipoArchivo'];
                       }
                    }
             }else{
                 //Buscamos archivos por unidad
                 $query  = "SELECT iConsecutivo, sNombreArchivo, eArchivo, hContenidoDocumentoDigitalizado, sTipoArchivo, iTamanioArchivo ".
                           "FROM cb_unidad_files WHERE  iConsecutivoUnidad = '".$Endoso['iConsecutivoUnidad']."' $filtroArchivo "; 
                 $result = $conexion->query($query) or die($conexion->error);
                 $rows   = $result->num_rows;
                 if($rows > 0){
                    while ($files = $result->fetch_assoc()){
                       #Here will constructed the temporary files: 
                       if($files['sNombreArchivo'] != ""){ 
                         $file['id']     = $files['iConsecutivo'];
                         $file['nombre'] = $files['sNombreArchivo'];
                         $file['tipo']   = $files['eArchivo'];
                       }
                    }
                 }
             }
             if(count($file)==0){$file="";} 
             /**************/ 
             
             #CONSULTAMOS POLIZAS DEL ENDOSO E INFO DE LOS EMAILS:
             $query  = "SELECT iConsecutivoEndoso,iConsecutivoPoliza,B.sNumeroPoliza,B.iTipoPoliza,D.sDescripcion AS sTipoPoliza, sMensajeEmail, A.sEmail, C.iConsecutivo AS iConsecutivoBroker, C.sName AS sBrokerName, C.bEndosoMensual ".
                       "FROM cb_endoso_estatus   AS A ".
                       "LEFT JOIN ct_polizas     AS B ON A.iConsecutivoPoliza  = B.iConsecutivo ".
                       "LEFT JOIN ct_tipo_poliza AS D ON B.iTipoPoliza = D.iConsecutivo ".
                       "LEFT JOIN ct_brokers     AS C ON B.iConsecutivoBrokers = C.iConsecutivo ".
                       "WHERE A.iConsecutivoEndoso = '$iConsecutivo' AND C.bEndosoMensual='0'"; 
             $result = $conexion->query($query) or die($conexion->error);
             $rows   = $result->num_rows;
             
             if($rows == 0){$error = '1';$mensaje = "Error to query the endorsement email data, please try again later.";}
             else{
                while($data = $result->fetch_assoc()){ 
                    //Variables por Email:
                    $email      = array();
                    $sMensaje   = $data['sMensajeEmail'];
                    $sNumPoliza = $data['sNumeroPoliza'];
                    $sEmails    = $data['sEmail'];
                    $sBrokerName= $data['sBrokerName'];
                    $sTipoPoliza= $data['sTipoPoliza'];
                    $idPoliza   = $data['iConsecutivoPoliza'];
                    $tipoPoliza = get_policy_type($data['iTipoPoliza']); 
                    
                    #ENDOSO TIPO ADD:
                    if($Endoso["eAccion"] == 'A'){
                        
                        $action  = "Please add to my policy the following $UnidadTipo.";
                        $subject = "Endorsement application - please add the following $UnidadTipo from policy number: $ComNombre, $sNumPoliza - $sTipoPoliza";
                        
                        $bodyData = "<p style=\"color:#000;margin:5px auto; text-align:left;\">".
                                    "$Year $Make $VIN $Radius $Peso ";

                        #PDAmount
                        if($data['iTipoPoliza'] == '1' && $Endoso["iPDAmount"] != ''){$bodyData.=$PDAmount;}
                        
                        $bodyData .= "</p><br><br>";
                        
                        
                      
                    }else
                    if($Endoso["eAccion"] == 'D'){
                       $action   = "Please delete of my policy the following $UnidadTipo";                                                                   
                       $subject  = "Endorsement application - please delete the following $UnidadTipo from policy number: $ComNombre, $sNumPoliza - $sTipoPoliza";
                       $bodyData = "<p style=\"color:#000;margin:5px auto; text-align:left;\"> $Year $Make $VIN </p><br><br>";         
                    }    
                    
                    $htmlEmail = "<table style=\"font-size:12px;border:1px solid #6191df;border-radius:3px;padding:10px;width:95%; margin:5px auto;font-family: Arial, Helvetica, sans-serif;\">".
                                 "<tr><td><h2 style=\"color:#313131;text-transform: uppercase; text-align:center;\">Endorsement application from Solo-Trucking Insurance</h2></td></tr>".
                                 "<tr><td><p style=\"color:#000;margin:5px auto; text-align:left;\">$action</p><br><br></td></tr>".
                                 "<tr><td>$bodyData</td></tr>".
                                 "<tr><td><p style=\"color:#010101;margin:5px auto 10px; text-align:left;font-size:11px;\">Please reply this email to the account:<a href=\"mailto:customerservice@solo-trucking.com\"> customerservice@solo-trucking.com</a></p></td></tr>". 
                                 "<tr><td><p style=\"color:#858585;margin:5px auto; text-align:left;font-size:10px;\">e-mail sent from Solo-trucking Insurance System.</p></td></tr>".
                                 "</table>";
                    
                    #ADD DATA TO ARRAY:
                    $email["subject"] = $subject;
                    $email['html']    = $htmlEmail;
                    $email['broker']  = $sBrokerName;
                    $email['files']   = $file;
                    $email['idPoliza']= $idPoliza;
                    
                    #EMAILS TO SEND (VALIDATE)
                    $emailRegex   = "/^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/"; 
                    $emailstosend = explode(",",$sEmails);
                    $countemails  = count($emailstosend);
                    $emailerror   = "";
                      
                    for($z = 0; $z < $countemails; $z++){ 
                        if($emailstosend[$z] != ""){
                              $validaemail = preg_match($emailRegex,trim($emailstosend[$z]));
                              if(!($validaemail)){$emailerror .= $emailstosend[$z]."<br>";}
                        }
                    }
                    if($emailerror == ""){$email["emails"] = $sEmails;$email["error"] = "0";}
                    else{$email["emails"] = $emailerror; $email["error"] = "1";} 
                    
                    $Emails[] = $email;
                    if($email["error"] == "1"){$error .= $email["emails"];}  
                    
             } 
          }
      }
      
      $error != "" ? $Emails['error'] = $mensaje : $Emails['error'] = "0";
      $conexion->close(); 
      return $Emails;
          
      }
  }
  
  #FUNCIONES UNITS:
  function get_units_autocomplete(){
      
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);
      $company = trim($_POST['iConsecutivoCompania']);
      $error   = "0";
      $mensaje = "";
      $sql     = "SELECT iConsecutivo, sVIN, sTipo, siConsecutivosPolizas, iYear, iConsecutivoRadio, iModelo  ". 
                 "FROM ct_unidades WHERE iConsecutivoCompania = '$company' ".
                 "ORDER BY iConsecutivo ASC";
      $result  = $conexion->query($sql);
      $rows    = $result->num_rows;  
      if($rows > 0){
             
        while ($items = $result->fetch_assoc()) {
           $cadena     = '"'.$items["sVIN"].' | '.$items["sTipo"].'|'.utf8_encode($items["iYear"]).'|'.$items["iConsecutivoRadio"].'|'.$items["iModelo"].'|'.$items["iConsecutivo"].'"';
           $respuesta == '' ? $respuesta .= $cadena : $respuesta .= ','.$cadena;    
        }                                                                                                                                                                        
      }else {$respuesta .="";}
      $conexion->rollback();
      $conexion->close();
       
      $respuesta = "[".$respuesta."]";
      echo $respuesta;
      
  }
  
  #FUNCIONES FILES:
  function get_files(){
        
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa  = true;
      $iConsecutivo         = trim($_POST['iConsecutivo']);
      $registros_por_pagina = $_POST["registros_por_pagina"];
      $pagina_actual        = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
      $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
      $query_union          = "";
      
      #CONSULTAR DATOS DEL ENDOSO                                                                                                               
      $sql    = "SELECT A.iConsecutivo, A.iConsecutivoCompania, A.iConsecutivoTipoEndoso, sDescripcion, iReeferYear,iTrailerExchange, iPDAmount, ". 
                "A.sComentarios, iPDApply, iConsecutivoUnidad,sVINUnidad, eAccion ".  
                "FROM cb_endoso A ".
                "LEFT JOIN ct_tipo_endoso B ON A.iConsecutivoTipoEndoso = B.iConsecutivo ".
                "WHERE A.iConsecutivo = '$iConsecutivo' ";
      $result = $conexion->query($sql);
      $rows   = $result->num_rows;
      
      if($rows > 0){ 
        
        $endoso = $result->fetch_assoc();
        //Filtros de informacion //
        $filtroQuery = " WHERE iConsecutivoEndoso = '".$iConsecutivo."' ";
        
        // ordenamiento//
        $ordenQuery = " ORDER BY ".$_POST["ordenInformacion"]." ".$_POST["sortInformacion"];
        
        #VERIFICAMOS, SI ES UN ADD, VERIFICAMOS TABLA DE ARCHIVOS X UNIDAD:
        if($endoso['eAccion'] == 'A' && $endoso['iConsecutivoUnidad'] != ""){
          $query_union = " UNION ".
                         "(SELECT iConsecutivo, sTipoArchivo, sNombreArchivo, iTamanioArchivo FROM cb_unidad_files WHERE iConsecutivoUnidad = '".$endoso['iConsecutivoUnidad']."' $ordenQuery)";    
        }

        //contando registros // 
        /*$query_rows = "(SELECT COUNT(iConsecutivo) AS total FROM cb_endoso_files ".$filtroQuery.")";
                      
        $Result    = $conexion->query($query_rows);
        $items     = $Result->fetch_assoc();
        $registros = $items["total"];
        if($registros == "0"){$pagina_actual = 0;}
        $paginas_total = ceil($registros / $registros_por_pagina);
        
        if($registros == "0"){
            $limite_superior = 0;
            $limite_inferior = 0;
            $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";
        }else{*/
            
          $pagina_actual   == "0" ? $pagina_actual = 1 : false;
          $limite_superior = $registros_por_pagina;
          $limite_inferior = ($pagina_actual*$registros_por_pagina)-$registros_por_pagina;
          
          $sql    = "(SELECT iConsecutivo, sTipoArchivo,sNombreArchivo,iTamanioArchivo FROM cb_endoso_files ".$filtroQuery.$ordenQuery.")".$query_union;
          $result = $conexion->query($sql);
          $rows   = $result->num_rows; 
             
            if ($rows > 0) {    
                while ($items = $result->fetch_assoc()) { 
                   if($items["iConsecutivo"] != ""){

                         $htmlTabla .= "<tr>".
                                       "<td id=\"idFile_".$items['iConsecutivo']."\">".$items['sNombreArchivo']."</td>".
                                       "<td>".$items['sTipoArchivo']."</td>".
                                       "<td>".$items['iTamanioArchivo']."</td>". 
                                       "<td>".
                                            "<div class=\"btn-icon edit btn-left\" title=\"Open file in a new window\" onclick=\"window.open('open_pdf.php?idfile=".$items['iConsecutivo']."&type=endoso');\"><i class=\"fa fa-external-link\"></i><span></span></div>". 
                                            "<div class=\"btn_delete_file btn-icon trash btn-left\" title=\"Delete file\"><i class=\"fa fa-trash\"></i><span></span></div>".
                                       "</td></tr>";  
                                           
                     }else{                                                                                                                                                                                                        
                        
                         $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>"   ;
                     }    
                }
                $conexion->rollback();
                $conexion->close();                                                                                                                                                                       
            }
            else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";}
          //}
          
      }
      else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">Error to query endorsement data.</td></tr>"; }
            
        
      $response = array("total"=>"$paginas_total","pagina"=>"$pagina_actual","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
      echo json_encode($response); 
  }
  
  #FUNCIONES BROKERS:
  function send_email_brokers(){
      include("cn_usuarios.php");
      $error = '0';
      //variables:
      $id= $_POST['clave'];
      $idPoliza= $_POST['idPoliza']; 
      $htmlEmail = "";
      $conexion->autocommit(FALSE);
      $transaccion_exitosa = true;
      #1- First Step: Consult the general information from the Endorsement with the id.                    
      $endorsement_query = "SELECT A.iConsecutivo, A.iConsecutivoCompania, sNombreCompania,iConsecutivoTipoEndoso,iConsecutivoUnidad, eAccion, sNumeroPoliza, T.sDescripcion AS Tipo, sEmail AS Broker, iPDAmount, iPDApply, P.iTipoPoliza
                            FROM cb_endoso A
                            LEFT JOIN ct_companias B ON A.iConsecutivoCompania = B.iConsecutivo
                            LEFT JOIN cb_endoso_estatus D ON A.iConseCutivo = D.iConsecutivoEndoso
                            LEFT JOIN ct_polizas P ON D.iConsecutivoPoliza = P.iConsecutivo
                            LEFT JOIN ct_tipo_poliza T ON P.iTipoPoliza = T.iConsecutivo
                            LEFT JOIN ct_brokers BR ON P.iConsecutivoBrokers = BR.iConsecutivo
                            WHERE A.iConsecutivo = '$id' AND D.iConsecutivoPoliza = '$idPoliza'";                      
      $result = $conexion->query($endorsement_query);
      $rows = $result->num_rows; 
      $rows > 0 ? $endorsement = $result->fetch_assoc() : $endorsement = "";
      if($endorsement['iConsecutivo'] != "" && $endorsement['iConsecutivoTipoEndoso'] == '1' && $endorsement['iConsecutivoUnidad'] != ''){  

          
             #Unit: Consult the unit information:
             $unit_query = "SELECT A.iConsecutivo, iConsecutivoCompania, iYear, iModelo, sVIN, sModelo, B.sAlias, B.sDescripcion AS Make, C.sDescripcion AS Radius, sTipo ".  
                           "FROM ct_unidades A ".
                           "LEFT JOIN ct_unidad_modelo B ON A.iModelo = B.iConsecutivo ".
                           "LEFT JOIN ct_unidad_radio C ON A.iConsecutivoRadio = C.iConsecutivo ".
                           "WHERE A.iConsecutivo = '".$endorsement['iConsecutivoUnidad']."'";
             $result_d = $conexion->query($unit_query);
             $rows_d = $result_d->num_rows; 
             $rows_d > 0 ? $unit = $result_d->fetch_assoc() : $unit = "";
             
             $subject_policy = $endorsement['sNumeroPoliza'].'-'.$endorsement['Tipo'];
                 
                      
             #MAKE
             if($unit['sAlias'] != ''){$make = $unit['sAlias'];}else if($unit['Make'] != ''){$make = $unit['Make']; }
                              
             #action to define the subject an body email: 
             if($endorsement["eAccion"] == 'A'){
                    $action = 'Please add to my policy the following '.strtolower($unit['sTipo']).'.';
                    $subject = "Endorsement application - please add the following ".strtolower($unit['sTipo'])." from policy number: ".$endorsement['sNombreCompania'].", ".$subject_policy;
                    
                    #PDAmount
                    $endorsement['iTipoPoliza'] == '1' && $endorsement["iPDApply"] == '1' && $endorsement["iPDAmount"] != '' ? $PDAmount = number_format($endorsement["iPDAmount"]) : $PDAmount = '';
                    
                    #RADIUS:
                    $radius = explode('(',$unit['Radius']);
                    
                    $bodyData = "<p style=\"color:#000;margin:5px auto; text-align:left;\">".$unit['iYear']."&nbsp;".$make."&nbsp;".$unit['sVIN']."&nbsp;".$radius[0]."&nbsp;11-22&nbsp;TONS&nbsp;".$PDAmount."</p><br><br>";
                  
             }else if($endorsement["eAccion"] == 'D'){
                   $action = 'Please delete of my policy the following '.strtolower($unit['sTipo']).'.';                                                                   
                   $subject = "Endorsement application - please delete the following ".strtolower($unit['sTipo'])." from policy number: ".$endorsement['sNombreCompania'].", ".$subject_policy;
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
                                  "<tr><td>".$bodyData."</td></tr>".
                                  "<tr>".
                                  "<tr><td><p style=\"color:#010101;margin:5px auto 10px; text-align:left;font-size:11px;\">Please reply this email to the account:<a href=\"mailto:customerservice@solo-trucking.com\"> customerservice@solo-trucking.com</a></p></td></tr>". 
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
                     $mail->AddReplyTo('customerservice@solo-trucking.com','Customer service Solo-Trucking');
                     $mail->Subject    = $subject;
                     $mail->AltBody    = "To view the message, please use an HTML compatible email viewer!";  // optional, comment out and test
                     $mail->MsgHTML($htmlEmail);
                     $mail->IsHTML(true); 
                     
                     //Receptores:
                     $direcciones         = explode(",",trim($endorsement['Broker']));
                     $nombre_destinatario = trim($endorsement['NameBroker']);
                     foreach($direcciones as $direccion){
                        $mail->AddAddress(trim($direccion),$nombre_destinatario);
                     }
                     
                     //Revisar si se necesitan enviar archivos adjuntos:
                     include("./lib/fpdf153/fpdf.php"); // libreria fpdf 
                     
                     //Consult the driver files:
                     $driver_files_query = "SELECT iConsecutivo, sNombreArchivo, eArchivo, hContenidoDocumentoDigitalizado, sTipoArchivo, iTamanioArchivo ". 
                                           "FROM cb_unidad_files ". 
                                           "WHERE  iConsecutivoUnidad = '".$endorsement['iConsecutivoUnidad']."'";   
                     $result_files = $conexion->query($driver_files_query);
                     $rows_files = $result_files->num_rows; 
                     if($rows_files > 0){
                            while ($files = $result_files->fetch_assoc()){
                               #Here will constructed the temporary files: 
                               if($files['sNombreArchivo'] != ""){
                               
                                 if($endorsement["eAccion"] == 'A' && $files['eArchivo'] == 'TITLE'){ #<--- Si es un ADD verificar que el arhcivo que enviemos sea TITLE
                                    $file_tmp = fopen('tmp/'.$files["sNombreArchivo"],"w") or die("Error when creating the file. Please check."); 
                                    fwrite($file_tmp,$files["hContenidoDocumentoDigitalizado"]); 
                                    fclose($file_tmp);     
                                    $archivo = "tmp/".$files["sNombreArchivo"];  
                                    $mail->AddAttachment($archivo);
                                    $delete_files .= "unlink(\"tmp/\".".$files["sNombreArchivo"].");";  
                                 }else if($endorsement["eAccion"] == 'D' && ($files['eArchivo'] == 'DA' || $files['eArchivo'] == 'BS' || $files['eArchivo'] == 'NOR' || $files['eArchivo'] == 'PTL')){
                                    $file_tmp = fopen('tmp/'.$files["sNombreArchivo"],"w") or die("Error when creating the file. Please check."); 
                                    fwrite($file_tmp,$files["hContenidoDocumentoDigitalizado"]); 
                                    fclose($file_tmp);     
                                    $archivo = "tmp/".$files["sNombreArchivo"];  
                                    $mail->AddAttachment($archivo);
                                    $delete_files .= "unlink(\"tmp/\".".$files["sNombreArchivo"].");"; 
                                 }
                               }
                               
                            }
                     } 
          
                    $mail_error = false;
                    if(!$mail->Send()){$mail_error = true; $mail->ClearAddresses();}
                    if(!$mail_error){
                        //$mensaje = "Mail confirmation to the user $usuario was successfully sent.";  
                    }else{
                        $mensaje = "Error: The e-mail cannot be sent.";
                        $error = "1";            
                    }
                    $mail->ClearAttachments();
                    #deleting files attachment:
                    eval($delete_files);
                 
                 #VERIFICAR SI SE ENVIARON LOS CORREOS CORRECTAMENTE:
                 if($error == '0'){
                    #UPDATE ENDORSEMENT DETAILS:
                    $sql_endoso = "UPDATE cb_endoso_estatus SET eStatus = 'SB', dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."', sUsuarioActualizacion='".$_SESSION['usuario_actual']."' ".
                                  "WHERE iConsecutivoEndoso = '$id' AND iConsecutivoPoliza = '$idPoliza'"; 
                    $conexion->query($sql_endoso);
                    if($conexion->query($sql_endoso)){
                            $msj = "The Endorsement was sent successfully, please check your email (customerservice@solo-trucking.com) waiting for their response."; 
                    }else{
                          $transaccion_exitosa = false;
                            $msj = "The data of endorsement was not updated properly, please try again.";  
                    }  
  
                 }
     
            
      }else{
          $error = '1';
          $msj = "The endorsement data was not found, please try again.";
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
            
  } 

  
?>
