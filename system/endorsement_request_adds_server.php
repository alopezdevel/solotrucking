<?php
  session_start();
  // Generic functions lib 
  include("functiones_genericas.php"); 
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId 
  
  function get_endorsements(){
      
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa   = true;
      $registros_por_pagina  = $_POST["registros_por_pagina"];
      $pagina_actual         = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
      $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
        
     //Filtros de informacion //
     $filtroQuery = " WHERE A.eStatus != 'E' AND A.iDeleted='0' ";
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
    $query_rows = "SELECT COUNT(A.iConsecutivo) AS total FROM cb_endoso_adicional AS A ".
                  "LEFT JOIN ct_companias       AS D ON A.iConsecutivoCompania = D.iConsecutivo ".$filtroQuery; 
    $Result     = $conexion->query($query_rows);
    $items      = $Result->fetch_assoc();
    $registros  = $items["total"];
    
    if($registros == "0"){$pagina_actual = 0;}
    $paginas_total = ceil($registros / $registros_por_pagina);
    
    if($registros == "0"){
        $limite_superior = 0;
        $limite_inferior = 0;
        $htmlTabla      .= "<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";
    }
    else{
      $pagina_actual  == "0" ? $pagina_actual = 1 : false;
      $limite_superior = $registros_por_pagina;
      $limite_inferior = ($pagina_actual*$registros_por_pagina)-$registros_por_pagina;
       
      $sql    = "SELECT A.iConsecutivo, D.sNombreCompania,A.eStatus, DATE_FORMAT(A.dFechaAplicacion, '%m/%d/%Y') AS dFechaIngreso, D.iOnRedList ".
                "FROM cb_endoso_adicional AS A ".
                "LEFT JOIN ct_companias   AS D ON A.iConsecutivoCompania   = D.iConsecutivo ".
                $filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
      $result = $conexion->query($sql);
      $rows   = $result->num_rows; 
         
      if($rows > 0){    
            while ($item = $result->fetch_assoc()){ 
               
                 $btn_confirm = "";
                 $estado      = "";
                 $class       = "";
                 $detalle     = "";
                 
                 switch($item["eStatus"]){
                     case 'S': 
                        $estado      = '<i class="fa fa-circle-o icon-estatus " aria-hidden=\"true\"></i><span style="font-size: 10px;">NEW</span>';
                        $titleEstatus= "The data can be edited only by the employees of Solo-Trucking.";
                        $class       = "class = \"blue\"";
                        $btn_confirm = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"View and Edit Endorsement Status\"><i class=\"fa fa-pencil-square-o\"></i></div>".
                                       "<div class=\"btn_edit_estatus btn-icon send-email btn-left\" title=\"Send e-mail to the brokers\"><i class=\"fa fa-envelope\"></i></div>"; 
                     break;
                     case 'A': 
                        $estado      = '<i class="fa fa-check-circle status-success icon-estatus " aria-hidden=\"true\"></i><span style="font-size: 10px;">APPROVED</span>';
                        $titleEstatus= "Your endorsement has been approved successfully.";
                        $class       = "class = \"green\"";
                        $btn_confirm = "<div class=\"btn_change_status btn-icon edit btn-left\" title=\"Change the status of endorsement\"><i class=\"fa fa-pencil-square-o\"></i></div>";
                        $btn_confirm.= "<div class=\"btn-icon send-email btn-left\" title=\"See the e-mail sent\" onclick=\"fn_endorsement.email.preview('".$item['iConsecutivo']."');\"><i class=\"fa fa-external-link\"></i></div>"; 
                     break;
                     case 'D': 
                        $estado      = '<i class="fa fa-times status-error icon-estatus " aria-hidden=\"true\"></i><span style="font-size: 10px;">CANCELED</span>';
                        $titleEstatus= "Your endorsement has been canceled, please see the reasons on the comments.";
                        $class       = "class = \"red\"";
                        $btn_confirm = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"View and Edit Endorsement Status\"><i class=\"fa fa-pencil-square-o\"></i></div>".
                                       "<div class=\"btn_edit_estatus btn-icon send-email btn-left\" title=\"Send e-mail to the brokers\"><i class=\"fa fa-envelope\"></i></div>";
                                    
                     break;
                     case 'SB': 
                        $estado      = '<i class="fa fa-share-square-o status-process icon-estatus " aria-hidden=\"true\"></i><span style="font-size: 10px;">SENT TO BROKERS</span>';
                        $titleEstatus= "Your endorsement has been sent to the brokers.";
                        $class       = "class = \"yellow\"";
                        $btn_confirm = "<div class=\"btn_change_status btn-icon edit btn-left\" title=\"Change the status of endorsement\"><i class=\"fa fa-pencil-square-o\"></i></div>"; 
                        $btn_confirm.= "<div class=\"btn-icon send-email btn-left\" title=\"See the e-mail sent\" onclick=\"fn_endorsement.email.preview('".$item['iConsecutivo']."');\"><i class=\"fa fa-external-link\"></i></div>";
                     break;
                     case 'P': 
                        $estado      = '<i class="fa fa-refresh status-process icon-estatus " aria-hidden=\"true\"></i><span style="font-size: 10px;">IN PROCESS</span>';
                        $titleEstatus= "Your endorsement is being in process by the brokers.";
                        $class       = "class = \"orange\"";
                        $btn_confirm = "<div class=\"btn_change_status btn-icon edit btn-left\" title=\"Change the status of endorsement\"><i class=\"fa fa-pencil-square-o\"></i></div>";
                        $btn_confirm.= "<div class=\"btn-icon send-email btn-left\" title=\"See the e-mail sent\" onclick=\"fn_endorsement.email.preview('".$item['iConsecutivo']."');\"><i class=\"fa fa-external-link\"></i></div>";
                     break;
                 } 
                 
                 #CONSULTAR DETALLE DEL ENDOSO:
                 $query = "SELECT sNombreCompania AS sNombreAdicional, sDireccion, sEstado, sCiudad, sCodigoPostal, eTipoEndoso, ".
                          "(CASE WHEN eAccion = 'ADDSWAP'    THEN 'ADD SWAP'
                                 WHEN eAccion = 'DELETESWAP' THEN 'DELETE SWAP' 
                                 ELSE eAccion ".
                          "END) AS eAccion FROM cb_endoso_adicional_detalle WHERE iConsecutivoEndoso = '".$item['iConsecutivo']."' ORDER BY sNombreAdicional ASC";
                 $r     = $conexion->query($query);
                 $row   = $r->num_rows;
                 if($row > 0){
                     $detalle = '<table style="width:100%;">';
                     
                     while($itemD = $r->fetch_assoc()){
                     
                        $texto  = $itemD['sNombreAdicional'].'<br><span style="font-size:11px!important;">'.$itemD['sDireccion'].', '.$itemD['sCiudad'].', '.$itemD['sEstado'].', '.$itemD['sCodigoPostal'].'</span>';
                        $action = $itemD['eAccion'];
                        $tipo   = $itemD['eTipoEndoso'];

                        $detalle.= '<tr style="background-color:transparent!important;">'.
                                   '<td style="border: 0px;width: 15%;">'.$action.'</td>'.
                                   '<td style="border: 0px;width: 50%;">'.$texto.'</td>'.
                                   '<td style="border: 0px;width: 30%;">'.$tipo.'</td>'.
                                   '</tr>';
                        
                     }
                     
                     $detalle.= '</table>';
                 }
                 
                  //Redlist:
                 $item['iOnRedList'] == '1' ? $redlist_icon = "<i class=\"fa fa-star\" style=\"color:#e8051b;margin-right:4px;\"></i>" : $redlist_icon = ""; 
                 $htmlTabla .=   "<tr $class>".
                                 "<td id=\"iCve_".$item['iConsecutivo']."\">".$redlist_icon.$item['sNombreCompania']."</td>".
                                 "<td>".$detalle."</td>". 
                                 "<td class=\"text-center\">".$item['dFechaIngreso']."</td>".
                                 "<td title='$titleEstatus'>".$estado."</td>".                                                                                                                                                                                                                       
                                 "<td> $btn_confirm</td></tr>";
                  
            }
            $conexion->rollback();
            $conexion->close();                                                                                                                                                                       
      }else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";}
    }
    $response = array("total"=>"$paginas_total","pagina"=>"$pagina_actual","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
    echo json_encode($response); 
  }
  function get_endorsement(){
  
      $error   = '0';
      $msj     = "";
      $fields  = "";
      $clave   = trim($_POST['clave']);
      $domroot = $_POST['domroot'];
      
      #Function Begin
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                 
      $sql    = "SELECT A.iConsecutivo, A.iConsecutivoCompania, A.eStatus, DATE_FORMAT(A.dFechaAplicacion, '%m/%d/%Y') AS dFechaAplicacion, A.sComentarios ".
                "FROM cb_endoso_adicional AS A ".
                "WHERE A.iConsecutivo = '$clave'";
      $result = $conexion->query($sql);
      $items  = $result->num_rows; 
      
      if($items > 0){     
            
            $data    = $result->fetch_assoc(); //<---Endorsement Data Array.
            $llaves  = array_keys($data);
            $datos   = $data; 
            
            foreach($datos as $i => $b){ 
                if($i != 'eStatus' && $i != 'sComentarios'){
                  $fields .= "\$('#$domroot :input[id=".$i."]').val('".$datos[$i]."');";  
                }
                else if($i == 'sComentarios'){$comentarios = utf8_decode($datos[$i]);}    
            }
            
            //CONSULTAMOS LA TABLA DE POLIZAS LIGADA AL ENDOSO               
            $query  = "SELECT iConsecutivoPoliza, B.iTipoPoliza ".
                      "FROM cb_endoso_adicional_estatus AS A ".
                      "INNER JOIN ct_polizas            AS B ON A.iConsecutivoPoliza = B.iConsecutivo AND B.iDeleted = '0' ".
                      "WHERE iConsecutivoEndoso = '$clave'"; 
            $result = $conexion->query($query);
            $rows   = $result->num_rows; 
            if($rows > 0){
                while($policies = $result->fetch_assoc()){
                   $policies_checkbox .= "\$('#$domroot :checkbox[value=".$policies['iConsecutivoPoliza']."]').prop('checked',true);";
                }
                       
            }  
            $eStatus    = $data['eStatus']; 
      }
      $conexion->rollback();
      $conexion->close(); 
      $response = array(
                    "msj"          => "$msj",
                    "error"        => "$error",
                    "fields"       => "$fields",
                    "policies"     => "$policies_checkbox",
                    "sComentarios" => "$comentarios"
                  );   
      echo json_encode($response);  
      
  }
  function save_endorsement(){
      
      //Conexion:
      include("cn_usuarios.php");  
      $conexion->autocommit(FALSE); 
                                                                                                                                                                                                                                           
      $success              = true;
      $error                = "0";  
      $mensaje              = "";
      $edit_mode            = trim($_POST['edit_mode']); 
      $iConsecutivo         = trim($_POST['iConsecutivo']); 
      $iConsecutivoCompania = trim($_POST['iConsecutivoCompania']);
      $sIP                  = $_SERVER['REMOTE_ADDR'];
      $sUsuario             = $_SESSION['usuario_actual'];
      $dFecha               = date("Y-m-d H:i:s");

      $valores = array();
      $campos  = array();
      
      //GUARDAMOS EL ENDOSO
      if($edit_mode == 'true'){
          
            foreach($_POST as $campo => $valor){
                if($campo != "accion" && $campo != "edit_mode" && $campo != "iConsecutivo" && strpos($campo,"chk_policies_") === false && $campo != "dFechaAplicacionHora"){ // Estos campos no se insertan a la tabla
                    if($campo == 'dFechaAplicacion'){$valor = date('Y-m-d',strtotime(trim($valor)));}else
                    if($campo == 'sComentarios' && $valos != ""){$valor = utf8_encode($valor);}
                    array_push($valores,"$campo='".$valor."'");
                }
            }
          
            //Campos adicionales:
            array_push($valores,"sUsuarioActualizacion='$sUsuario'");
            array_push($valores,"sIP='$sIP'");
            array_push($valores,"dFechaActualizacion='$dFecha'");
                
            $query   = "UPDATE cb_endoso_adicional SET ".implode(",",$valores)." WHERE iConsecutivo='$iConsecutivo' AND iConsecutivoCompania='$iConsecutivoCompania'";
            $mensaje = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The data has been updated successfully!</p>';
            
          
      }
      else{
          foreach($_POST as $campo => $valor){ 
            if($campo != "accion" && $campo != "edit_mode" && $campo != "iConsecutivo" && strpos($campo,"chk_policies_") === false && $campo != "dFechaAplicacionHora"){ // Estos campos no se insertan a la tabla
                if($campo == 'dFechaAplicacion'){$valor = date('Y-m-d',strtotime(trim($valor)));}else
                if($campo == 'sComentarios'){$valor = utf8_encode($valor);}
                array_push($campos, $campo);
                array_push($valores,date_to_server($valor));
            }
          }
         
          // Campos Adicionales:
          array_push($campos,"sUsuarioIngreso"); array_push($valores,$sUsuario);
          array_push($campos,"sIP");             array_push($valores,$sIP);
          array_push($campos,"eStatus");         array_push($valores,'S');
          
          $query   = "INSERT INTO cb_endoso_adicional (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')";
          $mensaje = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The data has been saved successfully!</p>';
      }
        
      $success = $conexion->query($query);
      if(!($success)){$error = '1';$mensaje = "Error to save the endorsement data, please try again later.";}
      else{
          if($edit_mode == 'false'){$iConsecutivo = $conexion->insert_id;} 
          //ELIMINAMOS POLIZAS GUARDADAS ANTERIORMENTE:
          $query   = "DELETE FROM cb_endoso_adicional_estatus WHERE iConsecutivoEndoso='$iConsecutivo'";  
          $success = $conexion->query($query); 
          if(!($success)){$error = '1';$mensaje = "Error to update the endorsement status data, please try again later.";}
          else{
              foreach($_POST as $campo => $valor){
                  if(!(strpos($campo,"chk_policies_") === false) && $valor == 1){
                      $poliza  = str_replace("chk_policies_","",$campo);
                      $query   = "INSERT INTO cb_endoso_adicional_estatus (iConsecutivoEndoso,iConsecutivoPoliza,eStatus,sIP,sUsuarioIngreso,dFechaIngreso) ".
                                 "VALUES('$iConsecutivo','$poliza','S','$sIP','$sUsuario','$dFecha')";
                      $success = $conexion->query($query);
                      if(!($success)){$error = '1';$mensaje = "Error to save the endorsement status data, please try again later.";} 
                  }
              }   
          }
      } 
      
      $success && $error == '0' ? $conexion->commit() : $conexion->rollback();
      $conexion->close();
      $response = array("error"=>"$error","msj"=>"$mensaje","iConsecutivo"=>"$iConsecutivo");
      echo json_encode($response);
  }
  
  // DETALLE
  function detalle_datagrid(){
      
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $iConsecutivo        = trim($_POST['iConsecutivoEndoso']);
      
      if($iConsecutivo != ""){
          #CONTAR REGISTROS                                                                                                              
          $sql    = "SELECT COUNT(iConsecutivoEndoso) AS total ".
                    "FROM cb_endoso_adicional_detalle ".
                    "WHERE iConsecutivoEndoso='$iConsecutivo'";
          $result = $conexion->query($sql);
          $rows   = $result->fetch_assoc();
          
          if($rows['total'] > 0){
             //Filtros de informacion //
            $filtroQuery = " WHERE iConsecutivoEndoso = '".$iConsecutivo."' "; 
            
            // ordenamiento//
            $ordenQuery = " ORDER BY sNombreCompania ASC";
            
            #CONSULTA:
            $sql    = "SELECT iConsecutivoDetalle, sNombreCompania, CONCAT(sDireccion,', ',sCiudad,', ',sEstado,' ',sCodigoPostal) AS sAddress, eAccion,eTipoEndoso   ".
                      "FROM cb_endoso_adicional_detalle ".$filtroQuery.$ordenQuery;
            $result = $conexion->query($sql);
            
            while ($items = $result->fetch_assoc()) { 
                
                 if($items['eAccion'] == "ADDSWAP"){$action = "ADD SWAP";}else
                 if($items['eAccion'] == "DELETESWAP"){$action = "DELETE SWAP";}
                 else{$action = $items['eAccion'];}
               
                 $htmlTabla .= "<tr>".
                               "<td id=\"idDet_".$items['iConsecutivoDetalle']."\">".$action."</td>".
                               "<td>".$items['sNombreCompania']."</td>".
                               "<td>".$items['eTipoEndoso']."</td>".
                               "<td>".$items['sAddress']."</td>". 
                               "<td>".
                                    "<div class=\"btn_edit_detalle btn-icon edit btn-left\" title=\"Edit data\"><i class=\"fa fa-pencil-square-o\"></i></div>".
                                    "<div class=\"btn_delete_detalle btn-icon trash btn-left\" title=\"Delete file\"><i class=\"fa fa-trash\"></i><span></span></div>".
                               "</td></tr>";  
            }
            $conexion->rollback();
            $conexion->close(); 
          }
          else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>"; }    
      }
      else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";} 
      $response = array("mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
      echo json_encode($response); 
  }
  function detalle_save(){
      include("cn_usuarios.php");  
      $conexion->autocommit(FALSE); 
      
      #VARIABLES:
      $success              = true;
      $edit_mode            = trim($_POST['edit_mode']);
      $iConsecutivoDetalle  = trim($_POST['iConsecutivoDetalle']);
      $iConsecutivoEndoso   = trim($_POST['iConsecutivoEndoso']);
      $campos               = array();
      $valores              = array();
      $error                = 0;
      $msj                  = "";
      $sIP                  = $_SERVER['REMOTE_ADDR'];
      $sUsuario             = $_SESSION['usuario_actual'];
      $dFecha               = date("Y-m-d H:i:s");
      
      if($edit_mode == "true"){
          foreach($_POST as $campo => $valor){
            if($campo != "accion" && $campo != "edit_mode" && $campo != "iConsecutivoDetalle" && $campo != "iConsecutivoEndoso" && $campo != "sComentarios"){ //Estos campos no se insertan a la tabla
                array_push($valores,"$campo='". strtoupper($valor)."'"); 
            }
          }
          
          $query = "UPDATE cb_endoso_adicional_detalle SET ".implode(",",$valores)." WHERE iConsecutivoDetalle ='$iConsecutivoDetalle' AND iConsecutivoEndoso = '$iConsecutivoEndoso'";
          $msj   = "The data has been updated successfully!."; 
      }
      else{
          foreach($_POST as $campo => $valor){
            if($campo != "accion" && $campo != "edit_mode" && $campo != "iConsecutivoDetalle" && $campo != "sComentarios"){ //Estos campos no se insertan a la tabla
                array_push($campos ,$campo);
                array_push($valores,strtoupper($valor)); 
            }
          }
          
          $query = "INSERT INTO cb_endoso_adicional_detalle (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')";
          $msj   = "The data has been saved successfully!."; 
      }
      
      $success = $conexion->query($query);
      if(!($success)){$conexion->rollback();$error = 1;}else{$conexion->commit();}
      $conexion->close();
      $response = array("error"=>"$error","msj"=>"$msj");
      echo json_encode($response);
  }
  function detalle_get(){
      
      #Err flags:
      $error = '0';
      $msj   = "";
      #Variables
      $fields   = "";
      $clave    = trim($_POST['iConsecutivoDetalle']);
      $idEndoso = trim($_POST['iConsecutivoEndoso']);
      $domroot  = $_POST['domroot']; 
      
      #Function Begin
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);
      $sql    = "SELECT * FROM cb_endoso_adicional_detalle WHERE iConsecutivoEndoso = '$idEndoso' AND iConsecutivoDetalle='$clave'";
      $result = $conexion->query($sql);
      $items  = $result->num_rows; 
      if ($items > 0) {
          $data    = $result->fetch_assoc();
          $llaves  = array_keys($data);
          $datos   = $data; 
            
          foreach($datos as $i => $b){ 
            $fields .= "\$('$domroot [name=".$i."]').val('".$datos[$i]."');";   
          }  
            
      }
      else{$error = "1"; $msj = "Error to data query, please try again later.";}
      $conexion->rollback();
      $conexion->close(); 
      
      $response = array("msj"=>"$msj", "error"=>"$error", "fields"=>"$fields",);   
      echo json_encode($response);
  }
  function detalle_delete(){
      #VARIABLES
      $error    = '0';
      $mensaje  = "";
      $fields   = "";
      $clave    = trim($_POST['iConsecutivoDetalle']);
      $idEndoso = trim($_POST['iConsecutivoEndoso']);
      
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE); 
      
      $query   = "DELETE FROM cb_endoso_adicional_detalle WHERE iConsecutivoDetalle='$clave' AND iConsecutivoEndoso='$idEndoso'";
      $success = $conexion->query($query);
      
      if(!($success)){$error = '1'; $mensaje = "Error to try delete data, please try again later.";}
      else{$mensaje = "The data has been deleted successfully!";}
      
      $error == "0" ? $conexion->commit() : $conexion->rollback();
      $conexion->close();
      
      $response = array("msj"=>"$mensaje","error"=>"$error",);   
      echo json_encode($response); 
  }
  
  #FUNCIONES EMAILS Y STATUS:
  function get_endorsement_estatus(){
      
      include("cn_usuarios.php");     
      $error       = "0";
      $clave       = trim($_POST['iConsecutivo']);
      $domroot     = trim($_POST['domroot']);
      
      #CONSULTAR DATOS DEL ENDOSO Y COMPANIA
      $sql    = "SELECT iConsecutivoEndoso,iConsecutivoPoliza,B.sNumeroPoliza,D.sDescripcion AS sTipoPoliza, sMensajeEmail, ".
                "IF(A.sEmail != '',A.sEmail,C.sEmail) AS sEmail, C.iConsecutivo AS iConsecutivoBroker, C.sName AS sBrokerName, C.bEndosoMensual  ".
                "FROM cb_endoso_adicional_estatus AS A ".
                "LEFT JOIN ct_polizas             AS B ON A.iConsecutivoPoliza  = B.iConsecutivo ".
                "LEFT JOIN ct_tipo_poliza         AS D ON B.iTipoPoliza = D.iConsecutivo ".
                "LEFT JOIN ct_brokers             AS C ON B.iConsecutivoBrokers = C.iConsecutivo ".
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
                   
      
                   /*if($data['bEndosoMensual'] == '1'){
                       $readonly       = "readonly=\"readonly\"";
                       $opacity        = "style=\"opacity:0.5;\"";
                       $noapply        = "no_apply_endoso";
                       $broker_option .= "<br><span style=\"color:#ef2828;\">This broker only accepts an endorsement by month.</span><br>";
                   }
                   else{$opacity = "";$noapply="";} */
                   
                   $opacity = "";$noapply="";
                    
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
          $query   = "UPDATE cb_endoso_adicional_estatus SET sEmail='".$data[1]."', sMensajeEmail='".$sMensaje."',sIP='$sIP',sUsuarioActualizacion='$sUsuario',dFechaActualizacion='$dFecha' ".
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
  function get_email_data($iConsecutivo){
          
      include("cn_usuarios.php");
      $Emails  = array();
      $error   = "";
      $mensaje = "";
      $file    = "";
      
      #DATOS DEL ENDOSO:
      $query  = "SELECT A.*, B.sNombreCompania FROM cb_endoso_adicional AS A ".
                "INNER JOIN ct_companias AS B ON A.iConsecutivoCompania = B. iConsecutivo ".
                "WHERE A.iConsecutivo = '$iConsecutivo' "; 
      $result = $conexion->query($query) or die($conexion->error);
      $rows   = $result->num_rows; 
      
      if($rows == 0){$error = '1';$mensaje = "Error to query the endorsement data, please try again later.";}
      else{
          
          $Endoso    = $result->fetch_assoc();
          $ComNombre = $Endoso['sNombreCompania']; 
              
          #CONSULTAR DESCRIPCION DEL ENDOSO: 
          $query  = "SELECT * FROM cb_endoso_adicional_detalle AS A WHERE A.iConsecutivoEndoso = '$iConsecutivo'";
          $result = $conexion->query($query) or die($conexion->error);
          $rows   = $result->num_rows;
          
          if($rows == 0){$error = '1';$mensaje = "Error to query the endorsement description data, please try again later.";}
          else{
              #DECLARAR ARRAY DE DETALLE:
              $Detalle = mysql_fetch_all($result);
              $countD  = count($Detalle);
              
              #CONSULTAMOS POLIZAS DEL ENDOSO E INFO DE LOS EMAILS:
              $query  = "SELECT iConsecutivoEndoso,iConsecutivoPoliza,B.sNumeroPoliza,B.iTipoPoliza,D.sDescripcion AS sTipoPoliza, sMensajeEmail, A.sEmail, C.iConsecutivo AS iConsecutivoBroker, C.sName AS sBrokerName, C.bEndosoMensual ".
                       "FROM cb_endoso_adicional_estatus   AS A ".
                       "LEFT JOIN ct_polizas     AS B ON A.iConsecutivoPoliza  = B.iConsecutivo ".
                       "LEFT JOIN ct_tipo_poliza AS D ON B.iTipoPoliza = D.iConsecutivo ".
                       "LEFT JOIN ct_brokers     AS C ON B.iConsecutivoBrokers = C.iConsecutivo ".
                       "WHERE A.iConsecutivoEndoso = '$iConsecutivo' ";//" AND C.bEndosoMensual='0'"; 
                       
              $result = $conexion->query($query) or die($conexion->error);
              $rows   = $result->num_rows;
              if($rows <= 0){$error = '1';$mensaje = "The emails can not be generated.Please check that the endorsement has brokers to send email from this module.";}
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
                    
                    $data['sMensajeEmail'] != "" ?  $message = $data['sMensajeEmail'] : $message = "Please create new endorsement for the following insured. ";
                    
                    #DATOS DEL CORREO:
                    $action   = $message."<br>$ComNombre, $sNumPoliza - $sTipoPoliza.";
                    $subject  = "$ComNombre//$sNumPoliza - $sTipoPoliza. Endorsement application - $message";
                    $bodyData = "<table cellspacing=\"0\" cellpadding=\"0\" style=\"color:#000;margin:5px auto; text-align:left;float:left;min-width:300px;\">";
                    $detalle  = "";
                    //Recorremos array de DETALLE:
                    for($x=0;$x<$countD;$x++){
                 
                         if($Detalle[$x]['eAccion'] == "ADDSWAP")   {$Detalle[$x]['eAccion'] = "ADD SWAP";}
                         if($Detalle[$x]['eAccion'] == "DELETESWAP"){$Detalle[$x]['eAccion'] = "DELETE SWAP";}
                         
                         $eAccion     = $Detalle[$x]['eAccion'];
                         $eTipoEndoso = $Detalle[$x]['eTipoEndoso'];
                         $sCompania   = $Detalle[$x]['sNombreCompania'];
                         $sDireccion  = $Detalle[$x]['sDireccion'];
                         $sEstado     = $Detalle[$x]['sEstado'];
                         $sCiudad     = $Detalle[$x]['sCiudad'];
                         $sCodigoP    = $Detalle[$x]['sCodigoPostal'];
                         
                         $detalle .= "<tr>";
                         $detalle .= "<td style=\"padding:1px 3px;\">$eAccion</td>";
                         $detalle .= "<td style=\"padding:1px 3px;\">$eTipoEndoso</td>";
                         $detalle .= "<td style=\"padding:1px 3px;\">$sCompania</td>";
                         $detalle .= "<td style=\"padding:1px 3px;\">$sDireccion, $sCiudad, $sEstado. $sCodigoP</td>";
                         $detalle .= "</tr>";
                    }
                 
                    $bodyData .= $detalle."</table>";
                    $bodyData .= "</p><br><br>";
                    
                    $htmlEmail = "<table style=\"font-size:12px;border:1px solid #6191df;border-radius:3px;padding:10px;width:95%; margin:5px auto;font-family: Arial, Helvetica, sans-serif;\">".
                                 "<tr><td><h2 style=\"color:#313131;text-transform: uppercase; text-align:center;\">Endorsement application from Solo-Trucking Insurance</h2></td></tr>".
                                 "<tr><td><p style=\"color:#000;margin:5px auto; text-align:left;\">$action</p><br></td></tr>".
                                 "<tr><td style=\"text-align:left;\">$bodyData</td></tr>".
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
          if($Emails['error']=="0"){
              #UPDATE ENDORSEMENT DETAILS:
              $query   = "UPDATE cb_endoso_adicional SET eStatus = 'SB',dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."', sUsuarioActualizacion='".$_SESSION['usuario_actual']."' ".
                         "WHERE iConsecutivo = '$iConsecutivo'"; 
              $success = $conexion->query($query);
              if(!($success)){$success = false;$mensaje="Error to update the endorsement status, please check with de system admin.";}     
          }
          else{$success = false;$msj=$Emails['error'];}
      }
      if($success){
        for($x=0;$x < $count;$x++){
          if($Emails[$x]['html']!= ""){
                  
            #UPDATE ENDORSEMENT DETAILS:
            $query = "UPDATE cb_endoso_adicional_estatus SET eStatus = 'SB', dFechaAplicacion='".date("Y-m-d H:i:s")."', dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."', sUsuarioActualizacion='".$_SESSION['usuario_actual']."' ".
                     "WHERE iConsecutivoEndoso = '$iConsecutivo' AND iConsecutivoPoliza = '".$Emails[$x]['idPoliza']."'"; 
            $success = $conexion->query($query);
            if(!($success)){$success = false;$mensaje="Error to update the endorsement status, please check with de system admin.";}
            else{
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
                
                #VERIFICAR SERVIDOR DONDE SE ENVIAN CORREOS:
                if($_SERVER["HTTP_HOST"]=="stdev.websolutionsac.com"){
                  $mail->Username   = "systemsupport@solo-trucking.com";  // GMAIL username
                  $mail->Password   = "SL09100242";  
                }else if($_SERVER["HTTP_HOST"] == "solotrucking.laredo2.net" || $_SERVER["HTTP_HOST"] == "st.websolutionsac.com" || $_SERVER["HTTP_HOST"] == "www.solo-trucking.com"){
                  $mail->Username   = "customerservice@solo-trucking.com";  // GMAIL username
                  $mail->Password   = "SL641404tK";   
                }
                
                $mail->SetFrom('customerservice@solo-trucking.com', 'Customer Service Solo-Trucking Insurance');
                $mail->AddReplyTo('customerservice@solo-trucking.com', 'Customer Service Solo-Trucking Insurance'); 
                $mail->AddCC('systemsupport@solo-trucking.com','System Support Solo-Trucking Insurance');
                
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
                /*$files        = $Emails[$x]['files'];
                $delete_files = "";
                if($files != ""){
                   include("./lib/fpdf153/fpdf.php");//libreria fpdf
                   $file_tmp = fopen('tmp/'.$files["name"],"w") or die("Error when creating the file. Please check."); 
                   fwrite($file_tmp,$files["content"]); 
                   fclose($file_tmp);     
                   $archivo = "tmp/".$files["name"];  
                   $mail->AddAttachment($archivo);
                   $delete_files .= "unlink('tmp/.".$files["name"]."');"; 
                } */
                
                $mail_error = false;
                if(!$mail->Send()){$mail_error = true; $mail->ClearAddresses();}
                if(!($mail_error)){$msj = "The mail has been sent to the brokers";}
                else{$msj = "Error: The e-mail cannot be sent.";$error = "1";}
                
                /*$mail->ClearAttachments(); 
                eval($delete_files); */  
            }    

          } 
      }
      }
      
      $success && $error == '0' ? $conexion->commit() : $conexion->rollback();
      $conexion->close();
      
      $response = array("msj"=>"$msj","error"=>"$error","tabla" => "$htmlTabla");   
      echo json_encode($response);    
  }

  /*------FUNCIONES GENERALES DEL MODULO DE SOLICITUD DE ENDOSOS -----------------------*/
  function get_estatus_info(){
      include("cn_usuarios.php");     
      $error       = "0";
      $clave       = trim($_POST['iConsecutivoEndoso']);
      $domroot     = trim($_POST['domroot']);
      
      #CONSULTAR DATOS DEL ENDOSO Y COMPANIA
      $sql    = "SELECT iConsecutivoEndoso,iConsecutivoPoliza,A.eStatus, B.sNumeroPoliza,B.iTipoPoliza,D.sDescripcion AS sTipoPoliza,C.iConsecutivo AS iConsecutivoBroker,C.sName AS sBrokerName,".
                "C.bEndosoMensual,A.sComentarios,A.sNumeroEndosoBroker,A.rImporteEndosoBroker,DATE_FORMAT(A.dFechaActualizacion,'%m/%d/%Y %H:%i') AS dFechaActualizacion, ".
                "DATE_FORMAT(A.dFechaAplicacion,'%m/%d/%Y %H:%i') AS dFechaAplicacion ".
                "FROM cb_endoso_adicional_estatus AS A ".
                "LEFT JOIN ct_polizas             AS B ON A.iConsecutivoPoliza = B.iConsecutivo ".
                "LEFT JOIN ct_tipo_poliza         AS D ON B.iTipoPoliza = D.iConsecutivo ".
                "LEFT JOIN ct_brokers             AS C ON B.iConsecutivoBrokers = C.iConsecutivo ".
                "WHERE iConsecutivoEndoso = '$clave' ";
      $result = $conexion->query($sql); 
      $rows   = $result->num_rows; 
      
      if($rows > 0){ 
          while ($data = $result->fetch_assoc()){
              
              $tipoPoliza = get_policy_type($data['iTipoPoliza']);
              $sNumPoliza = $data['sNumeroPoliza'];
              $sDescPoliza= $data['sTipoPoliza']; 
              $sBroker    = $data['sBrokerName'];
              
              //Revisamos si el endoso aplica para envio mensual... (no debe aparecer aqui.)
              $endosoFields = "";
             
              //$fechaActualizacion = "<span>Last updated: ".$data['dFechaActualizacion']."</span>";
              $label  = "style=\"display: block;float: left;margin: 2px 0px;padding:5px 0px;\"";
              $input  = "style=\"width: 96%;clear: none;margin: 2px 0!important;height:20px!important;resize: none;\"";
              $textar = "style=\"width: 96%;clear: none;margin: 2px 0!important;height:22px!important;resize: none;padding-top: 0px!important;\"";
              //$select = "style=\"float: right;width: 100%!important;clear: none;margin: 2px!important;height:25px!important;\"";
              //$div    = "style=\"clear:both;\""; 
                       
              $endosoFields .= "<table style=\"width:100%;\">";
              $endosoFields .= "<tr>";
              
              //COLUMNA 1
              /*$endosoFields .= "<td style=\"vertical-align:top;\">";
              $endosoFields .= "<div $div>".
                                    "<label $label>Endorsement No:</label>".
                                    "<input $input type=\"text\" maxlength=\"15\" name=\"sNumeroEndosoBroker\" title=\"This number is the one granted by the broker for the endorsement.\" placeholder=\"Endorsement No:\">".
                               "</div>";
              $endosoFields .= "<div $div>".
                                    "<label $label>Amount \$:</label>".
                                    "<input $input type=\"text\" name=\"rImporteEndosoBroker\" title=\"Endorsement Amount \$\" placeholder=\"\$ 0000.00\" class=\"decimals\">".
                               "</div>";
              $endosoFields .= "</td>";
              
              //COLUMNA 2
              $endosoFields .= "<td style=\"vertical-align:top;\">";
              $endosoFields .= "<div $div>".
                                    "<label $label>Status:</label>".
                                    "<select $select id=\"eStatus_".$data['iConsecutivoPoliza']."\"  name=\"eStatus\">".
                                    "<option value=\"SB\">SENT TO BROKERS</option>".
                                    "<option value=\"P\">IN PROCESS</option>".
                                    "<option value=\"A\">APPROVED</option>".
                                    "<option value=\"D\">CANCELED/DENIED</option>".
                                    "</select>".
                               "</div>";
              $endosoFields .= "<div $div>".
                                    "<label $label>Comments:</label>".
                                    "<textarea $textar id=\"sComentarios_".$data['iConsecutivoPoliza']."\" name=\"sComentarios\" maxlength=\"1000\" title=\"Max. 1000 characters.\"></textarea>".
                               "</div>";
              $endosoFields .= "</td>"; */
              
              //COLUMNA 1
              $endosoFields .= "<td style=\"vertical-align:top;\">".
                               "<div $div>".
                                    "<label $label>Endorsement No:</label>".
                                    "<input $input class=\"end-num txt-uppercase\" type=\"text\" maxlength=\"15\" name=\"sNumeroEndosoBroker\" title=\"This number is the one granted by the broker for the endorsement.\" placeholder=\"Endorsement No:\">".
                               "</div>".
                               "</td>";
              // COLUMNA 2
              $endosoFields .= "<td style=\"vertical-align:top;\">".
                               "<div $div>".
                                    "<label $label>Amount:</label>".
                                    "<input $input type=\"text\" name=\"rImporteEndosoBroker\" title=\"Endorsement Amount \$\" placeholder=\"\$ 0000.00\" class=\"decimals\">".
                               "</div>".
                               "</td>";
              //COLUMNA 3
              $endosoFields .= "<td style=\"vertical-align:top;\">".
                               "<div $div>".
                                    "<label $label>Comments to Accounting:</label>".
                                    "<textarea $textar id=\"sComentarios_".$data['iConsecutivoPoliza']."\" name=\"sComentarios\" maxlength=\"1000\" title=\"Max. 1000 characters.\"></textarea>".
                                    "<select id=\"eStatus_".$data['iConsecutivoPoliza']."\"  name=\"eStatus\" style=\"display:none;\" >".
                                    "<option value=\"SB\">SENT TO BROKERS</option>".
                                    "<option value=\"P\">IN PROCESS</option>".
                                    "<option value=\"A\">APPROVED</option>".
                                    "<option value=\"D\">CANCELED/DENIED</option>".
                                    "</select>".
                               "</div>".
                               "</td>";
                               
              $endosoFields .= "</tr>";
              $endosoFields .= "</table>"; 
              
              /*$endosoFields .= "<div $div>".
                               "<label $label>Endorsement No:</label>".
                               "<input $input type=\"text\" maxlength=\"15\" name=\"sNumeroEndosoBroker\" title=\"This number is the one granted by the broker for the endorsement.\" placeholder=\"Endorsement No:\">".
                               "</div>".
                                   "<div $div>".
                                    "<label $label>Amount \$:</label>".
                                    "<input $input type=\"text\" name=\"rImporteEndosoBroker\" title=\"Endorsement Amount \$\" placeholder=\"\$ 0000.00\" class=\"decimals\">".
                                   "</div>".
                                   "<div $div>".
                                    "<label $label>Status:</label>".
                                    "<select $select id=\"eStatus_".$data['iConsecutivoPoliza']."\"  name=\"eStatus\">".
                                    "<option value=\"SB\">SENT TO BROKERS</option>".
                                    "<option value=\"P\">IN PROCESS</option>".
                                    "<option value=\"A\">APPROVED</option>".
                                    "<option value=\"D\">CANCELED/DENIED</option>".
                                    "</select>".
                                   "</div>";
              $endosoFields .= "<div $div>".
                               "<label $label>Comments:</label><textarea $textar id=\"sComentarios_".$data['iConsecutivoPoliza']."\" name=\"sComentarios\" maxlength=\"1000\" title=\"Max. 1000 characters.\"></textarea>".
                               "</div>";*/
              
              $html .= '<h3>';
              $html .= "<span style='display: -webkit-inline-box;width: 20%;'>".$sNumPoliza."</span>";
              $html .= "<span style='display: -webkit-inline-box;width: 30%;'>".$sDescPoliza."</span>";
              $html .= "<span style='display: -webkit-inline-box;width: 40%;'>".$sBroker."</span>";
              $html .= '</h3>';
              $html .= '<div id="dataPolicy_'.$data['iConsecutivoPoliza'].'" class="data_policy" style="height:70px!important;">'.$endosoFields.'</div>';                        

              #FIELDS:
              $llaves  = array_keys($data);
              $datos   = $data;
                      
              foreach($datos as $i => $b){
                    if($i == "sComentarios" || $i == "eStatus" || $i == "sNumeroEndosoBroker" || $i == "rImporteEndosoBroker"){
                      if($i == 'sComentarios'){$value = utf8_decode(utf8_encode($datos[$i]));}else{$value = $datos[$i];}
                      $fields .= "\$('#$domroot #dataPolicy_".$data['iConsecutivoPoliza']." :input[name=".$i."]').val('$value');\n";  
                    }
              }
          }
          #consultar comentarios del endoso:
          $query  = "SELECT iConsecutivo AS iConsecutivoEndoso, eStatus, sComentarios FROM cb_endoso_adicional WHERE iConsecutivo = '$clave'";
          $result = $conexion->query($query);
          $rows   = $result->num_rows;
          if($rows > 0){
            $data    = $result->fetch_assoc();
            $fields .= "\$('#$domroot :input[name=sComentariosEndoso]').val('".utf8_decode($data['sComentarios'])."');\n"; 
            $fields .= "\$('#$domroot :input[name=iConsecutivoEndoso]').val('".$data['iConsecutivoEndoso']."');\n";  
            $fields .= "\$('#$domroot :input[name=eStatusEndoso]').val('".utf8_decode($data['eStatus'])."');\n"; 
        
          }
          
          #CONSULTAR DESCRIPCION DEL ENDOSO: 
          $query  = "SELECT * FROM cb_endoso_adicional_detalle AS A WHERE A.iConsecutivoEndoso = '$clave'";
          $result = $conexion->query($query) or die($conexion->error);
          $rows   = $result->num_rows;
          if($rows > 0){
              #DECLARAR ARRAY DE DETALLE:
              $Detalle = mysql_fetch_all($result);
              $countD  = count($Detalle);
              
              //Recorremos array de DETALLE:
              for($x=0;$x<$countD;$x++){
             
                     if($Detalle[$x]['eAccion'] == "ADDSWAP")   {$Detalle[$x]['eAccion'] = "ADD SWAP";}
                     if($Detalle[$x]['eAccion'] == "DELETESWAP"){$Detalle[$x]['eAccion'] = "DELETE SWAP";}
                     
                     $eAccion     = $Detalle[$x]['eAccion'];
                     $eTipoEndoso = $Detalle[$x]['eTipoEndoso'];
                     $sCompania   = $Detalle[$x]['sNombreCompania'];
                     $sDireccion  = $Detalle[$x]['sDireccion'];
                     $sEstado     = $Detalle[$x]['sEstado'];
                     $sCiudad     = $Detalle[$x]['sCiudad'];
                     $sCodigoP    = $Detalle[$x]['sCodigoPostal'];
                     
                     $detalle .= "<tr>";
                     $detalle .= "<td style=\"padding:1px 3px;\">$eAccion</td>";
                     $detalle .= "<td style=\"padding:1px 3px;\">$eTipoEndoso</td>";
                     $detalle .= "<td style=\"padding:1px 3px;\">$sCompania</td>";
                     $detalle .= "<td style=\"padding:1px 3px;\">$sDireccion, $sCiudad, $sEstado. $sCodigoP</td>";
                     $detalle .= "</tr>";
              }
          }
      }
      else{$error = '1';} 

      $response = array("fields"=>"$fields","error"=>"$error","html"=>"$html","detalle"=>$detalle);   
      echo json_encode($response); 
  }
  function save_estatus_info(){

      $error          = '0';  
      $mensaje        = ""; 
      $Comentarios    = trim($_POST['sMensaje']);
      $iConsecutivo   = trim($_POST['iConsecutivoEndoso']);
      $PolizasEstatus = trim($_POST['polizas']);
      $eStatus        = trim($_POST['eStatusEndoso']); 
      
      if(isset($_FILES['file-0'])){
          $file        = fopen($_FILES['file-0']["tmp_name"], 'r'); 
          $fileContent = fread($file, filesize($_FILES['file-0']["tmp_name"]));
          $fileName    = $_FILES['file-0']['name'];
          $fileType    = $_FILES['file-0']['type']; 
          $fileTmpName = $_FILES['file-0']['tmp_name']; 
          $fileSize    = $_FILES['file-0']['size']; 
          $fileError   = $_FILES['file-0']['error'];
          $fileExten   = explode(".",$fileName);
      }  
 
      //Conexion:
      include("cn_usuarios.php");  
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      //Revisar si hay que actualizar polizas:
      $array       = explode(";",$PolizasEstatus);
      $count       = count($array);
      $iAprobacion = 0;
      $iDenegado   = 0;
      
      if($count > 0){
          
          for($x=0;$x < $count; $x++){
              $actualiza = "";
              $poliza    = explode("|",$array[$x]);
              $polizaID  = $poliza[0];
              $eStatus == "A" ? $eStatusP = 'A' : $eStatusP  = trim($poliza[1]);
              
              $actualiza .= " eStatus='$eStatusP' "; 
              $actualiza != "" ? $actualiza .= ", sComentarios='".utf8_encode(trim($poliza[2]))."'" : $actualiza = "sComentarios='".utf8_encode(trim($poliza[2]))."'";
              $actualiza != "" ? $actualiza .= ", sNumeroEndosoBroker='".trim($poliza[3])."'"       : $actualiza = "sNumeroEndosoBroker='".trim($poliza[3])."'"; 
              $actualiza != "" ? $actualiza .= ", rImporteEndosoBroker='".trim($poliza[4])."'"      : $actualiza = "rImporteEndosoBroker='".trim($poliza[4])."'"; 
              
              if($actualiza != "" && $polizaID != ""){
                 $query   = "UPDATE cb_endoso_adicional_estatus SET $actualiza WHERE iConsecutivoPoliza ='$polizaID' AND iConsecutivoEndoso = '$iConsecutivo'";
                 $success = $conexion->query($query);
                 if(!($success)){$transaccion_exitosa = false;$mensaje = "The data was not updated properly, please try again.";}
                 
                 //Incrementamos contador para verificar aprobacion:
                 if($eStatusP == "A"){
                     
                     $iAprobacion++;
                     $validaAccion = set_endoso_poliza($iConsecutivo,$polizaID,$conexion);
                    
                     if(!($validaAccion)){$transaccion_exitosa=false;$mensaje="The policy data was not updated properly, please try again.";}
                     else{
                        $mensaje = "The data has been saved successfully and updated in the company policy. <br>Thank you!"; 
                     }
                 }else
                 if($eStatusP == "D"){$iDenegado++;}
              }
              
          }
          //VERIFICAMOS SI TODOS LOS ESTATUS ESTAN APROBADOS, MARCAMOS EL ENDOSO TAMBIEN:
          if($iAprobacion == $count){$eStatus = 'A';}else
          if($iDenegado == $count){$eStatus = 'D';}  
      }
      
      if($transaccion_exitosa){
          $actualiza = "eStatus='$eStatus'";
          if($Comentarios != ""){$actualiza .= ", sComentarios='".utf8_encode($Comentarios)."'";}
          
          if($actualiza != ""){
              $query   = "UPDATE cb_endoso_adicional SET $actualiza WHERE iConsecutivo = '$iConsecutivo'"; 
              $success = $conexion->query($query); 
              if(!($success)){$transaccion_exitosa = false;$mensaje = "The data was not saved properly, please try again.";}
          }
      }
      
      //Subir archivo
      if($transaccion_exitosa && $fileName != ""){
          if(count($fileExten) != 2){$transaccion_exitosa = false;$mensaje = "Error: Please check that the name of the file should not contain points.";}
          else{
            //Extension Valida:
              $fileExten = strtolower($fileExten[1]);
              if($fileExten != "pdf" && $fileExten != "jpg" && $fileExten != "jpeg" && $fileExten != "png" && $fileExten != "doc" && $fileExten != "docx" && $fileExten != "xlsx" && $fileExten != "xls" && $fileExten != "mp3" && $fileExten != "mp4" && $fileExten != "key" && $fileExten != "cer" && $fileExten != "zip" && $fileExten != "ppt" && $fileExten != "pptx"){
                  $transaccion_exitosa = false; $mensaje="Error: The file extension is not valid, please check it.";
              }
              else{
                  //Verificar Tamaño:
                  if($fileSize > 0  && $fileError == 0){
                      $sContenido           = $conexion->real_escape_string($fileContent);
                      $eArchivo             = trim('ENDORSEMENT'); 
                      $sql = "INSERT INTO cb_endoso_adicional_files (sNombreArchivo, sTipoArchivo, iTamanioArchivo, hContenidoDocumentoDigitalizado, eArchivo,iConsecutivoEndoso, dFechaIngreso, sIP, sUsuarioIngreso) ".
                             "VALUES('$fileName','$fileType','$fileSize','$sContenido','$eArchivo','$iConsecutivo','".date("Y-m-d H:i:s")."', '".$_SERVER['REMOTE_ADDR']."', '".$_SESSION['usuario_actual']."')"; 
                      
                      if(!($conexion->query($sql))){$transaccion_exitosa = false; $mensaje = "A general system error ocurred : internal error";}       
                  }
                  else{$transaccion_exitosa = false;  $mensaje = "Error: The file you are trying to upload is empty or corrupt, please check it and try again.";}
              }    
          }
      }
       
      if($transaccion_exitosa){
            $conexion->commit();
            $conexion->close();
            if($mensaje == ""){$mensaje = "The data has been saved successfully, Thank you!";}
      }
      else{
            $conexion->rollback();
            $conexion->close(); 
            $error = "1";
      }
      
      $response = array("error"=>"$error","msj"=>"$mensaje");
      echo json_encode($response);
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
      
      
      // Filtros de informacion //
      $filtroQuery = " WHERE iConsecutivoEndoso = '".$iConsecutivo."' ";
        
      // Ordenamiento//
      $ordenQuery = " ORDER BY ".$_POST["ordenInformacion"]." ".$_POST["sortInformacion"];
        
      $pagina_actual   == "0" ? $pagina_actual = 1 : false;
      $limite_superior = $registros_por_pagina;
      $limite_inferior = ($pagina_actual*$registros_por_pagina)-$registros_por_pagina;
          
      $sql    = "SELECT iConsecutivo, sTipoArchivo,sNombreArchivo,iTamanioArchivo,eArchivo FROM cb_endoso_adicional_files ".$filtroQuery.$ordenQuery;
      $result = $conexion->query($sql);
      $rows   = $result->num_rows; 
             
      if($rows > 0){    
            while ($items = $result->fetch_assoc()) { 
                $htmlTabla .= "<tr>".
                              "<td id=\"idFile_".$items['iConsecutivo']."\">".$items['sNombreArchivo']."</td>".
                              "<td>".$items['eArchivo']."</td>".
                              "<td>".$items['sTipoArchivo']."</td>".
                              "<td>".$items['iTamanioArchivo']."</td>". 
                              "<td>".
                                   "<div class=\"btn-icon edit btn-left\" title=\"Open file in a new window\" onclick=\"window.open('open_pdf.php?idfile=".$items['iConsecutivo']."&type=endoso_add');\"><i class=\"fa fa-external-link\"></i><span></span></div>". 
                                   "<div class=\"btn_delete_file btn-icon trash btn-left\" title=\"Delete file\"><i class=\"fa fa-trash\"></i><span></span></div>".
                              "</td></tr>";  
            }
            $conexion->rollback();
            $conexion->close();                                                                                                                                                                       
      }
      else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";}
  
      $response = array("total"=>"$paginas_total","pagina"=>"$pagina_actual","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
      echo json_encode($response);
  }
  function guarda_pdf_endoso(){
      
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);
      //Variables:
      $error                  = "0";                                                                                                                                                                                                                                    
      $transaccion_exitosa    = true; 
      $_POST['iConsecutivo'] != "" ? $edit_mode = true : $edit_mode = false; 
       
      //Revisamos Archivo:   
      if(isset($_FILES['file-0'])){
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
                         $sql = "UPDATE cb_endoso_adicional_files SET sNombreArchivo ='$fileName', sTipoArchivo ='$fileType', iTamanioArchivo ='$fileSize', ".
                                "hContenidoDocumentoDigitalizado='$sContenido', eArchivo='$eArchivo', ".
                                "dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."', sUsuarioActualizacion='".$_SESSION['usuario_actual']."'".
                                "WHERE iConsecutivo ='".trim($_POST['iConsecutivo'])."'";  
                      }
                      #INSERT
                      else{
                         $sql = "INSERT INTO cb_endoso_adicional_files (sNombreArchivo, sTipoArchivo, iTamanioArchivo, hContenidoDocumentoDigitalizado, eArchivo,iConsecutivoEndoso, dFechaIngreso, sIP, sUsuarioIngreso) ".
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
                  }else{$error = "1";$mensaje = "Error: The file you are trying to upload is empty or corrupt, please check it and try again.";}
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
      $query = "DELETE FROM cb_endoso_adicional_files WHERE iConsecutivo = '$iConsecutivo'"; 
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
  
  #FUNCION PARA APLICAR ACCION DEL ENDOSO EN UNIDAD/POLIZA:
  function set_endoso_poliza($iConsecutivoEndoso,$iConsecutivoPoliza,$conexion){
    
      $transaccion_exitosa = true;
      
      #CONSULTAR DATOS DEL ENDOSO:
      $query  = "SELECT * FROM cb_endoso_adicional WHERE iConsecutivo='$iConsecutivoEndoso'"; 
      $result = $conexion->query($query); 
      $rows   = $result->num_rows; 
      
      if($rows > 0){
          
          $data                 = $result->fetch_assoc();
          $dFechaEndoso         = $data['dFechaAplicacion'];
          $iConsecutivoCompania = $data['iConsecutivoCompania'];
          
          //Consultamos las unidades relacionadas al endoso:
          $query  = "SELECT * FROM cb_endoso_adicional_detalle WHERE iConsecutivoEndoso='$iConsecutivoEndoso'"; 
          $result = $conexion->query($query); 
          $rows   = $result->num_rows; 
          
          if($rows > 0){
              //Recorremos resultado:
              while ($item = $result->fetch_assoc()){ 
                  
                  //Tomamos variables:
                  $eAccion   = $item['eAccion'];
                  $sNombreCo = $item['sNombreCompania'];
                  $sTipoCo   = $item['eTipoEndoso'];
                  $sDireccion= $item['sDireccion'];
                  $sEstado   = $item['sEstado'];
                  $sCiudad   = $item['sCiudad'];
                  $sCodigoP  = $item['sCodigoPostal'];
                  $sIP       = $_SERVER['REMOTE_ADDR'];
                  $sUsuario  = $_SESSION['usuario_actual'];
                  
                  //Buscamos si ya existe una compania con el mismo nombre y categoria:
                  $query = "SELECT iConsecutivo FROM ct_companias_adicionales WHERE sNombreCompania='$sNombreCo' AND eTipo='$sTipoCo'";
                  $row   = $conexion->query($query);
                  $row   = $row->fetch_assoc();
                 
                  //UPDATE REGISTRO
                  if($row['iConsecutivo'] != ""){
                     $query   = "UPDATE ct_companias_adicionales SET sNombreCompania='$sNombreCo',sDireccion='$sDireccion',sEstado='$sEstado',sCiudad='$sCiudad', ".
                                "sCodigoPostal='$sCodigoP',iActiva='1',eTipo='$sTipoCo',sIPIngreso='$sIP',sUsuarioIngreso='$sUsuario' WHERE iConsecutivo='$iConsecutivo'";
                     $success = $conexion->query($query);
                     $idDetalle= $row['iConsecutivo']; 
                  }
                  //INSERT REGISTRO
                  else{
                     $query   = "INSERT INTO ct_companias_adicionales (iConsecutivoCompania,sNombreCompania,sDireccion,sEstado,sCiudad,sCodigoPostal,iActiva,eTipo,sIPIngreso,sUsuarioIngreso) ".
                                "VALUES('$iConsecutivoCompania','$sNombreCo','$sDireccion','$sEstado','$sCiudad','$sCodigoP','1','$sTipoCo','$sIP','$sUsuario')";
                     $success = $conexion->query($query); 
                     $idDetalle= $conexion->insert_id;
                  }
                  
                  if($success){
                     //Revisamos si ya existe en el catalogo con relacion a poliza:
                     $query = "SELECT COUNT(iConsecutivoPoliza) AS total FROM cb_poliza_companias_adicionales WHERE iConsecutivoPoliza='$iConsecutivoPoliza' AND iConsecutivoAdicional='$idDetalle'";
                     $row   = $conexion->query($query);
                     $row   = $row->fetch_assoc();
                     
                     if($eAccion == "ADD" || $eAccion == "ADDSWAP"){$iDeleted = 0;}else
                     if($eAccion == "DELETE" || $eAccion == "DELETESWAP"){$iDeleted = 1;}
                     
                     //UPDATE POLIZA/COMPANIA
                     if($row['total'] != 0){
                        $query   = "UPDATE cb_poliza_companias_adicionales SET iDeleted='$iDeleted',dFechaActualizacion='$dFechaEndoso',sIPActualizacion='".$_SERVER['REMOTE_ADDR']."',sUsuarioActualizacion='".$_SESSION['usuario_actual']."' ".
                                   "WHERE iConsecutivoPoliza='$iConsecutivoPoliza' AND iConsecutivoAdicional='$idDetalle'";
                        $success = $conexion->query($query);     
                     }
                     //INSERT POLIZA/COMPANIA
                     else{
                        $query   = "INSERT INTO cb_poliza_companias_adicionales (iConsecutivoPoliza,iConsecutivoAdicional,eModoIngreso,dFechaIngreso,sIPIngreso,sUsuarioIngreso,iDeleted) ".
                                   "VALUES('$iConsecutivoPoliza','$idDetalle','ENDORSEMENT','$dFechaEndoso','$sIP','$sUsuario','$iDeleted')";
                        $success = $conexion->query($query);     
                     } 
                  
                  } 
                  
                  //CONSULTAMOS, SI EL REGISTRO NO ESTA ACTUALMENTE EN NINGUNA POLIZA, LO MARCAREMOS COMO ELIMINADO EN EL CATALOGO:
                  if(($eAccion == "DELETE" || $eAccion == "DELETESWAP") && $success){
          
                      $query = "SELECT COUNT(A.iConsecutivo) AS total ".
                               "FROM ct_companias_adicionales AS A INNER JOIN cb_poliza_companias_adicionales AS B ON A.iConsecutivo = B.iConsecutivoAdicional ".
                               "WHERE A.iConsecutivo = '$idDetalle'";
                      $r     = $conexion->query($query);
                      $valid = $r->fetch_assoc();
                      $valid['total'] > 0 ? $iElimina = false : $iElimina = true;
                      
                      if($iElimina){
                        $query   = "UPDATE ct_companias_adicionales SET iActiva='0' WHERE iConsecutivo='$idDetalle'";
                        $success = $conexion->query($query); 
                        if(!($success)){$error = '1'; $mensaje = "Error to try update data, please try again later.";}
                      }
                      
                  }
                   
              }
          }
          else{$transaccion_exitosa = false;}
      }
      else{$transaccion_exitosa = false;}
   
      return $transaccion_exitosa;
      
  }
  
?>
