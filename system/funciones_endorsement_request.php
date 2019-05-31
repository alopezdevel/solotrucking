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
     $filtroQuery = " WHERE A.eStatus != 'E' AND iConsecutivoTipoEndoso = '2' AND A.iDeleted='0' ";
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
                  "LEFT JOIN ct_companias   AS D ON A.iConsecutivoCompania   = D.iConsecutivo ".
                  "LEFT JOIN ct_operadores  AS F ON A.iConsecutivoOperador   = F.iConsecutivo  ".$filtroQuery; 
    $Result = $conexion->query($query_rows);
    $items = $Result->fetch_assoc();
    $registros = $items["total"];
    if($registros == "0"){$pagina_actual = 0;}
    $paginas_total = ceil($registros / $registros_por_pagina);
    
    if($registros == "0"){
        $limite_superior = 0;
        $limite_inferior = 0;
        $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";
    }
    else{
      $pagina_actual == "0" ? $pagina_actual = 1 : false;
      $limite_superior = $registros_por_pagina;
      $limite_inferior = ($pagina_actual*$registros_por_pagina)-$registros_por_pagina;
       
      $sql = "SELECT A.iConsecutivo,D.sNombreCompania,DATE_FORMAT(A.dFechaAplicacion, '%m/%d/%Y') AS dFechaIngreso,C.sDescripcion,A.eStatus,eAccion,D.iOnRedList, F.sNombre, iEndosoMultiple ".
             "FROM cb_endoso AS A ".
             "LEFT JOIN ct_tipo_endoso AS C ON A.iConsecutivoTipoEndoso = C.iConsecutivo ".
             "LEFT JOIN ct_companias   AS D ON A.iConsecutivoCompania   = D.iConsecutivo ".
             "LEFT JOIN ct_operadores  AS F ON A.iConsecutivoOperador   = F.iConsecutivo ".
             $filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
      $result = $conexion->query($sql);
      $rows = $result->num_rows; 
         
        if ($rows > 0) {    
            while ($items = $result->fetch_assoc()) { 
                 $btn_confirm = "";
                 $estado      = "";
                 $class       = "";
                 $descripcion = ""; 
                 
                 switch($items["eStatus"]){
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
                        $btn_confirm.= "<div class=\"btn-icon send-email btn-left\" title=\"See the e-mail sent\" onclick=\"fn_endorsement.email.preview('".$items['iConsecutivo']."');\"><i class=\"fa fa-external-link\"></i></div>"; 
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
                        $btn_confirm.= "<div class=\"btn-icon send-email btn-left\" title=\"See the e-mail sent\" onclick=\"fn_endorsement.email.preview('".$items['iConsecutivo']."');\"><i class=\"fa fa-external-link\"></i></div>";
                     break;
                     case 'P': 
                        $estado      = '<i class="fa fa-refresh status-process icon-estatus " aria-hidden=\"true\"></i><span style="font-size: 10px;">IN PROCESS</span>';
                        $titleEstatus= "Your endorsement is being in process by the brokers.";
                        $class       = "class = \"orange\"";
                        $btn_confirm = "<div class=\"btn_change_status btn-icon edit btn-left\" title=\"Change the status of endorsement\"><i class=\"fa fa-pencil-square-o\"></i></div>";
                        $btn_confirm.= "<div class=\"btn-icon send-email btn-left\" title=\"See the e-mail sent\" onclick=\"fn_endorsement.email.preview('".$items['iConsecutivo']."');\"><i class=\"fa fa-external-link\"></i></div>";
                     break;
                 } 
                 
                
                 $action       = "";
                 $detalle      = "";
                 
                 #REVISAR SI ES MULTIPLE O NO:
                 if($items['iEndosoMultiple'] == "0"){
                     switch($items["eAccion"]){
                         case 'A': $action = 'ADD'; break;
                         case 'D': $action = 'DELETE'; break;
                     }
                     
                     $detalle = "<table style=\"width:100%;padding:0!important;margin:0!important;\">";
                     $detalle.= "<tr style='background: none;'>".
                                "<td style='border: 0;width:120px;padding: 0!important;'>".$action."</td>".
                                "<td style='border: 0;padding: 0!important;'>".strtoupper($items['sNombre'])."</td>".
                                "</tr>";
                     $detalle.= "</table>";
                
                 }
                 else if($items['iEndosoMultiple'] == "1"){
                     #CONSULTAR DETALLE DEL ENDOSO:
                     $query = "SELECT A.sNombre, (CASE 
                                WHEN A.eAccion = 'ADDSWAP'    THEN 'ADD SWAP'
                                WHEN A.eAccion = 'DELETESWAP' THEN 'DELETE SWAP'
                                ELSE A.eAccion
                                END) AS eAccion FROM cb_endoso_operador AS A WHERE A.iConsecutivoEndoso = '".$items['iConsecutivo']."' ORDER BY sNombre ASC";
                     $r     = $conexion->query($query);
                     
                     $detalle     = "<table style=\"width:100%;padding:0!important;margin:0!important;border-collapse: collapse;\">";
                     $description = "";
                         
                     while($item = $r->fetch_assoc()){
                        $description .= "<tr style='background: none;'>".
                        "<td style='border: 0;width:120px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['eAccion']."</td>".
                        "<td style='border: 0;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['sNombre']."</td>".
                        "</tr>"; 
                     }
                     $detalle .= $description."</table>";
                 }
                 
                 //Consultar Estatus x poliza:
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
                        $policies .= "<td style='border: 0;width:30%;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['rImporteEndosoBroker']."</td>";
                        $policies .= "</tr>"; 
                     }
                     $policies.="</table>";
                     }
                 
                  //Redlist:
                 $items['iOnRedList'] == '1' ? $redlist_icon = "<i class=\"fa fa-star\" style=\"color:#e8051b;margin-right:4px;\"></i>" : $redlist_icon = ""; 
                 $htmlTabla .= "<tr $class>".
                               "<td id=\"iCve_".$items['iConsecutivo']."\">".$redlist_icon.$items['sNombreCompania']."</td>".
                               "<td>".$detalle."</td>". 
                               "<td>".$policies."</td>".
                               "<td class=\"text-center\">".$items['dFechaIngreso']."</td>".
                               "<td title='$titleEstatus'>".$estado."</td>".                                                                                                                                                                                                                       
                               "<td> $btn_confirm</td></tr>";   
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
  
      $error   = '0';
      $msj     = "";
      $fields  = "";
      $clave   = trim($_POST['clave']);
      $domroot = $_POST['domroot'];
      
      #Function Begin
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                 
      $sql    = "SELECT A.iConsecutivo, iConsecutivoCompania, sComentarios, DATE_FORMAT(dFechaAplicacion,'%m/%d/%Y') AS dFechaAplicacion,sSolicitanteNombre, sSolicitanteEmail, IF(sSolicitanteFecha != '0000-00-00 00:00:00' AND sSolicitanteFecha != '',DATE_FORMAT(sSolicitanteFecha,'%m/%d/%Y'), '') AS sSolicitanteFecha, ".
                "iConsecutivoOperador, eAccion,iEndosoMultiple, iConsecutivoTipoEndoso   ".  
                "FROM      cb_endoso      AS A ".
                "LEFT JOIN ct_tipo_endoso AS B ON A.iConsecutivoTipoEndoso = B.iConsecutivo ". 
                "WHERE A.iConsecutivo = '$clave'";
      $result = $conexion->query($sql);
      $items  = $result->num_rows; 
      
      if($items > 0){     
            
            $data    = $result->fetch_assoc(); //<---Endorsement Data Array.
            $llaves  = array_keys($data);
            $datos   = $data; 
            
            foreach($datos as $i => $b){ 
                if($i != 'eStatus' && $i != 'sComentarios' && $i != 'iConsecutivoTipoEndoso' && $i != 'iEndosoMultiple' && $i != 'iConsecutivoOperador' && $i != 'eAccion'){
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
                }
                       
            }  
                
            //SI EL OPERADOR EXISTE EN EL CATALOGO SIEMPRE Y CUANDO EL ENDOSO AUN SEA NO MULTIPLE:
            if($data['iEndosoMultiple'] == '0' && $data['iConsecutivoOperador'] != '' && $data['iConsecutivoTipoEndoso']== '2'){
                $sql2   = "SELECT iConsecutivo AS iConsecutivoOperador, sNombre, DATE_FORMAT(dFechaNacimiento,'%m/%d/%Y') AS dFechaNacimiento,iExperienciaYear,eTipoLicencia,iNumLicencia,DATE_FORMAT(dFechaExpiracionLicencia,'%m/%d/%Y') AS dFechaExpiracionLicencia ".    
                          "FROM  ct_operadores A ".
                          "WHERE A.iConsecutivo = '".$data['iConsecutivoOperador']."'";
                $result = $conexion->query($sql2);
                $items2 = $result->num_rows;
                if ($items2 > 0) {
                    
                     $data2    = $result->fetch_assoc();
                     $llaves2  = array_keys($data2);
                     $datos2   = $data2;
                     
                     foreach($datos2 as $i => $b){     
                        $fields .= "\$('#$domroot :input[id=".$i."]').val('".$datos2[$i]."');";
                     }
                     
                     $data['eAccion'] == 'A' ? $data['eAccion'] = 'ADD' : $data['eAccion'] = 'DELETE';
                     $fields .= "\$('#$domroot :input[id=eAccion]').val('".$data['eAccion']."');";
                }
            }
            $eStatus    = $data['eStatus']; 
      }
      $conexion->rollback();
      $conexion->close(); 
      $response = array("msj"=>"$msj","error"=>"$error","fields"=>"$fields","policies"=>"$policies_checkbox","sComentarios" => "$comentarios");   
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
      $valoresD  = array();
      $camposD   = array();
      $edit_mode = trim($_POST['edit_mode']); 
      
      //VARIABLES POST:
      $iConsecutivo         = trim($_POST['iConsecutivo']);
      $iConsecutivoCompania = trim($_POST['iConsecutivoCompania']); 
      $sComentarios         = $_POST['sComentarios'] != "" ? "'".utf8_decode(trim($_POST['sComentarios']))."'" : "''";
      $sIP                  = $_SERVER['REMOTE_ADDR'];
      $sUsuario             = $_SESSION['usuario_actual'];
      $dFecha               = date("Y-m-d H:i:s");
      $valores              = array();
      $campos               = array();
      
      //GUARDAMOS EL ENDOSO
      if($edit_mode == 'true'){
          
            foreach($_POST as $campo => $valor){
                if($campo != "accion" && $campo != "edit_mode" && $campo != "iConsecutivo" && strpos($campo,"chk_policies_") === false && $campo != "dFechaAplicacionHora"){ // Estos campos no se insertan a la tabla
                    if(($campo == 'dFechaAplicacion' || $campo == "sSolicitanteFecha") && $valor != ""){$valor = date('Y-m-d',strtotime(trim($valor)));}else
                    if($campo == 'sComentarios' && $valor != ""){$valor = utf8_encode($valor);}
                    array_push($valores,"$campo='".$valor."'");
                }
            }
          
            //Campos adicionales:
            array_push($valores,"sUsuarioActualizacion='$sUsuario'");
            array_push($valores,"sIP='$sIP'");
            array_push($valores,"dFechaActualizacion='$dFecha'");
            array_push($valores,"iEndosoMultiple='1'");
                
            $query   = "UPDATE cb_endoso SET ".implode(",",$valores)." WHERE iConsecutivo='$iConsecutivo' AND iConsecutivoCompania='$iConsecutivoCompania'";
            $mensaje = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The data has been updated successfully!</p>';
          
      }
      else{
          foreach($_POST as $campo => $valor){ 
            if($campo != "accion" && $campo != "edit_mode" && $campo != "iConsecutivo" && strpos($campo,"chk_policies_") === false && $campo != "dFechaAplicacionHora"){ // Estos campos no se insertan a la tabla
                if(($campo == 'dFechaAplicacion' || $campo == "sSolicitanteFecha") && $valor != ""){$valor = date('Y-m-d',strtotime(trim($valor)));}else
                if($campo == 'sComentarios' && $valor != ""){$valor = utf8_encode($valor);}
                array_push($campos, $campo);
                array_push($valores,date_to_server($valor));
            }
          }
         
          // Campos Adicionales:
          array_push($campos,"sUsuarioIngreso");        array_push($valores,$sUsuario);
          array_push($campos,"sIP");                    array_push($valores,$sIP);
          array_push($campos,"eStatus");                array_push($valores,'S');
          array_push($campos,"iConsecutivoTipoEndoso"); array_push($valores,'2');
          array_push($campos,"iEndosoMultiple");        array_push($valores,'1');
          
          $query   = "INSERT INTO cb_endoso (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')";
          $mensaje = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The data has been saved successfully!</p>';
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

      $success && $error == '0' ? $conexion->commit() : $conexion->rollback();
      $conexion->close();
      $response = array("error"=>"$error","msj"=>"$mensaje","iConsecutivo"=>"$iConsecutivo");
      echo json_encode($response);
  }
  function get_drivers_autocomplete(){
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);
      $company = trim($_POST['iConsecutivoCompania']);
      $error   = "0";
      $mensaje = "";
      $sql     = "SELECT iConsecutivo, sNombre,eTipoLicencia, iNumLicencia, DATE_FORMAT(dFechaNacimiento,  '%m/%d/%Y' ) AS dFechaNacimiento, DATE_FORMAT(dFechaExpiracionLicencia,  '%m/%d/%Y' ) AS dFechaExpiracionLicencia,  ".
                 "iExperienciaYear ". 
                 "FROM ct_operadores WHERE iConsecutivoCompania = '$company' ".
                 "ORDER BY sNombre ASC";
      $result  = $conexion->query($sql);
      $rows    = $result->num_rows;  
      if($rows > 0){
             
        while ($items = $result->fetch_assoc()) {
           $cadena     = '"'.$items["sNombre"].' | '.$items["iNumLicencia"].'|'.$items['eTipoLicencia'].'|'.utf8_encode($items["dFechaNacimiento"]).'|'.$items["dFechaExpiracionLicencia"].'|'.$items["iExperienciaYear"].'|'.$items["iConsecutivo"].'"';
           $respuesta == '' ? $respuesta .= $cadena : $respuesta .= ','.$cadena;    
        }                                                                                                                                                                        
      }else {$respuesta .="";}
      $conexion->rollback();
      $conexion->close();
       
      $respuesta = "[".$respuesta."]";
      echo $respuesta;    
  }
  
  #DETALLE 
  function detalle_save(){
      include("cn_usuarios.php");  
      $conexion->autocommit(FALSE); 
      
      #VARIABLES:
      $success              = true;
      $edit_mode            = trim($_POST['edit_mode']);
      $iConsecutivoOperador = trim($_POST['iConsecutivoOperador']);
      $iConsecutivoCompania = trim($_POST['iConsecutivoCompania']);
      $iConsecutivoEndoso   = trim($_POST['iConsecutivoEndoso']);
      $campoCatalogo        = array();
      $valorCatalogo        = array();
      $campoDetalle         = array();
      $valorDetalle         = array();
      $edit_detalle         = false;
      $error                = "0";
      $sIP                  = $_SERVER['REMOTE_ADDR'];
      $sUsuario             = $_SESSION['usuario_actual'];
      $dFecha               = date("Y-m-d H:i:s");
      
      //REVISAMOS DATOS DEL OPERADOR:
      if($iConsecutivoOperador == ""){
         //Verificamos si la unidad ya existe: 
         $query  = "SELECT iConsecutivo FROM ct_operadores WHERE iNumLicencia='".trim($_POST['iNumLicencia'])."' AND iConsecutivoCompania='$iConsecutivoCompania'";
         $result = $conexion->query($query);
         $items  = $result->fetch_assoc();
         //Tomamos el consecutivo de la unidad ya existente:
         $iConsecutivoOperador           = trim($items['iConsecutivo']);
         $_POST['$iConsecutivoOperador'] = $iConsecutivoOperador; 
      }
  
      if($iConsecutivoOperador!= ""){
         
          //Verificamos si se va a editar en el DETALLE:
          $query = "SELECT COUNT(iConsecutivoOperador) AS total FROM cb_endoso_operador ".
                   "WHERE iConsecutivoOperador='$iConsecutivoOperador' AND iConsecutivoEndoso='$iConsecutivoEndoso'";
          $result= $conexion->query($query);
          $valid = $result->fetch_assoc();
          $valid['total'] > 0 ? $edit_detalle = true : $edit_detalle = false;
          
          foreach($_POST as $campo => $valor){
            if($campo != "accion" && $campo != "edit_mode"){ //Estos campos no se insertan a la tabla
                #CAMPOS QUE SE GUARDAN A NIVEL CATALOGO
                if($campo != "iConsecutivoOperador" && $campo != "iConsecutivoCompania" && $campo != "iNumLicencia" && $campo != "eAccion" && $campo != "iConsecutivoEndoso" && $campo != "sNombre"){
                    
                    //Campos para fechas:
                    if($campo == 'dFechaNacimiento' || $campo == 'dFechaExpiracionLicencia'){$valor = date('Y-m-d',strtotime(trim($valor)));}
                    else{$valor = strtoupper(trim($valor));}
                    
                    array_push($valorCatalogo,"$campo='".$valor."'"); 
                }
                #CAMPOS QUE DE GUARDAN A NIVEL DETALLE ENDOSO:
                if($campo == "sNombre" || $campo == "iConsecutivoEndoso" || $campo == "iNumLicencia" || $campo == "eAccion" || $campo == "iConsecutivoOperador"){
                   if($valid['total'] == 0){
                        if($valor != ""){
                            array_push($campoDetalle ,$campo);
                            array_push($valorDetalle, strtoupper(trim($valor)));    
                        }
                   }
                   else{
                       if($valor != ""){
                            array_push($valorDetalle,"$campo='". strtoupper(trim($valor))."'");
                       }
                   } 
                }
            }
          }
          
          #EDITAR LA UNIDAD:
          array_push($valorCatalogo,"sUsuarioActualizacion='".$sUsuario."'");
          array_push($valorCatalogo,"sIP='".$sIP."'");
          array_push($valorCatalogo ,"dFechaActualizacion='".$dFecha."'");
         
          $query   = "UPDATE ct_operadores SET ".implode(",",$valorCatalogo)." WHERE iConsecutivo='$iConsecutivoOperador' AND iConsecutivoCompania = '$iConsecutivoCompania'"; 
          $success = $conexion->query($query);
          if(!($success)){$mensaje = "Error: The data of driver has not been saved successfully, please try again.";$error = "1";} 
                  
      }
      else{
         
         //GUARDAR DATOS A NIVEL DETALLE ENDOSO:
         foreach($_POST as $campo => $valor){
            if($campo != "accion" && $campo != "edit_mode"){
                
                #CAMPOS QUE SE GUARDAN PARA UNA UNIDAD NUEVA
                if($campo != "iConsecutivoOperador" && $campo != "eAccion" && $campo != "iConsecutivoEndoso"){
                   
                   //Campos para fechas:
                   if($campo == 'dFechaNacimiento' || $campo == 'dFechaExpiracionLicencia'){$valor = date('Y-m-d',strtotime(trim($valor)));}
                   else{$valor = strtoupper(trim($valor));}
                     
                   if($valor != ""){
                       array_push($campoCatalogo ,$campo);
                       array_push($valorCatalogo, strtoupper(trim($valor)));
                   }
                }
                
                #CAMPOS QUE DE GUARDAN A NIVEL DETALLE ENDOSO:
                if($campo == "sNombre" || $campo == "iConsecutivoEndoso" || $campo == "iNumLicencia" || $campo == "eAccion" || $campo == "iConsecutivoOperador"){
                    if($valor != ""){
                        array_push($campoDetalle ,$campo);
                        array_push($valorDetalle, strtoupper(trim($valor)));
                    }    
                }
            }
         } 
         
         //Se inicializan campos adicionales de control
         array_push($campoCatalogo ,"sUsuarioIngreso"); array_push($valorCatalogo,$sUsuario);
         array_push($campoCatalogo ,"sIP"); array_push($valorCatalogo,$sIP); 
         array_push($campoCatalogo ,"eModoIngreso"); array_push($valorCatalogo,"ENDORSEMENT"); 
         
         $query   = "INSERT INTO ct_operadores (".implode(",",$campoCatalogo).") VALUES ('".implode("','",$valorCatalogo)."')";
         $success = $conexion->query($query);
         if($success){
             $iConsecutivoOperador = $conexion->insert_id;
             array_push($campoDetalle ,"iConsecutivoOperador");
             array_push($valorDetalle, $iConsecutivoOperador);
         }
         else{$mensaje = "Error: The data of driver has not been saved successfully, please try again.";$error = "1";}
      
      }
      
      if($success){
         if($edit_detalle){
            $query   = "UPDATE cb_endoso_operador SET ".implode(",",$valorDetalle)." WHERE iConsecutivoOperador='$iConsecutivoOperador' AND iConsecutivoEndoso='$iConsecutivoEndoso'"; 
            $mensaje = "The data was updated successfully.";
         }
         else{
            $query   = "INSERT INTO cb_endoso_operador (".implode(",",$campoDetalle).") VALUES ('".implode("','",$valorDetalle)."')";
            $mensaje = "The data was saved successfully.";
         }
         $success = $conexion->query($query);
         if(!($success)){$mensaje = "Error: The data of driver/endorsement has not been saved successfully, please try again.";$error = "1";}
         
      }
      
      $success && $error == '0' ? $conexion->commit() : $conexion->rollback();
      $conexion->close();
      $response = array("error"=>"$error","msj"=>"$mensaje");
      echo json_encode($response);
  }
  function detalle_datagrid(){
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $iConsecutivo        = trim($_POST['iConsecutivoEndoso']);
      
      if($iConsecutivo != ""){
          
           //Filtros de informacion //
           $filtroQuery = " WHERE iConsecutivoEndoso = '".$iConsecutivo."' AND B.iDeleted='0'";
            
          #CONTAR REGISTROS                                                                                                              
          $sql    = "SELECT COUNT(iConsecutivoEndoso) AS total ".
                    "FROM cb_endoso_operador AS A ".
                    "LEFT JOIN ct_operadores AS B ON A.iConsecutivoOperador = B.iConsecutivo ".$filtroQuery;
          $result = $conexion->query($sql);
          $rows   = $result->fetch_assoc();
          
          if($rows['total'] > 0){
             
            // ordenamiento//
            $ordenQuery = " ORDER BY sNombre ASC";
            
            #CONSULTA:
            $sql    = "SELECT iConsecutivoOperador, B.iNumLicencia, A.sNombre, A.eAccion, DATE_FORMAT(B.dFechaNacimiento,'%m/%d/%Y') AS dFechaNacimiento, DATE_FORMAT(B.dFechaExpiracionLicencia,'%m/%d/%Y') AS dFechaExpiracionLicencia, B.iExperienciaYear, B.eTipoLicencia  ".
                      "FROM      cb_endoso_operador AS A ".
                      "LEFT JOIN ct_operadores      AS B ON A.iConsecutivoOperador = B.iConsecutivo ".$filtroQuery.$ordenQuery;
            $result = $conexion->query($sql);
            
            while ($items = $result->fetch_assoc()) { 
                
                 if($items['eAccion'] == "ADDSWAP"){$action = "ADD SWAP";}else
                 if($items['eAccion'] == "DELETESWAP"){$action = "DELETE SWAP";}
                 else{$action = $items['eAccion'];}
               
                 $htmlTabla .= "<tr>".
                               "<td id=\"idDet_".$items['iConsecutivoOperador']."\">".$action."</td>".
                               "<td>".$items['sNombre']."</td>".
                               "<td class=\"txt-c\">".$items['dFechaNacimiento']."</td>".
                               "<td>".$items['iNumLicencia']."</td>". 
                               "<td class=\"txt-c\">".$items['eTipoLicencia']."</td>".
                               "<td class=\"txt-c\">".$items['dFechaExpiracionLicencia']."</td>".
                               "<td class=\"txt-c\">".$items['iExperienciaYear']."</td>".
                               "<td>".
                                    "<div class=\"btn_edit_detalle btn-icon edit btn-left\" title=\"Edit data\"><i class=\"fa fa-pencil-square-o\"></i></div>".
                                    "<div class=\"btn_delete_detalle btn-icon trash btn-left\" title=\"Delete data\"><i class=\"fa fa-trash\"></i><span></span></div>".
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
  function detalle_get(){
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
            
          foreach($datos as $i => $b){ 
            $fields .= "\$('$domroot [name=".$i."]').val('".$datos[$i]."');";   
          }  
            
      }else{$error = "1"; $msj = "Error to data query, please try again later.";}
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
      $clave    = trim($_POST['iConsecutivoOperador']);
      $idEndoso = trim($_POST['iConsecutivoEndoso']);
      
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE); 
      
      //CONSULTAMOS, SI EL OPERADOR NO ESTA ACTUALMENTE EN NINGUNA POLIZA, LA MARCAREMOS COMO ELIMINADA EN EL CATALOGO:
      $query = "SELECT COUNT(A.iConsecutivo) AS total ".
               "FROM ct_operadores AS A INNER JOIN cb_poliza_operador AS B ON A.iConsecutivo = B.iConsecutivoOperador ".
               "WHERE A.iConsecutivo = '$clave'";
      $result= $conexion->query($query);
      $valid = $result->fetch_assoc();
      $valid['total'] > 0 ? $iElimina = false : $iElimina = true;
      
      $query   = "DELETE FROM cb_endoso_operador WHERE iConsecutivoOperador='$clave' AND iConsecutivoEndoso='$idEndoso'";
      $success = $conexion->query($query);
      
      if(!($success)){$error = '1'; $mensaje = "Error to try delete data, please try again later.";}
      else{
         if($iElimina){
            $query   = "UPDATE ct_operadores SET iDeleted = '1' WHERE iConsecutivo='$clave'";
            $success = $conexion->query($query); 
            if(!($success)){$error = '1'; $mensaje = "Error to try update data, please try again later.";}
         }
         if($error == "0"){$mensaje = "The data has been deleted successfully!";} 
      }
      
      $error == "0" ? $conexion->commit() : $conexion->rollback();
      $conexion->close();
      
      $response = array("msj"=>"$mensaje","error"=>"$error",);   
      echo json_encode($response); 
  }
  
  /*------FUNCIONES GENERALES DEL MODULO DE SOLICITUD DE ENDOSOS -----------------------*/
  /*function update_endorsement_status(){
      #paremeters
      $iConsecutivo = trim($_POST['iConsecutivo']);
      $idPoliza = trim($_POST['idPoliza']);
      $eStatus = trim($_POST['eStatus']);
      $_POST['sComentarios'] != '' ? $sComentarios = utf8_encode(trim($_POST['sComentarios'])) : $sComentarios = '';
      #variables
      $error = '0';  
      $msj = "";
      
      //Conexion:
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true; 
      
      if($iConsecutivo != ''){  
        $sql = "UPDATE cb_endoso_estatus SET eStatus='$eStatus', dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."',sUsuarioActualizacion='".$_SESSION['usuario_actual']."'".
               "WHERE iConsecutivoEndoso = '$iConsecutivo' AND iConsecutivoPoliza = '$idPoliza'";     
        if($conexion->query($sql)){
                 $eStatusGeneral = "";  
                 $query = "SELECT eStatus FROM cb_endoso_estatus WHERE iConsecutivoEndoso = '$iConsecutivo' GROUP BY eStatus ORDER BY eStatus DESC";
                 $result = $conexion->query($query);
                 $rows = $result->num_rows;
                  if($rows > 0){
                      $cadena = array();
                      while ($items = $result->fetch_assoc()) {array_push($cadena, trim($items['eStatus']));}
                      //Revisando cadena:
                           if(in_array('D',$cadena)) {$eStatusGeneral = 'D';}
                      else if(in_array('P',$cadena)) {$eStatusGeneral = 'P';}
                      else if(in_array('SB',$cadena)){$eStatusGeneral = 'SB';}
                      else if(in_array('A',$cadena)) {$eStatusGeneral = 'A';}
                  }
                 
                 if($eStatusGeneral != ''){
                     $sql = "UPDATE cb_endoso SET eStatus ='$eStatusGeneral', sComentarios='$sComentarios', dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."',sUsuarioActualizacion='".$_SESSION['usuario_actual']."'".
                            "WHERE iConsecutivo = '$iConsecutivo'";
                     if(!($conexion->query($sql))){
                        $error = '1';                                         
                        $msj = "Error: The Endorsement data was not found, please try again.";
                     }else{
                      //Revisamos si el Estatus es "APPROVED"  para actualizar la unidad o driver y agregarlo a las polizas.
                      if($eStatusGeneral == 'A'){
                         #1. saber que tipo de endoso es: 
                         $query_endoso = "SELECT iConsecutivoTipoEndoso, sNumPolizas, iConsecutivoCompania, iConsecutivoOperador, iConsecutivoUnidad, eAccion ".
                                         "FROM cb_endoso WHERE iConsecutivo = '$iConsecutivo'";
                         $result = $conexion->query($query_endoso);
                         $items = $result->num_rows; 
                         
                         if($items > 0){     
                            $data = $result->fetch_assoc();
                            #2. Revisamos la accion del endoso y sea una unit o un driver:
                            $endoAccion = trim($data['eAccion']);
                            $endoTipo   = trim($data['iConsecutivoTipoEndoso']);
                            $CompaniaID = trim($data['iConsecutivoCompania']);
                            $ct_actualiza = "";
                            $Consecutivo_actualiza = "";
                                 if($endoTipo == '1' && $data['iConsecutivoUnidad'] != '') {$ct_actualiza = "ct_unidades"; $Consecutivo_actualiza = trim($data['iConsecutivoUnidad']);}
                            else if($endoTipo == '2' && $data['iConsecutivoOperador'] != ''){$ct_actualiza = "ct_operadores";$Consecutivo_actualiza = trim($data['iConsecutivoOperador']);}
                            
                            //policies of endorsement:
                            $PolizasEndoso = explode('|',$data['sNumPolizas']);
                            #1 - Obtener las polizas que se actualizaron. 
                            $id_polizas = ""; 
                            $count = count($PolizasEndoso); 
                            for ($i = 0; $i < $count; $i++) {
                                 $poliza = explode('/',$PolizasEndoso[$i]);
                                 $policy_query = "SELECT iConsecutivo FROM ct_polizas ".
                                                 "WHERE sNumeroPoliza = '".$poliza[0]."' AND iTipoPoliza = '".$poliza[1]."' ".
                                                 "AND iConsecutivoCompania = '$CompaniaID' AND iDeleted = '0'";
                                 $result = $conexion->query($policy_query);
                                 $items  = $result->num_rows; 
                                 if($items > 0 ){
                                    $iConsecutivoPoliza = $result->fetch_assoc();  
                                    $id_polizas == '' ?  $id_polizas = $iConsecutivoPoliza['iConsecutivo'] : $id_polizas .= ','.$iConsecutivoPoliza['iConsecutivo']; 
                                 }else{$error = '1';$mensaje = "Error: Policy data not found.";}
                            }
                            
                            #Revisamos si es un ADD o un DELETE:
                            if($endoAccion == 'A'){
                                #2. Actualizamos la tabla correspondiente:
                                $polizas_actualiza = $id_polizas;
                                $inpolizas = "1";
                                   
                            }else if($endoAccion == 'D'){

                                #2 - En caso que sea Delete, consultamos la tabla de la unidad o driver para saber a que polizas pertenece actualmente:
                                $query = "SELECT siConsecutivosPolizas FROM $ct_actualiza WHERE iConsecutivo = '$Consecutivo_actualiza' AND iConsecutivoCompania = '$CompaniaID'";
                                $result = $conexion->query($query);
                                $items = $result->num_rows; 
                                $items > 0 ? $polizas_actuales = $result->fetch_assoc() : $polizas_actuales = "";
                                
                                if($polizas_actuales != ''){
                                    $polizas_actuales  = explode(',',$polizas_actuales);
                                    $polizas_endoso    = explode(',',$id_polizas); 
                                    $array_nuevo       = array_diff($polizas_endoso, $polizas_endoso);
                                    $polizas_actualiza = implode(',',$array_nuevo);
                                    $polizas_actualiza != "" ? $inpolizas = "1" : $inpolizas = "0";
                                }else{
                                    $polizas_actualiza = "";
                                    $inpolizas = "0";
                                }
   
                            }
                         
                            $update_list = "UPDATE $ct_actualiza SET inPoliza = '$inpolizas', siConsecutivosPolizas = '$polizas_actualiza' ".
                                           "WHERE iConsecutivo = '$Consecutivo_actualiza' AND iConsecutivoCompania = '$CompaniaID'";
                                       
                            if(!($conexion->query($update_list))){
                                    $error = '1';
                                    $transaccion_exitosa = false;
                                    $msj = "Error: The Unit or driver data was not found, please try again.";
                                    
                            }else{$msj = "The data has been update successfully.";}

                         }else{
                             $error = '1';
                             $mensaje = "Error: Policy ids not found.";
                         }
                           
                      }else{
                         $msj = "The data has been update successfully."; 
                      }
                      
                     }
                         
                 }else{
                     $msj = "The data has been update successfully.";
                 }
                
          }else{
              $error = '1';
              $msj = "Error: The Endorsement data was not found, please try again.";
          }
      }
      if($error == '0'){
         $conexion->commit();
         $conexion->close();
         //$msj = "The data has been update successfully.";  
      }else{
         $conexion->rollback();
         $conexion->close(); 
      }
      
      $response = array("error"=>"$error","msj"=>"$msj");
      echo json_encode($response);
  } 
  function evalua_estatus_general($idEndoso){
      include("cn_usuarios.php");
      $query = "SELECT eStatus FROM cb_endoso_estatus WHERE iConsecutivoEndoso = '$idEndoso' GROUP BY eStatus ORDER BY eStatus DESC";
      $result = $conexion->query($query);
      $rows = $result->num_rows;
      
      if($rows > 0){
          $cadena = array();
          while ($items = $result->fetch_assoc()) {                                           
             
             array_push($cadena, trim($items['eStatus']));
          }
          
          //Revisando cadena:
          if(in_array('D',$cadena)){
              $Status = 'D';  
          }else if(in_array('P',$cadena)){
              $Status = 'P';
          }else if(in_array('SB',$cadena)){
                $Status = 'SB';
          }else if(in_array('A',$cadena)){
                $Status = 'A';
          }
      }
 
      return $Status;
      
  }
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
      $endorsement_query = "SELECT A.iConsecutivo, A.iConsecutivoCompania, sNombreCompania,iConsecutivoTipoEndoso,iConsecutivoOperador, eAccion, sNumeroPoliza, T.sDescripcion AS Tipo, sEmail AS Broker, BR.sName AS NameBroker
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
      
      
      if($endorsement['iConsecutivo'] != "" && $endorsement['iConsecutivoTipoEndoso'] == '2' && $endorsement['iConsecutivoOperador'] != ''){    

             #Driver: Consult the driver information:
             $driver_query = "SELECT iConsecutivo, sNombre,DATE_FORMAT(dFechaNacimiento,'%m/%d/%Y') AS dFechaNacimiento, DATE_FORMAT(dFechaExpiracionLicencia,'%m/%d/%Y') AS dFechaExpiracionLicencia,iExperienciaYear,
                              iNumLicencia,dFechaContratacion,eTipoLicencia FROM ct_operadores 
                              WHERE iConsecutivo = '".$endorsement['iConsecutivoOperador']."'";
             $result_d = $conexion->query($driver_query);
             $rows_d = $result_d->num_rows; 
             $rows_d > 0 ? $driver = $result_d->fetch_assoc() : $driver = "";
      
             $subject_policy = $endorsement['sNumeroPoliza'].'-'.$endorsement['Tipo'];        
                       
             #action to define the subject:
             switch($endorsement["eAccion"]){ 
                    case 'A': 
                        $action = 'Please add to my policy the following driver.';
                        $subject = "Endorsement application - please add the following driver from policy number: ".$endorsement['sNombreCompania'].", ".$subject_policy; 
                        break; 
                    case 'D': 
                        $action = 'Please delete from my policy the following driver.';                                                                   
                        $subject = "Endorsement application - please delete the following driver from policy number: ".$endorsement['sNombreCompania'].", ".$subject_policy; 
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
                                      "<h2 style=\"color:#313131;text-transform: uppercase; text-align:center;\">Endorsement application from Solo-Trucking Insurance</h2> \n".
                                      "</td></tr>". 
                                      "<tr><td>".
                                      "<p style=\"color:#000;margin:5px auto; text-align:left;\">$action</p><br><br>".
                                      "</td></tr>".
                                      "<tr><td>".
                                      "<ul style=\"color:#010101;line-height:15px;list-style:none;\"> ".
                                      "<li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">NAME: </strong>".$driver['sNombre']."</li>".
                                      "<li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">DOB: </strong>".$driver['dFechaNacimiento']."</li>".
                                      "<li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">YOE: </strong>".$driver['iExperienciaYear']."</li>".
                                      "<li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">LICENSE EXP: </strong>".$driver['dFechaExpiracionLicencia']."</li>".
                                      "<li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">LICENSE NUMBER: </strong>".$driver['iNumLicencia']."</li>".
                                      "</ul>".
                                      "</td></tr>".
                                      "<tr><td><p style=\"color:#010101;margin:5px auto 10px; text-align:left;font-size:11px;\">Please reply this email to the account:<a href=\"mailto:customerservice@solo-trucking.com\"> customerservice@solo-trucking.com</a></p></td></tr>".
                                      "<tr><td><p style=\"color:#858585;margin:5px auto; text-align:left;font-size:10px;\">e-mail sent from Solo-trucking Insurance System.</p></td></tr>".
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
                        if($endorsement['eAccion'] == 'A'){
                            include("./lib/fpdf153/fpdf.php"); // libreria fpdf 
                            //Consult the driver files:
                            $driver_files_query = "SELECT iConsecutivo, sNombreArchivo, eArchivo, hContenidoDocumentoDigitalizado, sTipoArchivo, iTamanioArchivo  
                                                   FROM cb_operador_files 
                                                   WHERE  iConsecutivoOperador = '".$endorsement['iConsecutivoOperador']."'";
                            
                           $result_files = $conexion->query($driver_files_query);
                           $rows_files = $result_files->num_rows; 
                           if($rows_files > 0){
                                while ($files = $result_files->fetch_assoc()){
                                   #Here will constructed the temporary files: 
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
      
  } */  
  
  #funciones genericas:
  function CalculaEdad($fecha){
    list($m,$d,$Y) = explode("/",$fecha);
    return( date("md") < $m.$d ? date("Y")-$Y-1 : date("Y")-$Y );
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
      $sql    = "SELECT A.iConsecutivo, A.iConsecutivoCompania, A.iConsecutivoTipoEndoso, sDescripcion, iReeferYear,iTrailerExchange, ". 
                "A.sComentarios, iPDApply, iConsecutivoOperador,sNombreOperador, eAccion ".  
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
        if($endoso['eAccion'] == 'A' && $endoso['iConsecutivoOperador'] != ""){
          $query_union = " UNION ".
                         "(SELECT iConsecutivo, sTipoArchivo, sNombreArchivo, iTamanioArchivo,eArchivo FROM cb_operador_files WHERE iConsecutivoOperador = '".$endoso['iConsecutivoOperador']."' $ordenQuery)";    
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
          
          $sql    = "(SELECT iConsecutivo, sTipoArchivo,sNombreArchivo,iTamanioArchivo,eArchivo FROM cb_endoso_files ".$filtroQuery.$ordenQuery.")".$query_union;
          $result = $conexion->query($sql);
          $rows   = $result->num_rows; 
             
            if ($rows > 0) {    
                while ($items = $result->fetch_assoc()) { 
                   if($items["iConsecutivo"] != ""){

                         $htmlTabla .= "<tr>".
                                       "<td id=\"idFile_".$items['iConsecutivo']."\">".$items['sNombreArchivo']."</td>".
                                       "<td>".$items['eArchivo']."</td>".
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
  
  #FUNCIONES EMAILS Y STATUS:
  function get_endorsement_estatus(){
      
      include("cn_usuarios.php");     
      $error       = "0";
      $clave       = trim($_POST['iConsecutivo']);
      $domroot     = trim($_POST['domroot']);
      
      #CONSULTAR DATOS DEL ENDOSO Y COMPANIA
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
  function get_estatus_info(){
      include("cn_usuarios.php");     
      $error       = "0";
      $clave       = trim($_POST['iConsecutivoEndoso']);
      $domroot     = trim($_POST['domroot']);
      $detalle     = "";
      
      #CONSULTAR DATOS DEL ENDOSO Y COMPANIA
      $sql    = "SELECT iConsecutivoEndoso,iConsecutivoPoliza,A.eStatus, B.sNumeroPoliza,B.iTipoPoliza,D.sDescripcion AS sTipoPoliza,C.iConsecutivo AS iConsecutivoBroker,C.sName AS sBrokerName,".
                "C.bEndosoMensual,A.sComentarios,A.sNumeroEndosoBroker,A.rImporteEndosoBroker,DATE_FORMAT(A.dFechaActualizacion,'%m/%d/%Y %H:%i') AS dFechaActualizacion, ".
                "DATE_FORMAT(A.dFechaAplicacion,'%m/%d/%Y %H:%i') AS dFechaAplicacion ".
                "FROM cb_endoso_estatus AS A ".
                "LEFT JOIN ct_polizas AS B ON A.iConsecutivoPoliza = B.iConsecutivo ".
                "LEFT JOIN ct_tipo_poliza AS D ON B.iTipoPoliza = D.iConsecutivo ".
                "LEFT JOIN ct_brokers AS C ON B.iConsecutivoBrokers = C.iConsecutivo ".
                "WHERE iConsecutivoEndoso = '$clave' ";
      $result = $conexion->query($sql); 
      $rows   = $result->num_rows; 
      
      if($rows > 0){ 
          while ($data = $result->fetch_assoc()){
              
              $tipoPoliza = get_policy_type($data['iTipoPoliza']);
              $sNumPoliza = $data['sNumeroPoliza'];
              $sDescPoliza= $data['sTipoPoliza']; 
              $sBroker    = $data['sBrokerName'];
              
              //encabezado2:
              $encabezado2 = "style=\"color: #fff;text-align: center;font-weight:bold;text-transform:uppercase;\"";     
                   
              #HTML TABLA:
              $htmlTabla .= "<tr class=\"grid-head1\"><td $encabezado2 colspan=\"100%\">ENDORSEMENT TO $sBroker</td></tr>";
              
              //Revisamos si el endoso aplica para envio mensual... (no debe aparecer aqui.)
              $endosoFields = "";
              
              $label  = "style=\"display: block;float: left;width: 18%;margin: 2px 0px;padding:5px 0px;\"";
              $input  = "style=\"float: right;width: 99%;clear: none;margin: 2px!important;height: 20px!important;resize: none;\"";
              $textar = "style=\"float: right;width: 99%;clear: none;margin: 2px!important;height:22px!important;resize: none;padding-top: 0px!important;\"";
              $select = "style=\"float: right;width: 100%!important;clear: none;margin: 2px!important;height:25px!important;\"";
              $div    = "style=\"clear:both;\"";
              
              $endosoFields .= "<table style=\"width:100%;\">";
              $endosoFields .= "<tr>";
              
              //COLUMNA 1
              $endosoFields .= "<td style=\"vertical-align:top;\">";
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
              $endosoFields .= "</td>";
              $endosoFields .= "</tr>";
              $endosoFields .= "</table>"; 
              
              $html .= '<h3>'.$sNumPoliza.' / '.$sDescPoliza.' / '.$sBroker.'</h3>';
              $html .= '<div id="dataPolicy_'.$data['iConsecutivoPoliza'].'" class="data_policy" style="height:150px!important;">'.$endosoFields.'</div>'; 
             
              /*$fechaActualizacion = "<span>Last updated: ".$data['dFechaActualizacion']."</span>";
              $label  = "style=\"display: block;float: left;width: 18%;margin: 2px 0px;padding:5px 0px;\"";
              $input  = "style=\"float: right;width: 80%;clear: none;margin: 2px!important;height: 20px!important;resize: none;\"";
              $textar = "style=\"float: right;width: 80%;clear: none;margin: 2px!important;height:43px!important;resize: none;padding-top: 0px!important;\"";
              $select = "style=\"float: right;width: 81%!important;clear: none;margin: 2px!important;height:25px!important;\"";
              $div    = "style=\"clear:both;\""; 
                   
              $endosoFields .= "<td style=\"width: 50%;border:0px!important;\" id=\"dataPolicy_".$data['iConsecutivoPoliza']."\" class=\"data_policy\">"; 
              //Claim Data:
              $endosoFields .= "<div $div>".
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
                               "</div>";
              $endosoFields .= "</td>";
                    
             
              
              $htmlTabla .= "<tr>".
                            "<td colspan=\"100%\">".
                            "<table style=\"width:100%\">".
                            "<tr>".
                            "<td style=\"width: 50%;border:0px!important;vertical-align:top;\">".
                                "<h4 style=\"margin: 12px 0px 5px;font-size: 12px;padding:0;text-transform: uppercase;\">Policy Data</h4>".
                                "<label style=\"display: inline-block;width: 30%;\">No:</label><span style=\"$style\">$sNumPoliza</span><br>".
                                "<label style=\"display: inline-block;width: 30%;\">Type:</label><span style=\"$style\">$sDescPoliza</span><br>".
                                "<label style=\"display: inline-block;width: 30%;\">Broker:</label><span style=\"$style\">$sBroker</span>".
                            "</td>".
                            "$endosoFields".  
                            "</tr>".
                            "</table>".
                            "</td>".
                            "</tr>"; 
                                 
              //Salto de linea:              
              $htmlTabla .= "<tr><td colspan=\"100%\" style=\"height: 10px;text-align:center;font-size:11px;\"></td></tr>"; */                         

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
          $query  = "SELECT iConsecutivo AS iConsecutivoEndoso, eStatus, sComentarios, iEndosoMultiple FROM cb_endoso WHERE iConsecutivo = '$clave'";
          $result = $conexion->query($query);
          $rows   = $result->num_rows;
          if($rows > 0){
            $data    = $result->fetch_assoc();
            $fields .= "\$('#$domroot :input[name=sComentariosEndoso]').val('".utf8_decode($data['sComentarios'])."');\n"; 
            $fields .= "\$('#$domroot :input[name=iConsecutivoEndoso]').val('".$data['iConsecutivoEndoso']."');\n";  
            $fields .= "\$('#$domroot :input[name=eStatusEndoso]').val('".utf8_decode($data['eStatus'])."');\n"; 
            
            if($data['iEndosoMultiple'] == 0){
                #CONSULTAR DESCRIPCION DEL ENDOSO PARA ENDOSOS NO MULTIPLES (PROCESO VIEJO)
                $query  = "SELECT A.iConsecutivoOperador, B.sNombre, DATE_FORMAT(B.dFechaNacimiento,'%m/%d/%Y') AS dFechaNacimiento, B.eTipoLicencia, B.iNumLicencia, DATE_FORMAT(B.dFechaExpiracionLicencia,'%m/%d/%Y') AS dFechaExpiracionLicencia, iExperienciaYear, A.eAccion ".
                          "FROM      cb_endoso AS A ".
                          "LEFT JOIN ct_operadores AS B ON A.iConsecutivoOperador = B.iConsecutivo ".
                          "WHERE A.iConsecutivo='$clave' ";
                $result = $conexion->query($query) or die($conexion->error);
                $rows   = $result->num_rows;
                
                if($rows > 0){
                    #DECLARAR ARRAY DE DETALLE:
                    $Detalle = $result->fetch_assoc();  

                    if($Detalle['eAccion'] == "A"){$Detalle['eAccion'] = "ADD";}
                    if($Detalle['eAccion'] == "D"){$Detalle['eAccion'] = "DELETE";}
                    
                    $Acti   = $Detalle['eAccion'];
                    $nombre = $Detalle['sNombre'];
                    $dob    = $Detalle['dFechaNacimiento'];
                    $license= $Detalle['iNumLicencia'];
                    $tipoLi = $Detalle['eTipoLicencia'];
                    $expLi  = $Detalle['dFechaExpiracionLicencia'];
                    $expYear= $Detalle['iExperienciaYear'];
                    
                    $detalle  = "<tr>";
                    $detalle .= "<td style=\"padding:1px 3px;\">$Acti</td>";
                    $detalle .= "<td style=\"padding:1px 3px;\">$nombre</td>";
                    $detalle .= "<td style=\"padding:1px 3px;\">$dob</td>";
                    $detalle .= "<td style=\"padding:1px 3px;\">$license</td>";
                    $detalle .= "<td style=\"padding:1px 3px;\">$tipoLi</td>";
                    $detalle .= "<td style=\"padding:1px 3px;\">$expLi</td>";
                    $detalle .= "<td style=\"padding:1px 3px;\">$expYear</td>";
                    $detalle .= "</tr>";
                       
                }     
            }
            else{
                  #CONSULTAR DESCRIPCION DEL ENDOSO:
                  $query  = "SELECT A.iConsecutivoOperador, A.sNombre, DATE_FORMAT(B.dFechaNacimiento,'%m/%d/%Y') AS dFechaNacimiento, B.eTipoLicencia, A.iNumLicencia, DATE_FORMAT(B.dFechaExpiracionLicencia,'%m/%d/%Y') AS dFechaExpiracionLicencia, iExperienciaYear,eAccion ".
                            "FROM cb_endoso_operador AS A ".
                            "LEFT JOIN ct_operadores AS B ON A.iConsecutivoOperador = B.iConsecutivo ".
                            "WHERE A.iConsecutivoEndoso = '$clave' ";
                  $result = $conexion->query($query) or die($conexion->error);
                  $rows   = $result->num_rows; 
                  if($rows > 0){
                        #DECLARAR ARRAY DE DETALLE:
                        $Detalle = mysql_fetch_all($result);
                        $countD  = count($Detalle); 
                        
                        //Recorremos array de DETALLE:
                        for($x=0;$x<$countD;$x++){
                            
                            if($Detalle[$x]['eAccion'] == "ADDSWAP"){$Detalle[$x]['eAccion'] = "ADD SWAP";}
                            if($Detalle[$x]['eAccion'] == "DELETESWAP"){$Detalle[$x]['eAccion'] = "DELETE SWAP";}
                            
                            $Acti   = $Detalle[$x]['eAccion'];
                            $nombre = $Detalle[$x]['sNombre'];
                            $dob    = $Detalle[$x]['dFechaNacimiento'];
                            $license= $Detalle[$x]['iNumLicencia'];
                            $tipoLi = $Detalle[$x]['eTipoLicencia'];
                            $expLi  = $Detalle[$x]['dFechaExpiracionLicencia'];
                            $expYear= $Detalle[$x]['iExperienciaYear'];
                            
                            $detalle .= "<tr>";
                            $detalle .= "<td style=\"padding:1px 3px;border: 1px solid #dedede;\">$Acti</td>";
                            $detalle .= "<td style=\"padding:1px 3px;border: 1px solid #dedede;\">$nombre</td>";
                            $detalle .= "<td style=\"padding:1px 3px;border: 1px solid #dedede;\">$dob</td>";
                            $detalle .= "<td style=\"padding:1px 3px;border: 1px solid #dedede;\">$license</td>";
                            $detalle .= "<td style=\"padding:1px 3px;border: 1px solid #dedede;\">$tipoLi</td>";
                            $detalle .= "<td style=\"padding:1px 3px;border: 1px solid #dedede;\">$expLi</td>";
                            $detalle .= "<td style=\"padding:1px 3px;border: 1px solid #dedede;\">$expYear</td>";
                            $detalle .= "</tr>";
                        }   
                  }    
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
                 $query   = "UPDATE cb_endoso_estatus SET $actualiza WHERE iConsecutivoPoliza ='$polizaID' AND iConsecutivoEndoso = '$iConsecutivo'";
                 $success = $conexion->query($query);
                 if(!($success)){$transaccion_exitosa = false;$mensaje = "The data was not updated properly, please try again.";}
                 
                 //Incrementamos contador para verificar aprobacion:
                 if($eStatusP == "A"){
                     
                     $iAprobacion++;
                     $validaAccion = set_endoso_poliza($iConsecutivo,$polizaID,$conexion);
                    
                     if(!($validaAccion)){$transaccion_exitosa=false;$mensaje="The policy data was not updated properly, please try again.";}
                     else{
                        $mensaje = "The data has been saved successfully and the driver has been updated in the company policy. <br>Thank you!"; 
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
              $query   = "UPDATE cb_endoso SET $actualiza WHERE iConsecutivo = '$iConsecutivo'"; 
              $success = $conexion->query($query); 
              if(!($success)){$transaccion_exitosa = false;$mensaje = "The data was not saved properly, please try again.";}
          }
      }
      
      //Subir archivo
      if($transaccion_exitosa && $fileError = 0 && $fileName != ""){
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
                      //if($eArchivo != "OTHERS"){$fileName = strtolower($eArchivo).'.'.$fileExten;} //Si la categoria existe renombramos el archivo. 
                      
                      #UPDATE
                      /*if($edit_mode){
                         $sql = "UPDATE cb_endoso_files SET sNombreArchivo ='$fileName', sTipoArchivo ='$fileType', iTamanioArchivo ='$fileSize', ".
                                "hContenidoDocumentoDigitalizado='$sContenido', eArchivo='$eArchivo', ".
                                "dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."', sUsuarioActualizacion='".$_SESSION['usuario_actual']."'".
                                "WHERE iConsecutivo ='".trim($_POST['iConsecutivo'])."'";  
                      }
                      #INSERT
                      else{ */
                         $sql = "INSERT INTO cb_endoso_files (sNombreArchivo, sTipoArchivo, iTamanioArchivo, hContenidoDocumentoDigitalizado, eArchivo,iConsecutivoEndoso, dFechaIngreso, sIP, sUsuarioIngreso) ".
                                "VALUES('$fileName','$fileType','$fileSize','$sContenido','$eArchivo','$iConsecutivo','".date("Y-m-d H:i:s")."', '".$_SERVER['REMOTE_ADDR']."', '".$_SESSION['usuario_actual']."')"; 
                      //}
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
      
      if($Emails['error'] != "0"){
          $htmlTabla.= "<table style=\"font-size:12px;border:1px solid #dedede;border-radius:3px;padding:10px;width:95%; margin:5px auto;font-family: Arial, Helvetica, sans-serif;\">";
          $htmlTabla.= "<tr><td><hr></td></tr>"; 
          $htmlTabla.= "<tr><td style=\"text-align:center;\">".$Emails['error']."</td></tr>";
          $htmlTabla.= "</table>";
      }
      else{
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
      $msjCRC    = "";
      
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);
      
      #ACTUALIZAMOS ENDOSO A SB..
       if($count > 0){ 
          if($Emails['error']=="0"){
              #UPDATE ENDORSEMENT DETAILS:
              $query   = "UPDATE cb_endoso SET eStatus = 'SB',dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."', sUsuarioActualizacion='".$_SESSION['usuario_actual']."' ".
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
            $query = "UPDATE cb_endoso_estatus SET eStatus = 'SB', dFechaAplicacion='".date("Y-m-d H:i:s")."', dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."', sUsuarioActualizacion='".$_SESSION['usuario_actual']."' ".
                     "WHERE iConsecutivoEndoso = '$iConsecutivo' AND iConsecutivoPoliza = '".$Emails[$x]['idPoliza']."'"; 
            $success = $conexion->query($query);
            if(!($success)){$success = false;$mensaje="Error to update the endorsement status, please check with de system admin.";}
            else{
                //ANTES DE ENVIAR CORREO, VERIFICAMOS QUE EXISTAN CORREOS PARA LOS BROKERS REGISTRADOS:
                if($Emails[$x]['emails'] != ""){
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
                    $files        = $Emails[$x]['files'];
                    $delete_files = "";
                    if($files != ""){
                       include("./lib/fpdf153/fpdf.php");//libreria fpdf
                       $file_tmp = fopen('tmp/'.$files["name"],"w") or die("Error when creating the file. Please check."); 
                       fwrite($file_tmp,$files["content"]); 
                       fclose($file_tmp);     
                       $archivo = "tmp/".$files["name"];  
                       $mail->AddAttachment($archivo);
                       $delete_files .= "unlink('tmp/.".$files["name"]."');"; 
                    }
                    
                    $mail_error = false;
                    if(!$mail->Send()){$mail_error = true; $mail->ClearAddresses();}
                    if(!($mail_error)){$msj = "The mail has been sent to the brokers";}
                    else{$msj = "Error: The e-mail cannot be sent.";$error = "1";}
                    
                    $mail->ClearAttachments();
                    eval($delete_files);    
                }
                else{
                    $msjCRC = "<br> If the broker is CRC, the email has not been sent but the status in the system is been updated to \"SENT TO BROKERS\".";
                }
            }    

          } 
      }
      }
      
      $success && $error == '0' ? $conexion->commit() : $conexion->rollback();
      $conexion->close();
      
      $response = array("msj"=>$msj.$msjCRC,"error"=>"$error","tabla" => "$htmlTabla");   
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
                "WHERE A.iConsecutivoTipoEndoso = '2' AND A.iConsecutivo = '$iConsecutivo' ";
      $result = $conexion->query($query) or die($conexion->error);
      $rows   = $result->num_rows; 
      
      if($rows == 0){$error = '1';$mensaje = "Error to query the endorsement data, please try again later.";}
      else{
          
          $Endoso = $result->fetch_assoc();
          //REVISAMOS SI EL ENDOSO ES MULTIPLE O NO:
          if($Endoso['iEndosoMultiple'] == 0){
              #CONSULTAR DESCRIPCION DEL ENDOSO:
              $query  = "SELECT A.iConsecutivo, A.sNombre, DATE_FORMAT(A.dFechaNacimiento,'%m/%d/%Y') AS dFechaNacimiento, A.eTipoLicencia, A.iNumLicencia, DATE_FORMAT(A.dFechaExpiracionLicencia,'%m/%d/%Y') AS dFechaExpiracionLicencia, iExperienciaYear ".
                        "FROM   ct_operadores AS A ".
                        "WHERE A.iConsecutivo = '".$Endoso['iConsecutivoOperador']."' AND A.iConsecutivoCompania = '".$Endoso['iConsecutivoCompania']."' ";
              $result = $conexion->query($query) or die($conexion->error);
              $rows   = $result->num_rows;
              if($rows == 0){$error = '1';$mensaje = "Error to query the endorsement description data, please try again later.";}
              else{
                  
                 //Variables: 
                 $Detalle          = $result->fetch_assoc(); 
                 $Tipo             = "driver";
                 $ComNombre        = $Endoso['sNombreCompania'];
                 $sNombre          = $Detalle['sNombre'];
                 $dFechaNacimiento = $Detalle['dFechaNacimiento'];
                 $eTipoLicencia    = $Detalle['eTipoLicencia'];
                 $iNumLicencia     = $Detalle['iNumLicencia'];
                 $dFechaExpLicen   = $Detalle['dFechaExpiracionLicencia'];
                 $iExperienciaYear = $Detalle['iExperienciaYear'];
                 
                 #CONSULTAR ARCHIVOS:
                 $file          = array();
                 $filtroArchivo = "";
          
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
                 }/*else{
                     //Buscamos archivos por Operador
                     $query  = "SELECT iConsecutivo, sNombreArchivo, eArchivo, hContenidoDocumentoDigitalizado, sTipoArchivo, iTamanioArchivo ".
                               "FROM cb_operador_files WHERE  iConsecutivoOperador = '".$Endoso['iConsecutivoOperador']."' $filtroArchivo "; 
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
                 }*/
                 if(count($file)==0){$file="";} 
                 /**************/ 
                 
                 #CONSULTAMOS POLIZAS DEL ENDOSO E INFO DE LOS EMAILS:
                 $query  = "SELECT iConsecutivoEndoso,iConsecutivoPoliza,B.sNumeroPoliza,B.iTipoPoliza,D.sDescripcion AS sTipoPoliza, sMensajeEmail, A.sEmail, C.iConsecutivo AS iConsecutivoBroker, C.sName AS sBrokerName, C.bEndosoMensual ".
                           "FROM cb_endoso_estatus   AS A ".
                           "LEFT JOIN ct_polizas     AS B ON A.iConsecutivoPoliza  = B.iConsecutivo ".
                           "LEFT JOIN ct_tipo_poliza AS D ON B.iTipoPoliza = D.iConsecutivo ".
                           "LEFT JOIN ct_brokers     AS C ON B.iConsecutivoBrokers = C.iConsecutivo ".
                           "WHERE A.iConsecutivoEndoso = '$iConsecutivo' ";// AND C.bEndosoMensual='0'"; 
                 $result = $conexion->query($query) or die($conexion->error);
                 $rows   = $result->num_rows;
                 
                 if($rows == 0){$error = '1';$mensaje = "The emails can not be generated to this brokers. Please check that the endorsement has brokers to send email from this module.";}
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
                            
                            $action  = "Please add the following $Tipo from policy number: $ComNombre, $sNumPoliza - $sTipoPoliza.";
                            $subject = "$ComNombre//$sNumPoliza - $sTipoPoliza. Endorsement application - please add the following $Tipo from policy.";
                 
                        }
                        else if($Endoso["eAccion"] == 'D'){
                           $action   = "Please delete the following $Tipo from policy number: $ComNombre, $sNumPoliza - $sTipoPoliza";                                                                   
                           $subject  = "$ComNombre//$sNumPoliza - $sTipoPoliza. Endorsement application - please delete the following $Tipo from policy.";
                        }
                        
                        //$bodyData = "<p style=\"color:#000;margin:5px auto; text-align:left;\">".
                        $bodyData = "<ul style=\"color:#010101;line-height:15px;list-style:none;\"> ";
                        $bodyData.= "<li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">NAME: </strong> $sNombre</li>";
                        $bodyData.= "<li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">DOB: </strong> $dFechaNacimiento</li>";
                        
                        //Revisamos Campos opcionales:
                        if($iExperienciaYear != ""){$bodyData.= "<li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">YOE: </strong> $iExperienciaYear</li>";  }
                        if($dFechaExpLicen != "")  {$bodyData.= "<li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">LICENSE EXP: </strong> $dFechaExpLicen</li>";}
                        if($iNumLicencia != "")    {$bodyData.= "<li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">LICENSE NUMBER: </strong> $iNumLicencia</li>";}
                 
                        $bodyData .= "</ul><br><br>";    
                        
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
          }
          else if($Endoso['iEndosoMultiple'] == 1){
              $ComNombre  = $Endoso['sNombreCompania'];  
              
              #CONSULTAR DESCRIPCION DEL ENDOSO:
              $query  = "SELECT A.iConsecutivoOperador, A.sNombre, DATE_FORMAT(B.dFechaNacimiento,'%m/%d/%Y') AS dFechaNacimiento, B.eTipoLicencia, A.iNumLicencia, DATE_FORMAT(B.dFechaExpiracionLicencia,'%m/%d/%Y') AS dFechaExpiracionLicencia, iExperienciaYear,eAccion ".
                        "FROM cb_endoso_operador AS A ".
                        "LEFT JOIN ct_operadores AS B ON A.iConsecutivoOperador = B.iConsecutivo ".
                        "WHERE A.iConsecutivoEndoso = '".$Endoso['iConsecutivo']."' ";
              $result = $conexion->query($query) or die($conexion->error);
              $rows   = $result->num_rows; 
              if($rows == 0){$error = '1';$mensaje = "Error to query the endorsement description data, please try again later.";}
              else{
                  
                  #DECLARAR ARRAY DE DETALLE:
                  $Detalle = mysql_fetch_all($result);
                  $countD  = count($Detalle);
                  
                  #CONSULTAR ARCHIVOS:
                  $file = array();
                  //Buscamos archivos por endoso
                  $query  = "SELECT iConsecutivo, sNombreArchivo, eArchivo, hContenidoDocumentoDigitalizado, sTipoArchivo, iTamanioArchivo ".
                            "FROM cb_endoso_files WHERE iConsecutivoEndoso = '$iConsecutivo' $filtroArchivo"; 
                  $result = $conexion->query($query) or die($conexion->error);
                  $rows   = $result->num_rows;
                  
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
                  if(count($file)==0){$file="";} 
                  
                  #CONSULTAMOS POLIZAS DEL ENDOSO E INFO DE LOS EMAILS:
                  $query  = "SELECT iConsecutivoEndoso,iConsecutivoPoliza,B.sNumeroPoliza,B.iTipoPoliza,D.sDescripcion AS sTipoPoliza, sMensajeEmail, A.sEmail, C.iConsecutivo AS iConsecutivoBroker, C.sName AS sBrokerName, C.bEndosoMensual ".
                            "FROM cb_endoso_estatus   AS A ".
                            "LEFT JOIN ct_polizas     AS B ON A.iConsecutivoPoliza  = B.iConsecutivo ".
                            "LEFT JOIN ct_tipo_poliza AS D ON B.iTipoPoliza = D.iConsecutivo ".
                            "LEFT JOIN ct_brokers     AS C ON B.iConsecutivoBrokers = C.iConsecutivo ".
                            "WHERE A.iConsecutivoEndoso = '$iConsecutivo'";//" AND C.bEndosoMensual='0'"; 
                  $result = $conexion->query($query) or die($conexion->error);
                  $rows   = $result->num_rows;
                  if($rows <= 0){$error = '1';$mensaje = "The emails can not be generated.Please check that the endorsement has brokers to send email from this module.";}
                  else{
                      while($data = $result->fetch_assoc()){ 
                            //Variables por Email:
                            $email      = array();
                            /*$sMensaje   = $data['sMensajeEmail']; */
                            $sNumPoliza = $data['sNumeroPoliza'];
                            $sEmails    = $data['sEmail'];
                            $sBrokerName= $data['sBrokerName'];
                            $sTipoPoliza= $data['sTipoPoliza'];
                            $idPoliza   = $data['iConsecutivoPoliza'];
                            $tipoPoliza = get_policy_type($data['iTipoPoliza']);
                            
                            $data['sMensajeEmail'] != "" ?  $message = $data['sMensajeEmail'] : $message = "Please do the following in drivers from policy: ";
                            
                            #DATOS DEL CORREO:
                            $action   = $message."$ComNombre, $sNumPoliza - $sTipoPoliza.";
                            $subject  = "$ComNombre//$sNumPoliza - $sTipoPoliza. Endorsement application - ".$message;
                            $bodyData = "<table cellspacing=\"0\" cellpadding=\"0\" style=\"color:#000;margin:5px auto; text-align:left;float:left;min-width:300px;\">";
                            $detalle  = "";
                            
                            //Recorremos array de DETALLE:
                            for($x=0;$x<$countD;$x++){
                                
                                if($Detalle[$x]['eAccion'] == "ADDSWAP"){$Detalle[$x]['eAccion'] = "ADD SWAP";}
                                if($Detalle[$x]['eAccion'] == "DELETESWAP"){$Detalle[$x]['eAccion'] = "DELETE SWAP";}
                                
                                $Acti   = $Detalle[$x]['eAccion'];
                                $nombre = $Detalle[$x]['sNombre'];
                                $dob    = $Detalle[$x]['dFechaNacimiento'];
                                $license= $Detalle[$x]['iNumLicencia'];
                                $tipoLi = $Detalle[$x]['eTipoLicencia'];
                                $expLi  = $Detalle[$x]['dFechaExpiracionLicencia'];
                                $expYear= $Detalle[$x]['iExperienciaYear'];
                                
                                $detalle .= "<tr>";
                                $detalle .= "<td style=\"padding:1px 5px;\">$Acti</td>";
                                $detalle .= "<td style=\"padding:1px 5px;\">$nombre</td>";
                                $detalle .= "<td style=\"padding:1px 5px;\">$dob</td>";
                                $detalle .= "<td style=\"padding:1px 5px;\">$license</td>";
                                $tipoLi != "" ? $detalle .= "<td style=\"padding:1px 5px;\">$tipoLi</td>" : "";
                                $expLi  != "" ? $detalle .= "<td style=\"padding:1px 5px;\">$expLi</td>"  : "";
                                $expYear!= "" ? $detalle .= "<td style=\"padding:1px 5px;\">$expYear</td>": "";
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
          }
          
          
          
      }
      $error != "" ? $Emails['error'] = $mensaje : $Emails['error'] = "0";
      $conexion->close(); 
      return $Emails;
  }

  #FUNCION PARA APLICAR ACCION DEL ENDOSO EN UNIDAD/POLIZA:
  function set_endoso_poliza($iConsecutivoEndoso,$iConsecutivoPoliza,$conexion){
    
      $transaccion_exitosa = true;
      
      #CONSULTAR DATOS DEL ENDOSO:
      $query  = "SELECT * FROM cb_endoso WHERE iConsecutivo='$iConsecutivoEndoso'"; 
      $result = $conexion->query($query) or die($conexion->error);
      $rows   = $result->num_rows; 
      
      if($rows > 0){
          $data         = $result->fetch_assoc();
          $dFechaEndoso = $data['dFechaAplicacion'];
          #ENDOSOS NO MULTIPLES:
          if($data['iEndosoMultiple']==0){
              //Tomamos variables:
              $eAccion   = $data['eAccion'];
              $idDetalle = $data['iConsecutivoOperador']; 
              
              //verificamos si ya existe el registro en la tabla:
              $query = "SELECT COUNT(iConsecutivoOperador) AS total FROM cb_poliza_operador WHERE iConsecutivoPoliza='$iConsecutivoPoliza' AND iConsecutivoOperador='$idDetalle'";
              $result= $conexion->query($query) or die($conexion->error);
              $valida= $result->fetch_assoc();
              
              // UPDATE REGISTRO
              if($valida['total'] != 0){
                if($eAccion == "A"){
                    //Agregar registro a la tabla de relacion: 
                    $query   = "UPDATE cb_poliza_operador SET iDeleted='0',dFechaActualizacion='$dFechaEndoso',sIPActualizacion='".$_SERVER['REMOTE_ADDR']."',sUsuarioActualizacion='".$_SESSION['usuario_actual']."' ".
                               "WHERE iConsecutivoPoliza='$iConsecutivoPoliza' AND iConsecutivoOperador='$idDetalle'";
                    $success = $conexion->query($query);     
                }
                else if($eAccion == "D"){
                    //Agregar registro a la tabla de relacion: 
                    $query   = "UPDATE cb_poliza_operador SET iDeleted='1',dFechaActualizacion='$dFechaEndoso',sIPActualizacion='".$_SERVER['REMOTE_ADDR']."',sUsuarioActualizacion='".$_SESSION['usuario_actual']."' ".
                               "WHERE iConsecutivoPoliza='$iConsecutivoPoliza' AND iConsecutivoOperador='$idDetalle'";
                    $success = $conexion->query($query); 
                }    
              }
              // INSERT REGISTRO
              else{
                if($eAccion == "A"){
                    //Agregar registro a la tabla de relacion: 
                    $query   = "INSERT INTO cb_poliza_operador (iConsecutivoPoliza,iConsecutivoOperador,eModoIngreso,dFechaIngreso,sIPIngreso,sUsuarioIngreso) ".
                               "VALUES('$iConsecutivoPoliza','$idDetalle','ENDORSEMENT','$dFechaEndoso','".$_SERVER['REMOTE_ADDR']."','".$_SESSION['usuario_actual']."')";
                    $success = $conexion->query($query);         
                }
                else if($eAccion == "D"){
                    //Agregar registro a la tabla de relacion: 
                    $query   = "INSERT INTO cb_poliza_operador (iConsecutivoPoliza,iConsecutivoOperador,eModoIngreso,dFechaIngreso,sIPIngreso,sUsuarioIngreso,iDeleted) ".
                               "VALUES('$iConsecutivoPoliza','$idDetalle','ENDORSEMENT','$dFechaEndoso','".$_SERVER['REMOTE_ADDR']."','".$_SESSION['usuario_actual']."','1')";
                    $success = $conexion->query($query);     
                }    
              }
              if(!($success)){$transaccion_exitosa = false;}
              
              // Actualizamos el registro general del operador como "no eliminado".
              if($eAccion == "A"){
                 $query   = "UPDATE ct_operadores SET iDeleted='0' WHERE iConsecutivo='$idDetalle'";
                 $success = $conexion->query($query);
                 if(!($success)){$transaccion_exitosa = false;} 
              }
              else if($eAccion == "D"){
                  //CONSULTAMOS, SI EL OPERADOR NO ESTA ACTUALMENTE EN NINGUNA POLIZA, LO MARCAREMOS COMO ELIMINADA EN EL CATALOGO:
                  $query = "SELECT COUNT(A.iConsecutivo) AS total ".
                           "FROM ct_operadores AS A INNER JOIN cb_poliza_operador AS B ON A.iConsecutivo = B.iConsecutivoOperador ".
                           "WHERE A.iConsecutivo = '$idDetalle'";
                  $r     = $conexion->query($query);
                  $valid = $r->fetch_assoc();
                  $valid['total'] > 0 ? $iElimina = false : $iElimina = true;
                      
                  if($iElimina){
                    $query   = "UPDATE ct_operadores SET iDeleted = '1' WHERE iConsecutivo='$idDetalle'";
                    $success = $conexion->query($query); 
                    if(!($success)){$error = '1'; $mensaje = "Error to try update data, please try again later.";}
                  }
              }
              
              
          }
          else if($data['iEndosoMultiple']==1){
              //Consultamos las unidades relacionadas al endoso:
              $query  = "SELECT * FROM cb_endoso_operador WHERE iConsecutivoEndoso = '$iConsecutivoEndoso' "; 
              $result = $conexion->query($query); 
              $rows   = $result->num_rows; 
              if($rows > 0){
                  //Recorremos resultado:
                  while ($item = $result->fetch_assoc()) { 
                      //Tomamos variables:
                      $eAccion   = $item['eAccion'];
                      $idDetalle = $item['iConsecutivoOperador']; 
                      
                      //verificamos si ya existe el registro en la tabla:
                      $query = "SELECT COUNT(iConsecutivoOperador) AS total FROM cb_poliza_operador WHERE iConsecutivoPoliza='$iConsecutivoPoliza' AND iConsecutivoOperador='$idDetalle'";
                      $result= $conexion->query($query) or die($conexion->error);
                      $valida= $result->fetch_assoc();
                      
                      // UPDATE REGISTRO
                      if($valida['total'] != 0){
                        if($eAccion == "ADD" || $eAccion == "ADDSWAP"){
                            //Agregar registro a la tabla de relacion: 
                            $query   = "UPDATE cb_poliza_operador SET iDeleted='0',dFechaActualizacion='$dFechaEndoso',sIPActualizacion='".$_SERVER['REMOTE_ADDR']."',sUsuarioActualizacion='".$_SESSION['usuario_actual']."' ".
                                       "WHERE iConsecutivoPoliza='$iConsecutivoPoliza' AND iConsecutivoOperador='$idDetalle'";
                            $success = $conexion->query($query);     
                        }
                        else if($eAccion == "DELETE" || $eAccion == "DELETESWAP"){
                            //Agregar registro a la tabla de relacion: 
                            $query   = "UPDATE cb_poliza_operador SET iDeleted='1',dFechaActualizacion='$dFechaEndoso',sIPActualizacion='".$_SERVER['REMOTE_ADDR']."',sUsuarioActualizacion='".$_SESSION['usuario_actual']."' ".
                                       "WHERE iConsecutivoPoliza='$iConsecutivoPoliza' AND iConsecutivoOperador='$idDetalle'";
                            $success = $conexion->query($query); 
                        }    
                      }
                      // INSERT REGISTRO
                      else{
                        if($eAccion == "ADD" || $eAccion == "ADDSWAP"){
                            //Agregar registro a la tabla de relacion: 
                            $query   = "INSERT INTO cb_poliza_operador (iConsecutivoPoliza,iConsecutivoOperador,eModoIngreso,dFechaIngreso,sIPIngreso,sUsuarioIngreso) ".
                                       "VALUES('$iConsecutivoPoliza','$idDetalle','ENDORSEMENT','$dFechaEndoso','".$_SERVER['REMOTE_ADDR']."','".$_SESSION['usuario_actual']."')";
                            $success = $conexion->query($query);         
                        }
                        else if($eAccion == "DELETE" || $eAccion == "DELETESWAP"){
                            //Agregar registro a la tabla de relacion: 
                            $query   = "INSERT INTO cb_poliza_operador (iConsecutivoPoliza,iConsecutivoOperador,eModoIngreso,dFechaIngreso,sIPIngreso,sUsuarioIngreso,iDeleted) ".
                                       "VALUES('$iConsecutivoPoliza','$idDetalle','ENDORSEMENT','$dFechaEndoso','".$_SERVER['REMOTE_ADDR']."','".$_SESSION['usuario_actual']."','1')";
                            $success = $conexion->query($query);     
                        }    
                      }
                      if(!($success)){$transaccion_exitosa = false;}
                      
                      // Actualizamos el registro general del operador como "no eliminado".
                      if($eAccion == "ADD" || $eAccion == "ADDSWAP"){
                         $query   = "UPDATE ct_operadores SET iDeleted='0' WHERE iConsecutivo='$idDetalle'";
                         $success = $conexion->query($query);
                         if(!($success)){$transaccion_exitosa = false;} 
                      }
                      else if($eAccion == "DELETE" || $eAccion == "DELETESWAP"){
                          //CONSULTAMOS, SI EL OPERADOR NO ESTA ACTUALMENTE EN NINGUNA POLIZA, LO MARCAREMOS COMO ELIMINADA EN EL CATALOGO:
                          $query = "SELECT COUNT(A.iConsecutivo) AS total ".
                                   "FROM ct_operadores AS A INNER JOIN cb_poliza_operador AS B ON A.iConsecutivo = B.iConsecutivoOperador ".
                                   "WHERE A.iConsecutivo = '$idDetalle'";
                          $r     = $conexion->query($query);
                          $valid = $r->fetch_assoc();
                          $valid['total'] > 0 ? $iElimina = false : $iElimina = true;
                              
                          if($iElimina){
                            $query   = "UPDATE ct_operadores SET iDeleted = '1' WHERE iConsecutivo='$idDetalle'";
                            $success = $conexion->query($query); 
                            if(!($success)){$error = '1'; $mensaje = "Error to try update data, please try again later.";}
                          }
                      }
                      
                      //Revisamos Action:
                      /*if($eAccion == "ADD" || $eAccion == "ADDSWAP"){
                          
                         //Agregamos a la poliza la unidad: 
                         $query   = "INSERT INTO cb_poliza_operador (iConsecutivoPoliza,iConsecutivoOperador,eModoIngreso,dFechaIngreso,sIPIngreso,sUsuarioIngreso) ".
                                    "VALUES('$iConsecutivoPoliza','$idDetalle','ENDORSEMENT','$dFechaEndoso','".$_SERVER['REMOTE_ADDR']."','".$_SESSION['usuario_actual']."')";
                         $success = $conexion->query($query); 
                        
                      }
                      if($eAccion == "DELETE" || $eAccion == "DELETESWAP"){
                          //Marcar como eliminado de la poliza la unidad:
                          $query   = "UPDATE cb_poliza_operador SET iDeleted='1',dFechaActualizacion='$dFechaEndoso',sIPActualizacion='".$_SERVER['REMOTE_ADDR']."',sUsuarioActualizacion='".$_SESSION['usuario_actual']."' ".
                                     "WHERE iConsecutivoPoliza='$iConsecutivoPoliza' AND iConsecutivoOperador='$idDetalle' AND iDeleted='0'";
                          $success = $conexion->query($query); 
                          if(!($success)){$transaccion_exitosa = false;}
                          else{
                              //CONSULTAMOS, SI EL OPERADOR NO ESTA ACTUALMENTE EN NINGUNA POLIZA, LO MARCAREMOS COMO ELIMINADA EN EL CATALOGO:
                              $query = "SELECT COUNT(A.iConsecutivo) AS total ".
                                       "FROM ct_operadores AS A INNER JOIN cb_poliza_operador AS B ON A.iConsecutivo = B.iConsecutivoOperador ".
                                       "WHERE A.iConsecutivo = '$idDetalle'";
                              $r     = $conexion->query($query);
                              $valid = $r->fetch_assoc();
                              $valid['total'] > 0 ? $iElimina = false : $iElimina = true;
                              
                              if($iElimina){
                                $query   = "UPDATE ct_operadores SET iDeleted = '1' WHERE iConsecutivo='$idDetalle'";
                                $success = $conexion->query($query); 
                                if(!($success)){$error = '1'; $mensaje = "Error to try update data, please try again later.";}
                              }
                          }
                      } */
                       
                  }
              }
              else{$transaccion_exitosa = false;}
          }
      }
      else{$transaccion_exitosa = false;}
   
      return $transaccion_exitosa;
      
  }
  
?>
