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
     $filtroQuery = " WHERE A.eStatus != 'E' AND A.iConsecutivoTipoEndoso = '1' AND A.iDeleted='0' ";
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
      $sql = "SELECT A.iConsecutivo,D.sNombreCompania,DATE_FORMAT( A.dFechaAplicacion, '%m/%d/%Y' ) AS dFechaIngreso,C.sDescripcion,A.eStatus,eAccion,D.iOnRedList,sVIN, A.iEndosoMultiple ".
             "FROM cb_endoso AS A ".
             "LEFT JOIN ct_tipo_endoso AS C ON A.iConsecutivoTipoEndoso = C.iConsecutivo ".
             "LEFT JOIN ct_companias   AS D ON A.iConsecutivoCompania = D.iConsecutivo ".
             "LEFT JOIN ct_unidades    AS F ON A.iConsecutivoUnidad = F.iConsecutivo ".
             $filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
      $result = $conexion->query($sql);
      $rows = $result->num_rows; 
         
        if ($rows > 0) {    
            while ($items = $result->fetch_assoc()) { 
               if($items["iConsecutivo"] != ""){
                   
                     $btn_confirm = "";
                     $estado      = "";
                     $class       = "";
                     $descripcion = ""; 
                     #ESTATUS DEL ENDOSO:
                     switch($items["eStatus"]){
                         case 'S': 
                            $estado      = 'SENT TO SOLO-TRUCKING<br><span style="font-size:11px!important;">The data can be edited by you or by the employees of just-trucking.</span>';
                            $class       = "class = \"blue\"";
                            $btn_confirm = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"View and Edit Endorsement Status\"><i class=\"fa fa-pencil-square-o\"></i></div>".
                                           "<div class=\"btn_edit_estatus btn-icon send-email btn-left\" title=\"Send e-mail to the brokers\"><i class=\"fa fa-envelope\"></i></div>"; 
                         break;
                         case 'A': 
                            $estado      = 'APPROVED<br><span style="font-size:11px!important;">Your endorsement has been approved successfully.</span>';
                            $class       = "class = \"green\"";
                            $btn_confirm = "<div class=\"btn_change_status btn-icon edit btn-left\" title=\"Change the status of endorsement\"><i class=\"fa fa-pencil-square-o\"></i></div>";
                            $btn_confirm.= "<div class=\"btn-icon send-email btn-left\" title=\"See the e-mail sent\" onclick=\"fn_endorsement.email.preview('".$items['iConsecutivo']."');\"><i class=\"fa fa-external-link\"></i></div>"; 
                         break;
                         case 'D': 
                            $estado      = 'CANCELED<br><span style="font-size:11px!important;">Your endorsement has been canceled, please see the reasons on the edit button.</span>';
                            $class       = "class = \"red\"";
                            $btn_confirm = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"View and Edit Endorsement Status\"><i class=\"fa fa-pencil-square-o\"></i></div>".
                                           "<div class=\"btn_edit_estatus btn-icon send-email btn-left\" title=\"Send e-mail to the brokers\"><i class=\"fa fa-envelope\"></i></div>";
                                        
                         break;
                         case 'SB': 
                            $estado      = 'SENT TO BROKERS<br><span style="font-size:11px!important;">Your endorsement has been sent to the brokers.</span>';
                            $class       = "class = \"yellow\"";
                            $btn_confirm = "<div class=\"btn_change_status btn-icon edit btn-left\" title=\"Change the status of endorsement\"><i class=\"fa fa-pencil-square-o\"></i></div>"; 
                            $btn_confirm.= "<div class=\"btn-icon send-email btn-left\" title=\"See the e-mail sent\" onclick=\"fn_endorsement.email.preview('".$items['iConsecutivo']."');\"><i class=\"fa fa-external-link\"></i></div>";
                         break;
                         case 'P': 
                            $estado      = 'IN PROCESS<br><span style="font-size:11px!important;">Your endorsement is being in process by the brokers.</span>';
                            $class       = "class = \"orange\"";
                            $btn_confirm = "<div class=\"btn_change_status btn-icon edit btn-left\" title=\"Change the status of endorsement\"><i class=\"fa fa-pencil-square-o\"></i></div>";
                            $btn_confirm.= "<div class=\"btn-icon send-email btn-left\" title=\"See the e-mail sent\" onclick=\"fn_endorsement.email.preview('".$items['iConsecutivo']."');\"><i class=\"fa fa-external-link\"></i></div>";
                         break;
                     } 
                     
                     $color_action = "";
                     $action       = "";
                     $detalle      = "";
                     
                     if($items['iEndosoMultiple'] == "0"){
                         switch($items["eAccion"]){
                             case 'A': $action = 'ADD'; break;
                             case 'D': $action = 'DELETE'; break;
                         }
                         $detalle = strtoupper($items['sVIN']);
                     }else if($items['iEndosoMultiple'] == "1"){
                         #CONSULTAR DETALLE DEL ENDOSO:
                         $query = "SELECT A.sVIN, (CASE 
                                    WHEN A.eAccion = 'ADDSWAP'    THEN 'ADD SWAP'
                                    WHEN A.eAccion = 'DELETESWAP' THEN 'DELETE SWAP'
                                    ELSE A.eAccion
                                    END) AS eAccion FROM cb_endoso_unidad AS A WHERE A.iConsecutivoEndoso = '".$items['iConsecutivo']."' ORDER BY sVIN ASC";
                         $r     = $conexion->query($query);
                         while($item = $r->fetch_assoc()){
                            $detalle == "" ? $detalle = $item['sVIN']    : $detalle.= "<br>".$item['sVIN'];
                            $action  == "" ? $action  = $item['eAccion'] : $action .= "<br>".$item['eAccion']; 
                         }
                     }
                     
                      //Redlist:
                     $items['iOnRedList'] == '1' ? $redlist_icon = "<i class=\"fa fa-star\" style=\"color:#e8051b;margin-right:4px;\"></i>" : $redlist_icon = ""; 
                     $htmlTabla .= "<tr $class>
                                        <td>".$items['iConsecutivo']."</td>".
                                       "<td>".$redlist_icon.$items['sNombreCompania']."</td>".
                                       "<td>".$detalle."</td>". 
                                       "<td>".$action."</td>".
                                       "<td class=\"text-center\">".$items['dFechaIngreso']."</td>". 
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
      //$idPoliza = trim($_POST['idPoliza']);
      $domroot  = $_POST['domroot'];
      
      #Function Begin
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                 
      $sql    = "SELECT A.iConsecutivo, iConsecutivoCompania, iConsecutivoTipoEndoso, A.eStatus, ". 
                "A.sComentarios, DATE_FORMAT(dFechaAplicacion,'%m/%d/%Y') AS dFechaAplicacion, DATE_FORMAT(dFechaAplicacion,'%H:%i') AS dFechaAplicacionHora, ".
                "sSolicitanteNombre, sSolicitanteEmail, IF(sSolicitanteFecha != '0000-00-00 00:00:00' AND sSolicitanteFecha != '',DATE_FORMAT(sSolicitanteFecha,'%m/%d/%Y'), '') AS sSolicitanteFecha ".  
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
      $iConsecutivoCompania = trim($_POST['iConsecutivoCompania']);
      $sComentarios         = $_POST['sComentarios'] != "" ? "'".utf8_decode(trim($_POST['sComentarios']))."'" : "''";
      $sIP                  = $_SERVER['REMOTE_ADDR'];
      $sUsuario             = $_SESSION['usuario_actual'];
      $dFecha               = date("Y-m-d H:i:s");
      $dFechaApp            = trim($_POST['dFechaAplicacion'])     != "" ? date('Y-m-d',strtotime(trim($_POST['dFechaAplicacion']))) : date("Y-m-d");
      $dFechaAppHora        = trim($_POST['dFechaAplicacionHora']) != "" ? date('H:i:s',strtotime(trim($_POST['dFechaAplicacionHora']))) : date("H:m:s");
      $dFechaApp            = $dFechaApp." ".$dFechaAppHora;
      $sSolicitanteNombre   = trim(strtoupper($_POST['sSolicitanteNombre']));
      $sSolicitanteEmail    = trim(strtolower($_POST['sSolicitanteEmail']));
      $sSolicitanteFecha    = $_POST['sSolicitanteFecha'] != "" ? date('Y-m-d',strtotime(trim($_POST['sSolicitanteFecha']))) : "";
  
      //GUARDAMOS EL ENDOSO
      if($edit_mode == 'true'){
          //UPDATE
          $query   = "UPDATE cb_endoso SET dFechaAplicacion='$dFechaApp', sComentarios=$sComentarios, sIP='$sIP',sUsuarioActualizacion='$sUsuario',dFechaActualizacion='$dFecha',iEndosoMultiple='1', ".
                     "sSolicitanteNombre='$sSolicitanteNombre',sSolicitanteEmail='$sSolicitanteEmail',sSolicitanteFecha='$sSolicitanteFecha' ".   
                     "WHERE iConsecutivo='$iConsecutivo' AND iConsecutivoCompania='$iConsecutivoCompania'";
          $mensaje = "The data was updated successfully.";
      
      }else if($edit_mode == 'false'){
          //INSERT
          $query   = "INSERT cb_endoso (iConsecutivoCompania,iConsecutivoTipoEndoso,eStatus,dFechaAplicacion,sComentarios,sIP,sUsuarioIngreso,dFechaIngreso,sSolicitanteNombre,sSolicitanteEmail,sSolicitanteFecha) ".
                     "VALUES('$iConsecutivoCompania','1','S','$dFechaApp',$sComentarios,'$sIP','$sUsuario','$dFecha','$sSolicitanteNombre','$sSolicitanteEmail','$sSolicitanteFecha') ";
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
     
      $success && $error == '0' ? $conexion->commit() : $conexion->rollback();
      $conexion->close();
      $response = array("error"=>"$error","msj"=>"$mensaje","iConsecutivo"=>"$iConsecutivo");
      echo json_encode($response);
  }
  
  #MULTIPLE UNITS:
  function unit_save(){
      
      include("cn_usuarios.php");  
      $conexion->autocommit(FALSE); 
      
      #VARIABLES:
      $success              = true;
      $edit_mode            = trim($_POST['edit_mode']);
      $iConsecutivoUnidad   = trim($_POST['iConsecutivoUnidad']);
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
      
      //REVISAMOS DATOS DE LA UNIDAD:
      if($iConsecutivoUnidad == ""){
         //Verificamos si la unidad ya existe: 
         $query  = "SELECT iConsecutivo FROM ct_unidades WHERE sVIN='".trim($_POST['sVIN'])."' AND iConsecutivoCompania = '$iConsecutivoCompania'";
         $result = $conexion->query($query);
         $items  = $result->fetch_assoc();
         //Tomamos el consecutivo de la unidad ya existente:
         $iConsecutivoUnidad          = trim($items['iConsecutivo']);
         $_POST['iConsecutivoUnidad'] = $iConsecutivoUnidad; 
      }
  
      if($iConsecutivoUnidad!= ""){
         
          //Verificamos si se va a editar en el DETALLE:
          $query = "SELECT COUNT(iConsecutivoUnidad) AS total FROM cb_endoso_unidad ".
                   "WHERE iConsecutivoUnidad='$iConsecutivoUnidad' AND iConsecutivoEndoso='$iConsecutivoEndoso'";
          $result= $conexion->query($query);
          $valid = $result->fetch_assoc();
          $valid['total'] > 0 ? $edit_detalle = true : $edit_detalle = false;
          
          foreach($_POST as $campo => $valor){
            if($campo != "accion" && $campo != "edit_mode"){ //Estos campos no se insertan a la tabla
                #CAMPOS QUE SE GUARDAN A NIVEL CATALOGO
                if($campo != "iConsecutivoUnidad" && $campo != "iConsecutivoCompania" && $campo != "iTotalPremiumPD" && $campo != "iConsecutivoRadio" && $campo != "eAccion" && $campo != "iConsecutivoEndoso"){
                   array_push($valorCatalogo,"$campo='". strtoupper($valor)."'"); 
                }
                #CAMPOS QUE DE GUARDAN A NIVEL DETALLE ENDOSO:
                if($campo == "sVIN" || $campo == "iConsecutivoEndoso" || $campo == "iConsecutivoRadio" || $campo == "iTotalPremiumPD" || $campo == "eAccion" || $campo == "iConsecutivoUnidad"){
                   if($valid['total'] == 0){
                        if($valor != ""){
                            array_push($campoDetalle ,$campo);
                            array_push($valorDetalle, strtoupper($valor));    
                        }
                   }
                   else{
                       if($valor != ""){
                            array_push($valorDetalle,"$campo='". strtoupper($valor)."'");
                       }
                   } 
                }
            }
          }
          
          #EDITAR LA UNIDAD:
          array_push($valorCatalogo,"sUsuarioActualizacion='".$sUsuario."'");
          array_push($valorCatalogo,"sIP='".$sIP."'");
          array_push($valorCatalogo ,"dFechaActualizacion='".$dFecha."'");
          //array_push($valorCatalogo ,"eModoIngreso='ENDORSEMENT'");
                
          $query   = "UPDATE ct_unidades SET ".implode(",",$valorCatalogo)." WHERE iConsecutivo ='$iConsecutivoUnidad' AND iConsecutivoCompania = '$iConsecutivoCompania'"; 
          $success = $conexion->query($query);
          if(!($success)){$mensaje = "Error: The data of unit has not been saved successfully, please try again.";$error = "1";} 
                  
      }
      else{
         
         //GUARDAR DATOS A NIVEL DETALLE ENDOSO:
         foreach($_POST as $campo => $valor){
            if($campo != "accion" && $campo != "edit_mode"){
                
                #CAMPOS QUE SE GUARDAN PARA UNA UNIDAD NUEVA
                if($campo != "iConsecutivoUnidad" && $campo != "iTotalPremiumPD" && $campo != "iConsecutivoRadio" && $campo != "eAccion" && $campo != "iConsecutivoEndoso"){
                   if($valor != ""){
                       array_push($campoCatalogo ,$campo);
                       array_push($valorCatalogo, strtoupper($valor));
                   }
                }
                
                #CAMPOS QUE DE GUARDAN A NIVEL DETALLE ENDOSO:
                if($campo == "sVIN" || $campo == "iConsecutivoEndoso" || $campo == "iConsecutivoRadio" || $campo == "iTotalPremiumPD" || $campo == "eAccion" || $campo == "iConsecutivoUnidad"){
                    if($valor != ""){
                        array_push($campoDetalle ,$campo);
                        array_push($valorDetalle, strtoupper($valor));
                    }    
                }
            }
         } 
         
         //Se inicializan campos adicionales de control
         array_push($campoCatalogo ,"sUsuarioIngreso"); array_push($valorCatalogo,$sUsuario);
         array_push($campoCatalogo ,"sIP"); array_push($valorCatalogo,$sIP); 
         array_push($campoCatalogo ,"eModoIngreso"); array_push($valorCatalogo,"ENDORSEMENT"); 
         
         $query   = "INSERT INTO ct_unidades (".implode(",",$campoCatalogo).") VALUES ('".implode("','",$valorCatalogo)."')";
         $success = $conexion->query($query);
         if($success){
             $iConsecutivoUnidad = $conexion->insert_id;
             array_push($campoDetalle ,"iConsecutivoUnidad");
             array_push($valorDetalle, $iConsecutivoUnidad);
         }
         else{$mensaje = "Error: The data of unit has not been saved successfully, please try again.";$error = "1";}
      
      }
      
      if($success){
         if($edit_detalle){
            $query   = "UPDATE cb_endoso_unidad SET ".implode(",",$valorDetalle)." WHERE iConsecutivoUnidad ='$iConsecutivoUnidad' AND iConsecutivoEndoso='$iConsecutivoEndoso'"; 
            $mensaje = "The data was updated successfully.";
         }
         else{
            $query   = "INSERT INTO cb_endoso_unidad (".implode(",",$campoDetalle).") VALUES ('".implode("','",$valorDetalle)."')";
            $mensaje = "The data was saved successfully.";
         }
      
         $success = $conexion->query($query);
         if(!($success)){$mensaje = "Error: The data of unit/endorsement has not been saved successfully, please try again.";$error = "1";}
         
      }
      
      $success && $error == '0' ? $conexion->commit() : $conexion->rollback();
      $conexion->close();
      $response = array("error"=>"$error","msj"=>"$mensaje");
      echo json_encode($response);
      
  }
  function unit_datagrid(){
      
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $iConsecutivo        = trim($_POST['iConsecutivoEndoso']);
      
      if($iConsecutivo != ""){
          #CONTAR REGISTROS                                                                                                              
          $sql    = "SELECT COUNT(iConsecutivoEndoso) AS total ".
                    "FROM cb_endoso_unidad AS A ".
                    "LEFT JOIN ct_unidades AS B ON A.iConsecutivoUnidad = B.iConsecutivo ".
                    "WHERE iConsecutivoEndoso='$iConsecutivo'";
          $result = $conexion->query($sql);
          $rows   = $result->fetch_assoc();
          
          if($rows['total'] > 0){
             //Filtros de informacion //
            $filtroQuery = " WHERE iConsecutivoEndoso = '".$iConsecutivo."' "; 
            
            // ordenamiento//
            $ordenQuery = " ORDER BY sVIN ASC";
            
            #CONSULTA:
            $sql    = "SELECT iConsecutivoUnidad, B.sVIN, B.iYear, A.iTotalPremiumPD, A.eAccion, C.sDescripcion AS sRadio, D.sAlias AS sModelo, B.sTipo, B.sPeso ".
                      "FROM cb_endoso_unidad      AS A ".
                      "LEFT JOIN ct_unidades      AS B ON A.iConsecutivoUnidad = B.iConsecutivo ".
                      "LEFT JOIN ct_unidad_radio  AS C ON A.iConsecutivoRadio = C.iConsecutivo ".
                      "LEFT JOIN ct_unidad_modelo AS D ON B.iModelo = D.iConsecutivo".$filtroQuery.$ordenQuery;
            $result = $conexion->query($sql);
            
            while ($items = $result->fetch_assoc()) { 
                
                 $items['iTotalPremiumPD'] > 0 ? $value = "\$ ".number_format($items['iTotalPremiumPD'],2,'.',',') : $value = ""; 
                 if($items['eAccion'] == "ADDSWAP"){$action = "ADD SWAP";}else
                 if($items['eAccion'] == "DELETESWAP"){$action = "DELETE SWAP";}
                 else{$action = $items['eAccion'];}
               
                 $htmlTabla .= "<tr>".
                               "<td id=\"idUnit_".$items['iConsecutivoUnidad']."\">".$action."</td>".
                               "<td>".$items['iYear']."</td>".
                               "<td>".$items['sModelo']."</td>".
                               "<td>".$items['sVIN']."</td>". 
                               "<td class=\"txt-c\">".$items['sRadio']."</td>".
                               "<td class=\"txt-c\">".$items['sPeso']."</td>".
                               "<td class=\"txt-c\">".$items['sTipo']."</td>".
                               "<td class=\"txt-r\">".$value."</td>".
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
  function unit_get(){
      
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
      $sql    = "SELECT A.iConsecutivoUnidad, A.sVIN, A.iConsecutivoRadio, A.iTotalPremiumPD, A.eAccion, B.iModelo, B.iYear, B.sTipo ".  
                "FROM      cb_endoso_unidad AS A ".
                "LEFT JOIN ct_unidades      AS B ON A.iConsecutivoUnidad = B.iConsecutivo ". 
                "WHERE A.iConsecutivoEndoso = '$idEndoso' AND A.iConsecutivoUnidad='$clave'";
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
  function unit_delete(){  
      
      #VARIABLES
      $error    = '0';
      $mensaje  = "";
      $fields   = "";
      $clave    = trim($_POST['iConsecutivoUnidad']);
      $idEndoso = trim($_POST['iConsecutivoEndoso']);
      
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE); 
      
      //CONSULTAMOS, SI LA UNIDAD NO ESTA ACTUALMENTE EN NINGUNA POLIZA, LA MARCAREMOS COMO ELIMINADA EN EL CATALOGO:
      $query = "SELECT COUNT(A.iConsecutivo) AS total ".
               "FROM ct_unidades AS A INNER JOIN cb_poliza_unidad AS B ON A.iConsecutivo = B.iConsecutivoUnidad ".
               "WHERE A.iConsecutivo = '$clave'";
      $result= $conexion->query($query);
      $valid = $result->fetch_assoc();
      $valid['total'] > 0 ? $iElimina = false : $iElimina = true;
      
      $query   = "DELETE FROM cb_endoso_unidad WHERE iConsecutivoUnidad='$clave' AND iConsecutivoEndoso='$idEndoso'";
      $success = $conexion->query($query);
      
      if(!($success)){$error = '1'; $mensaje = "Error to try delete data, please try again later.";}
      else{
         if($iElimina){
            $query   = "UPDATE ct_unidades SET iDeleted = '1' WHERE iConsecutivo='$clave'";
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
              /*$encabezado2 = "style=\"color: #fff;text-align: center;font-weight:bold;text-transform:uppercase;\"";     
                   
              #HTML TABLA:
              $htmlTabla .= "<tr class=\"grid-head1\"><td $encabezado2 colspan=\"100%\">ENDORSEMENT TO $sBroker</td></tr>"; */
              
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
              $endosoFields .= "</td>"; */
                    
              /*$htmlTabla .= "<tr>".
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
                            "</tr>"; */
              $html .= '<h3>'.$sNumPoliza.' / '.$sDescPoliza.' / '.$sBroker.'</h3>';
              $html .= '<div id="dataPolicy_'.$data['iConsecutivoPoliza'].'" class="data_policy" style="height:150px!important;">'.$endosoFields.'</div>'; 
                                 

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
          #consultar comentarios del claim:
          $query  = "SELECT iConsecutivo AS iConsecutivoEndoso, eStatus, sComentarios,iEndosoMultiple FROM cb_endoso WHERE iConsecutivo = '$clave'";
          $result = $conexion->query($query);
          $rows   = $result->num_rows;
          if($rows > 0){
            $data    = $result->fetch_assoc();
            $fields .= "\$('#$domroot :input[name=sComentariosEndoso]').val('".utf8_decode($data['sComentarios'])."');\n"; 
            $fields .= "\$('#$domroot :input[name=iConsecutivoEndoso]').val('".$data['iConsecutivoEndoso']."');\n";  
            $fields .= "\$('#$domroot :input[name=eStatusEndoso]').val('".utf8_decode($data['eStatus'])."');\n"; 
            
            if($data['iEndosoMultiple'] == 0){
                  #CONSULTAR DESCRIPCION DEL ENDOSO: 
                  $query  = "SELECT A.iConsecutivoUnidad, B.sVIN, A.eAccion,B.iYear, B.iTotalPremiumPD, C.sDescripcion AS sRadio, D.sDescripcion AS sModelo, D.sAlias AS sAliasModelo, B.sPeso, B.sTipo ".
                            "FROM cb_endoso             AS A ".
                            "LEFT JOIN ct_unidades      AS B ON A.iConsecutivoUnidad = B.iConsecutivo ".
                            "LEFT JOIN ct_unidad_radio  AS C ON B.iConsecutivoRadio  = C.iConsecutivo ".
                            "LEFT JOIN ct_unidad_modelo AS D ON B.iModelo            = D.iConsecutivo ".
                            "WHERE A.iConsecutivo='$clave'";
                  $result = $conexion->query($query) or die($conexion->error);
                  $rows   = $result->num_rows; 
                  if($rows > 0){
                      #DECLARAR ARRAY DE DETALLE:
                      $Detalle = $result->fetch_assoc();
                      
                      if($Detalle['eAccion'] == "A"){$Detalle[$x]['eAccion'] = "ADD";}
                      if($Detalle['eAccion'] == "D"){$Detalle[$x]['eAccion'] = "DELETE";}
                         
                      $Acti = $Detalle['eAccion'];
                      $Year = $Detalle['iYear'];
                      $Peso = $Detalle['sPeso'];
                      $VIN  = $Detalle['sVIN'];
                      $type = $Detalle['sTipo'];
                      $Detalle['sAliasModelo']  != '' ? $Make     = $Detalle['sAliasModelo'] : $Make   = $Detalle['sModelo']; 
                      $Detalle['sRadio']        != '' ? $Radius   = $Detalle['sRadio']       : $Radius = "";
                      $Detalle["iTotalPremiumPD"] > 0 ? $PDAmount = "\$ ".number_format($Detalle["iTotalPremiumPD"],2,'.',',') : $PDAmount = "";
                         
                         $detalle .= "<tr>";
                         $detalle .= "<td style=\"padding:1px 3px;border: 1px solid #dedede;\">$Acti</td>";
                         $detalle .= "<td style=\"padding:1px 3px;border: 1px solid #dedede;\">$Year</td>";
                         $detalle .= "<td style=\"padding:1px 3px;border: 1px solid #dedede;\">$Make</td>";
                         $detalle .= "<td style=\"padding:1px 3px;border: 1px solid #dedede;\">$VIN</td>";
                         $detalle .= "<td style=\"padding:1px 3px;border: 1px solid #dedede;\">$Radius</td>";
                         $detalle .= "<td style=\"padding:1px 3px;border: 1px solid #dedede;\">$Peso</td>";
                         $detalle .= "<td style=\"padding:1px 3px;border: 1px solid #dedede;\">$type</td>";
                         $detalle .= "<td style=\"padding:1px 3px;border: 1px solid #dedede;\" class=\"txt-r\">$PDAmount</td>";
                         $detalle .= "</tr>";
                      
                  }    
            }
            else{
                  #CONSULTAR DESCRIPCION DEL ENDOSO: 
                  $query  = "SELECT A.sVIN, A.eAccion,B.iYear, A.iTotalPremiumPD, C.sDescripcion AS sRadio, D.sDescripcion AS sModelo, D.sAlias AS sAliasModelo, B.sPeso, B.sTipo ".
                            "FROM cb_endoso_unidad      AS A ".
                            "LEFT JOIN ct_unidades      AS B ON A.iConsecutivoUnidad = B.iConsecutivo ".
                            "LEFT JOIN ct_unidad_radio  AS C ON A.iConsecutivoRadio = C.iConsecutivo ".
                            "LEFT JOIN ct_unidad_modelo AS D ON B.iModelo = D.iConsecutivo ".
                            "WHERE A.iConsecutivoEndoso = '$clave'";
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
                         
                         $Acti = $Detalle[$x]['eAccion'];
                         $Year = $Detalle[$x]['iYear'];
                         $type = $Detalle[$x]['sTipo'];
                         $VIN  = $Detalle[$x]['sVIN'];
                         $Peso = $Detalle[$x]['sPeso'];
                         $Detalle[$x]['sAliasModelo'] != ''  ? $Make     = $Detalle[$x]['sAliasModelo'] : $Make = $Detalle[$x]['sModelo']; 
                         $Detalle[$x]['sRadio']       != ''  ? $Radius   = $Detalle[$x]['sRadio'] : $Radius = "";
                         $Detalle[$x]["iTotalPremiumPD"] > 0 ? $PDAmount = "\$ ".number_format($Detalle[$x]["iTotalPremiumPD"],2,'.',',') : $PDAmount = "";
                         
                         $detalle .= "<tr>";
                         $detalle .= "<td style=\"padding:1px 3px;border: 1px solid #dedede;\">$Acti</td>";
                         $detalle .= "<td style=\"padding:1px 3px;border: 1px solid #dedede;\">$Year</td>";
                         $detalle .= "<td style=\"padding:1px 3px;border: 1px solid #dedede;\">$Make</td>";
                         $detalle .= "<td style=\"padding:1px 3px;border: 1px solid #dedede;\">$VIN</td>";
                         $detalle .= "<td style=\"padding:1px 3px;border: 1px solid #dedede;\">$Radius</td>";
                         $detalle .= "<td style=\"padding:1px 3px;border: 1px solid #dedede;\">$Peso</td>";
                         $detalle .= "<td style=\"padding:1px 3px;border: 1px solid #dedede;\">$type</td>";
                         $detalle .= "<td style=\"padding:1px 3px;border: 1px solid #dedede;\" class=\"txt-r\">$PDAmount</td>";
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
                 if(!($success)){$transaccion_exitosa = false;$mensaje = "The data was not updated properly, please try again."; }
                 
                 //Incrementamos contador para verificar aprobacion:
                 if($eStatusP == "A"){
                     
                     $iAprobacion++;
                     $validaAccion = set_endoso_poliza($iConsecutivo,$polizaID,$conexion);
                    
                     if(!($validaAccion)){$transaccion_exitosa=false;$mensaje="The policy data was not updated properly, please try again.";}
                     else{
                        $mensaje = "The data has been saved successfully and the vehicle has been updated in the company policy. <br>Thank you!"; 
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
              $query = "UPDATE cb_endoso SET eStatus = 'SB',dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."', sUsuarioActualizacion='".$_SESSION['usuario_actual']."' ".
                       "WHERE iConsecutivo = '$iConsecutivo'"; 
              $conexion->query($query);
              if($conexion->affected_rows < 1){$success = false;$mensaje="Error to update the endorsement status, please check with de system admin.";}     
          }
          else{$success = false;$msj=$Emails['error'];}
      }
      
      if($success){
         for($x=0;$x < $count;$x++){
              if($Emails[$x]['html']!= ""){
                      
                #UPDATE ENDORSEMENT DETAILS:
                $query = "UPDATE cb_endoso_estatus SET eStatus = 'SB', dFechaAplicacion='".date("Y-m-d H:i:s")."', dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."', sUsuarioActualizacion='".$_SESSION['usuario_actual']."' ".
                         "WHERE iConsecutivoEndoso = '$iConsecutivo' AND iConsecutivoPoliza = '".$Emails[$x]['idPoliza']."'"; 
                $conexion->query($query);
                if($conexion->affected_rows < 1){$success = false;$mensaje="Error to update the endorsement status, please check with de system admin.";}   
                
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
                    if($_SERVER["HTTP_HOST"]=="stdev.websolutionsac.com" || $_SERVER["HTTP_HOST"]=="www.stdev.websolutionsac.com"){
                      $mail->Username   = "systemsupport@solo-trucking.com";  // GMAIL username
                      $mail->Password   = "SL09100242";  
                      $mail->SetFrom('systemsupport@solo-trucking.com', 'Customer Service Solo-Trucking Insurance');
                    }else if($_SERVER["HTTP_HOST"] == "solotrucking.laredo2.net" || $_SERVER["HTTP_HOST"] == "st.websolutionsac.com" || $_SERVER["HTTP_HOST"] == "www.solo-trucking.com"){
                      $mail->Username   = "customerservice@solo-trucking.com";  // GMAIL username
                      $mail->Password   = "SL641404tK";
                      $mail->SetFrom('customerservice@solo-trucking.com', 'Customer Service Solo-Trucking Insurance');   
                    }
                    
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
                "WHERE A.iConsecutivoTipoEndoso = '1' AND A.iConsecutivo = '$iConsecutivo' ";
      $result = $conexion->query($query) or die($conexion->error);
      $rows   = $result->num_rows; 
      
      if($rows == 0){$error = '1';$mensaje = "Error to query the endorsement data, please try again later.";}
      else{
          
          $Endoso = $result->fetch_assoc();
          //REVISAMOS SI EL ENDOSO ES MULTIPLE O NO:
          if($Endoso['iEndosoMultiple'] == 0){
              
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
                 $VIN        = strtoupper($Detalle['sVIN']);
                 $Radius     = $Detalle['sRadio'];
                 $Peso       = $Detalle['sPeso'];
                 $Year       = $Detalle['iYear'];
                 
                 if($Detalle['sAliasModelo'] != ''){$Make = $Detalle['sAliasModelo'];}
                 else if($Detalle['sModelo'] != ''){$Make = $Detalle['sModelo']; }
                 
                 #CONSULTAR ARCHIVOS:
                 $file = array();
                 $Endoso["eAccion"] == 'A' ? $filtroArchivo = " AND (eArchivo ='TITLE' OR eArchivo='OTHERS')" : $filtroArchivo = " AND (eArchivo='DA' OR eArchivo='BS' OR eArchivo='NOR' OR eArchivo='PTL' OR eArchivo='OTHERS')";
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
                 }
                 /*else{
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
                 }*/
                 if(count($file)==0){$file="";} 
                 /**************/ 
                 
                 #CONSULTAMOS POLIZAS DEL ENDOSO E INFO DE LOS EMAILS:
                 $query  = "SELECT iConsecutivoEndoso,iConsecutivoPoliza,B.sNumeroPoliza,B.iTipoPoliza,D.sDescripcion AS sTipoPoliza, sMensajeEmail, A.sEmail, C.iConsecutivo AS iConsecutivoBroker, C.sName AS sBrokerName, C.bEndosoMensual ".
                           "FROM cb_endoso_estatus   AS A ".
                           "LEFT JOIN ct_polizas     AS B ON A.iConsecutivoPoliza  = B.iConsecutivo ".
                           "LEFT JOIN ct_tipo_poliza AS D ON B.iTipoPoliza = D.iConsecutivo ".
                           "LEFT JOIN ct_brokers     AS C ON B.iConsecutivoBrokers = C.iConsecutivo ".
                           "WHERE A.iConsecutivoEndoso = '$iConsecutivo' "; //"AND C.bEndosoMensual='0'"; 
                 $result = $conexion->query($query) or die($conexion->error);
                 $rows   = $result->num_rows;
                 
                 if($rows == 0){$error = '1';$mensaje = "The emails can not be generated.Please check that the endorsement has brokers to send email from this module.";}
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
                            
                            $action  = "Please add the following $UnidadTipo from policy number: $ComNombre, $sNumPoliza - $sTipoPoliza.";
                            $subject = "Endorsement application - please add the following $UnidadTipo from policy number: $ComNombre, $sNumPoliza - $sTipoPoliza";
                            
                            $bodyData = "<p style=\"color:#000;margin:5px auto; text-align:left;\">".
                                        "$Year $Make $VIN $Radius $Peso ";

                            #PDAmount
                            if($data['iTipoPoliza'] == '1' && $Endoso["iPDAmount"] != ''){$bodyData.=$PDAmount;}
                            
                            $bodyData .= "</p><br><br>";
                            
                            
                          
                        }else
                        if($Endoso["eAccion"] == 'D'){
                           $action   = "Please delete the following $UnidadTipo from policy number: $ComNombre, $sNumPoliza - $sTipoPoliza";                                                                   
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
          }
          else if($Endoso['iEndosoMultiple'] == 1){
             
             $ComNombre  = $Endoso['sNombreCompania']; 
              
             #CONSULTAR DESCRIPCION DEL ENDOSO: 
             $query  = "SELECT A.sVIN, A.eAccion,B.iYear, A.iTotalPremiumPD, C.sDescripcion AS sRadio, D.sDescripcion AS sModelo, D.sAlias AS sAliasModelo, B.sPeso ".
                       "FROM cb_endoso_unidad      AS A ".
                       "LEFT JOIN ct_unidades      AS B ON A.iConsecutivoUnidad = B.iConsecutivo ".
                       "LEFT JOIN ct_unidad_radio  AS C ON A.iConsecutivoRadio = C.iConsecutivo ".
                       "LEFT JOIN ct_unidad_modelo AS D ON B.iModelo = D.iConsecutivo ".
                       "WHERE A.iConsecutivoEndoso = '$iConsecutivo'";
             $result = $conexion->query($query) or die($conexion->error);
             $rows   = $result->num_rows; 
             
             if($rows == 0){$error = '1';$mensaje = "Error to query the endorsement description data, please try again later.";}
             else{
                 #DECLARAR ARRAY DE DETALLE:
                 $Detalle = mysql_fetch_all($result);
                 $countD  = count($Detalle);
                 
                 #CONSULTAR ARCHIVOS:
                 $file = array();
                 $Endoso["eAccion"] == 'A' ? $filtroArchivo = " AND (eArchivo ='TITLE' OR eArchivo='OTHERS')" : $filtroArchivo = " AND (eArchivo='DA' OR eArchivo='BS' OR eArchivo='NOR' OR eArchivo='PTL' OR eArchivo='OTHERS')";
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
                        $sMensaje   = $data['sMensajeEmail'];
                        $sNumPoliza = $data['sNumeroPoliza'];
                        $sEmails    = $data['sEmail'];
                        $sBrokerName= $data['sBrokerName'];
                        $sTipoPoliza= $data['sTipoPoliza'];
                        $idPoliza   = $data['iConsecutivoPoliza'];
                        $tipoPoliza = get_policy_type($data['iTipoPoliza']);
                        
                        $data['sMensajeEmail'] != "" ?  $message = $data['sMensajeEmail'] : $message = "Please do the following in vehicles from policy: ";
                        
                        #DATOS DEL CORREO:
                        $action   = $message."$ComNombre, $sNumPoliza - $sTipoPoliza.";
                        $subject  = "$ComNombre//$sNumPoliza - $sTipoPoliza. Endorsement application - ".$message;
                        $bodyData = "<table cellspacing=\"0\" cellpadding=\"0\" style=\"color:#000;margin:5px auto; text-align:left;float:left;min-width:300px;\">";
                        $detalle  = "";
                        //Recorremos array de DETALLE:
                        for($x=0;$x<$countD;$x++){
                     
                             if($Detalle[$x]['eAccion'] == "ADDSWAP"){$Detalle[$x]['eAccion'] = "ADD SWAP";}
                             if($Detalle[$x]['eAccion'] == "DELETESWAP"){$Detalle[$x]['eAccion'] = "DELETE SWAP";}
                             
                             $Acti = $Detalle[$x]['eAccion'];
                             $Year = " ".$Detalle[$x]['iYear'];
                             $Detalle[$x]['sAliasModelo'] != '' ? $Make = " ".$Detalle[$x]['sAliasModelo'] : $Make = " ".$Detalle[$x]['sModelo']; 
                             $VIN  = " ".$Detalle[$x]['sVIN'];
                             $Detalle[$x]['sRadio'] !=  '' ? $Radius = " ".$Detalle[$x]['sRadio'] : $Radius = "";
                             $Peso = " ".$Detalle['sPeso'];
                             
                             $data['iTipoPoliza'] == '1' && $Detalle[$x]["iTotalPremiumPD"] > 0 ? $PDAmount = number_format($Detalle[$x]["iTotalPremiumPD"],2,'.','') : $PDAmount = "";
                             
                             $detalle .= "<tr>";
                             $detalle .= "<td style=\"padding:1px 3px;\">$Acti</td>";
                             $detalle .= "<td style=\"padding:1px 3px;\">$Year</td>";
                             $detalle .= "<td style=\"padding:1px 3px;\">$Make</td>";
                             $detalle .= "<td style=\"padding:1px 3px;\">$VIN</td>";
                             $detalle .= "<td style=\"padding:1px 3px;\">$Radius</td>";
                             $detalle .= "<td style=\"padding:1px 3px;\">$Peso</td>";
                             $detalle .= "<td style=\"padding:1px 3px;\">$PDAmount</td>";
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
                         "(SELECT iConsecutivo, sTipoArchivo, sNombreArchivo, iTamanioArchivo,eArchivo FROM cb_unidad_files WHERE iConsecutivoUnidad = '".$endoso['iConsecutivoUnidad']."' $ordenQuery)";    
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
  
  #FUNCION PARA APLICAR ACCION DEL ENDOSO EN UNIDAD/POLIZA:
  function set_endoso_poliza($iConsecutivoEndoso,$iConsecutivoPoliza,$conexion){
    
      $transaccion_exitosa = true;
      
      #CONSULTAR DATOS DEL ENDOSO:
      $query  = "SELECT * FROM cb_endoso WHERE iConsecutivo='$iConsecutivoEndoso'"; 
      $result = $conexion->query($query); 
      $rows   = $result->num_rows; 
      
      if($rows > 0){
          $data         = $result->fetch_assoc();
          $dFechaEndoso = $data['dFechaAplicacion'];
          #ENDOSOS NO MULTIPLES:
          if($data['iEndosoMultiple']==0){
              //Tomamos variables:
              $eAccion   = $data['eAccion'];
              $idDetalle = $data['iConsecutivoUnidad']; 
              
              //verificamos si ya existe el registro en la tabla:
              $query = "SELECT COUNT(iConsecutivoUnidad) AS total FROM cb_poliza_unidad WHERE iConsecutivoPoliza='$iConsecutivoPoliza' AND iConsecutivoUnidad='$idDetalle'";
              $result= $conexion->query($query) or die($conexion->error);
              $valida= $result->fetch_assoc();
              
              // UPDATE REGISTRO
              if($valida['total'] != 0){
                    if($eAccion == "A"){
                        //Agregar registro a la tabla de relacion: 
                        $query   = "UPDATE cb_poliza_unidad SET iDeleted='0',dFechaActualizacion='$dFechaEndoso',sIPActualizacion='".$_SERVER['REMOTE_ADDR']."',sUsuarioActualizacion='".$_SESSION['usuario_actual']."'".
                                   "WHERE iConsecutivoPoliza='$iConsecutivoPoliza' AND iConsecutivoUnidad='$idDetalle'";
                        $success = $conexion->query($query);     
                    }
                    else if($eAccion == "D"){
                        //Agregar registro a la tabla de relacion: 
                        $query   = "UPDATE cb_poliza_unidad SET iDeleted='1',dFechaActualizacion='$dFechaEndoso',sIPActualizacion='".$_SERVER['REMOTE_ADDR']."',sUsuarioActualizacion='".$_SESSION['usuario_actual']."'".
                                   "WHERE iConsecutivoPoliza='$iConsecutivoPoliza' AND iConsecutivoUnidad='$idDetalle'";
                        $success = $conexion->query($query); 
                    }    
              }
              // INSERT REGISTRO
              else{
                  if($eAccion == "A"){
                        //Agregar registro a la tabla de relacion: 
                        $query   = "INSERT INTO cb_poliza_unidad (iConsecutivoPoliza,iConsecutivoUnidad,eModoIngreso,dFechaIngreso,sIPIngreso,sUsuarioIngreso) ".
                                   "VALUES('$iConsecutivoPoliza','$idDetalle','ENDORSEMENT','$dFechaEndoso','".$_SERVER['REMOTE_ADDR']."','".$_SESSION['usuario_actual']."')";
                        $success = $conexion->query($query);         
                  }
                  else if($eAccion == "D"){
                        //Agregar registro a la tabla de relacion: 
                        $query   = "INSERT INTO cb_poliza_unidad (iConsecutivoPoliza,iConsecutivoUnidad,eModoIngreso,dFechaIngreso,sIPIngreso,sUsuarioIngreso,iDeleted) ".
                                   "VALUES('$iConsecutivoPoliza','$idDetalle','ENDORSEMENT','$dFechaEndoso','".$_SERVER['REMOTE_ADDR']."','".$_SESSION['usuario_actual']."','1')";
                        $success = $conexion->query($query);     
                  }  
              }
              if(!($success)){$transaccion_exitosa = false;}
              // Actualizamos el registro general del vehiculo como "no eliminado".
              if($eAccion == "A"){
                 $query   = "UPDATE ct_unidades SET iDeleted='0' WHERE iConsecutivo='$idDetalle'";
                 $success = $conexion->query($query);
                 if(!($success)){$transaccion_exitosa = false;} 
              }
              else if($eAccion == "D"){
                  //CONSULTAMOS, SI EL VEHICULO NO ESTA ACTUALMENTE EN NINGUNA POLIZA, LO MARCAREMOS COMO ELIMINADA EN EL CATALOGO:
                  $query = "SELECT COUNT(A.iConsecutivo) AS total ".
                           "FROM ct_unidades AS A INNER JOIN cb_poliza_unidad AS B ON A.iConsecutivo = B.iConsecutivoUnidad ".
                           "WHERE A.iConsecutivo = '$idDetalle'";
                  $r     = $conexion->query($query);
                  $valid = $r->fetch_assoc();
                  $valid['total'] > 0 ? $iElimina = false : $iElimina = true;
                      
                  if($iElimina){
                    $query   = "UPDATE ct_unidades SET iDeleted = '1' WHERE iConsecutivo='$idDetalle'";
                    $success = $conexion->query($query); 
                    if(!($success)){$error = '1'; $mensaje = "Error to try update data, please try again later.";}
                  }
              }
              
          }
          else if($data['iEndosoMultiple']==1){
              //Consultamos las unidades relacionadas al endoso:
              $query  = "SELECT * FROM cb_endoso_unidad WHERE iConsecutivoEndoso = '$iConsecutivoEndoso' "; 
              $result = $conexion->query($query); 
              $rows   = $result->num_rows; 
              if($rows > 0){
                  //Recorremos resultado:
                  while ($item = $result->fetch_assoc()) { 
                      //Tomamos variables:
                      $eAccion   = $item['eAccion'];
                      $idDetalle = $item['iConsecutivoUnidad']; 
                      
                      //verificamos si ya existe el registro en la tabla:
                      $query = "SELECT COUNT(iConsecutivoUnidad) AS total FROM cb_poliza_unidad WHERE iConsecutivoPoliza='$iConsecutivoPoliza' AND iConsecutivoUnidad='$idDetalle'";
                      $result= $conexion->query($query) or die($conexion->error);
                      $valida= $result->fetch_assoc();
                      
                      // UPDATE REGISTRO
                      if($valida['total'] != 0){
                        if($eAccion == "ADD" || $eAccion == "ADDSWAP"){
                            //Agregar registro a la tabla de relacion: 
                            $query   = "UPDATE cb_poliza_unidad SET iDeleted='0',dFechaActualizacion='$dFechaEndoso',sIPActualizacion='".$_SERVER['REMOTE_ADDR']."',sUsuarioActualizacion='".$_SESSION['usuario_actual']."' ".
                                       "WHERE iConsecutivoPoliza='$iConsecutivoPoliza' AND iConsecutivoUnidad='$idDetalle'";
                            $success = $conexion->query($query);     
                        }
                        else if($eAccion == "DELETE" || $eAccion == "DELETESWAP"){
                            //Agregar registro a la tabla de relacion: 
                            $query   = "UPDATE cb_poliza_unidad SET iDeleted='1',dFechaActualizacion='$dFechaEndoso',sIPActualizacion='".$_SERVER['REMOTE_ADDR']."',sUsuarioActualizacion='".$_SESSION['usuario_actual']."' ".
                                       "WHERE iConsecutivoPoliza='$iConsecutivoPoliza' AND iConsecutivoUnidad='$idDetalle'";
                            $success = $conexion->query($query); 
                        }        
                      }
                      // INSERT REGISTRO
                      else{
                        if($eAccion == "ADD" || $eAccion == "ADDSWAP"){
                            //Agregar registro a la tabla de relacion: 
                            $query   = "INSERT INTO cb_poliza_unidad (iConsecutivoPoliza,iConsecutivoUnidad,eModoIngreso,dFechaIngreso,sIPIngreso,sUsuarioIngreso) ".
                                       "VALUES('$iConsecutivoPoliza','$idDetalle','ENDORSEMENT','$dFechaEndoso','".$_SERVER['REMOTE_ADDR']."','".$_SESSION['usuario_actual']."')";
                            $success = $conexion->query($query);         
                        }
                        else if($eAccion == "DELETE" || $eAccion == "DELETESWAP"){
                            //Agregar registro a la tabla de relacion: 
                            $query   = "INSERT INTO cb_poliza_unidad (iConsecutivoPoliza,iConsecutivoUnidad,eModoIngreso,dFechaIngreso,sIPIngreso,sUsuarioIngreso,iDeleted) ".
                                       "VALUES('$iConsecutivoPoliza','$idDetalle','ENDORSEMENT','$dFechaEndoso','".$_SERVER['REMOTE_ADDR']."','".$_SESSION['usuario_actual']."','1')";
                            $success = $conexion->query($query);     
                        }       
                      }
                      if(!($success)){$transaccion_exitosa = false;}
                      
                      // Actualizamos el registro general del vehiculo como "no eliminado".
                      if($eAccion == "ADD" || $eAccion == "ADDSWAP"){
                         $query   = "UPDATE ct_unidades SET iDeleted='0' $iRadio,iTotalPremiumPD='$iTotalPD' WHERE iConsecutivo='$idDetalle'";
                         $success = $conexion->query($query);
                         if(!($success)){$transaccion_exitosa = false;} 
                      }
                      else if($eAccion == "DELETE" || $eAccion == "DELETESWAP"){
                          //CONSULTAMOS, SI EL VEHICULO NO ESTA ACTUALMENTE EN NINGUNA POLIZA, LO MARCAREMOS COMO ELIMINADA EN EL CATALOGO:
                          $query = "SELECT COUNT(A.iConsecutivo) AS total ".
                                   "FROM ct_unidades AS A INNER JOIN cb_poliza_unidad AS B ON A.iConsecutivo = B.iConsecutivoUnidad ".
                                   "WHERE A.iConsecutivo = '$idDetalle'";
                          $r     = $conexion->query($query);
                          $valid = $r->fetch_assoc();
                          $valid['total'] > 0 ? $iElimina = false : $iElimina = true;
                              
                          if($iElimina){
                            $query   = "UPDATE ct_unidades SET iDeleted = '1' WHERE iConsecutivo='$idDetalle'";
                            $success = $conexion->query($query); 
                            if(!($success)){$error = '1'; $mensaje = "Error to try update data, please try again later.";}
                          }
                      }
                       
                  }
              }
              else{$transaccion_exitosa = false;}
          }
      }
      else{$transaccion_exitosa = false;}
   
      return $transaccion_exitosa;
      
  }
  
?>
