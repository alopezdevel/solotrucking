<?php
  session_set_cookie_params(86400); ini_set('session.gc_maxlifetime', 86400);   
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
     $filtroQuery   = " WHERE A.eStatus = 'A' AND sNumeroEndosoBroker != '' AND D.iDeleted = '0' AND B.iConsecutivoTipoEndoso = '1' ";
     $array_filtros = explode(",",$_POST["filtroInformacion"]);
     
     foreach($array_filtros as $key => $valor){
        if($array_filtros[$key] != ""){
            $campo_valor = explode("|",$array_filtros[$key]);
            
            if($campo_valor[0] == 'B.dFechaAplicacion'){ 
                $campo_valor[1] = date('Y-m-d',strtotime(trim($campo_valor[1])));
                $filtroQuery   .= " AND  ".$campo_valor[0]."='".$campo_valor[1]."' ";
            }
            else if($campo_valor[0] == 'eStatus'){
                     $filtroQuery .= " AND  ".$campo_valor[0]." = '".$campo_valor[1]."'";
            }
            else{
                 $filtroQuery.= " AND  ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'";
            }   
            
        }
     }
     // ordenamiento//
     $ordenQuery = " ORDER BY ".$_POST["ordenInformacion"]." ".$_POST["sortInformacion"];

    //contando registros // 
    $query_rows = "SELECT COUNT(A.iConsecutivoEndoso) AS total ".
                  "FROM       cb_endoso_estatus AS A
                   INNER JOIN cb_endoso         AS B ON A.iConsecutivoEndoso   = B.iConsecutivo
                   LEFT  JOIN ct_polizas        AS C ON A.iConsecutivoPoliza   = C.iConsecutivo
                   INNER JOIN ct_companias      AS D ON B.iConsecutivoCompania = D.iConsecutivo
                   LEFT  JOIN ct_tipo_poliza    AS E ON C.iTipoPoliza          = E.iConsecutivo 
                   LEFT JOIN  cb_invoices       AS F ON A.iConsecutivoInvoice  = F.iConsecutivo ".$filtroQuery; 
    $Result = $conexion->query($query_rows);
    $items = $Result->fetch_assoc();
    $registros = $items["total"];
    if($registros == "0"){$pagina_actual = 0;}
    $paginas_total = ceil($registros / $registros_por_pagina);
    
    if($registros == "0"){
        $limite_superior = 0;
        $limite_inferior = 0;
        $htmlTabla       ="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";
    }else{
      $pagina_actual == "0" ? $pagina_actual = 1 : false;
      $limite_superior = $registros_por_pagina;
      $limite_inferior = ($pagina_actual*$registros_por_pagina)-$registros_por_pagina;
      $sql = "SELECT A.iConsecutivoEndoso,B.iConsecutivoCompania, D.sNombreCompania, A.iConsecutivoPoliza, C.sNumeroPoliza, E.sDescripcion, A.eStatus, A.sNumeroEndosoBroker, A.rImporteEndosoBroker, DATE_FORMAT(B.dFechaAplicacion,'%m/%d/%Y') AS dFechaAplicacion, ".
             "D.iOnRedList, E.sAlias, F.iFinanciamiento,A.iConsecutivoInvoice, F.dTotal, F.sCveMoneda, F.eStatus, B.iEndosoMultiple ".
             "FROM       cb_endoso_estatus AS A
              INNER JOIN cb_endoso         AS B ON A.iConsecutivoEndoso   = B.iConsecutivo
              LEFT  JOIN ct_polizas        AS C ON A.iConsecutivoPoliza   = C.iConsecutivo
              INNER JOIN ct_companias      AS D ON B.iConsecutivoCompania = D.iConsecutivo
              LEFT  JOIN ct_tipo_poliza    AS E ON C.iTipoPoliza          = E.iConsecutivo 
              LEFT JOIN  cb_invoices       AS F ON A.iConsecutivoInvoice  = F.iConsecutivo ".
             $filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
      $result = $conexion->query($sql);
      $rows = $result->num_rows; 
         
        if ($rows > 0) {    
            while ($items = $result->fetch_assoc()) { 
              
                     $btn_confirm  = "";
                     $titleEstatus = "";
                     $color_action = "";
                     $action       = "";
                     $detalle      = "";
                     $estado       = "";
                     $class        = "";
                     /*if($items['iEndosoMultiple'] == "0"){
                         switch($items["eAccion"]){
                             case 'A': $action = 'ADD'; break;
                             case 'D': $action = 'DELETE'; break;
                         }
                         $detalle = "<table style=\"width:100%;padding:0!important;margin:0!important;\">";
                         $detalle.= "<tr style='background: none;'>".
                                    "<td style='border: 0;width:120px;padding: 0!important;'>".$action."</td>".
                                    "<td style='border: 0;padding: 0!important;'>".strtoupper($items['sVIN'])."</td>".
                                    "</tr>";
                         $detalle.= "</table>";
                     }*/
                     if($items['iEndosoMultiple'] == "1"){
                         #CONSULTAR DETALLE DEL ENDOSO:
                         $query = "SELECT A.sVIN, (CASE 
                                    WHEN A.eAccion = 'ADDSWAP'    THEN 'ADD SWAP'
                                    WHEN A.eAccion = 'DELETESWAP' THEN 'DELETE SWAP'
                                    WHEN A.eAccion = 'CHANGEPD'   THEN 'CHANGE PD'
                                    ELSE A.eAccion
                                    END) AS eAccion FROM cb_endoso_unidad AS A WHERE A.iConsecutivoEndoso = '".$items['iConsecutivoEndoso']."' ORDER BY sVIN ASC";
                         $r     = $conexion->query($query);
                         
                         $detalle     = "<table style=\"width:100%;padding:0!important;margin:0!important;border-collapse: collapse;\">";
                         $description = "";
                         
                         while($item = $r->fetch_assoc()){
                            $description .= "<tr style='background: none;'>".
                                            "<td style='border: 0;width:120px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['eAccion']."</td>".
                                            "<td style='border: 0;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['sVIN']."</td>".
                                            "</tr>"; 
                         }
                         $detalle .= $description."</table>";
                     } 
                     
                     // Revisar fecha limite de factuacion:
                     
                     
                     if($items['iConsecutivoInvoice'] == ''){
                         $estado      = '<i class="fa fa-circle-o icon-estatus" style=\"margin-right: -5px;\"></i><span style="font-size: 10px;position: relative; top: 2px;">NO BILLED</span>';
                         $titleEstatus= "The invoice the has not been created.";
                         //$class       = "class = \"blue\"";
                         $btn_confirm = '<div class="btn-icon btn-left btn-green btn_add" title="Add Invoice"><i class="fa fa-plus"></i></div>';
                     }
                     else{
                         switch($items['eStatus']){
                             case 'EDITABLE' : 
                                $estado      = '<i class="fa fa-circle-o icon-estatus" style=\"margin-right: -5px;\"></i><span style="font-size: 10px;position: relative; top: 2px;">BILL NOT APPLIED</span>';
                                $titleEstatus= "The invoice has been created but not sent/applied, you can edit it.";
                                //$class       = "class = \"blue\"";
                                $btn_confirm = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"View and Edit Endorsement Status\"><i class=\"fa fa-pencil-square-o\"></i></div>";
                                               //"<div class=\"btn_edit_estatus btn-icon send-email btn-left\" title=\"Send e-mail to the brokers\"><i class=\"fa fa-envelope\"></i></div>".
                                               //"<div class=\"btn_delete btn-icon trash btn-left\" title=\"Delete Endorsement\"><i class=\"fa fa-trash\"></i> <span></span></div>";
                             break;
                         }
                     }
                     
                     
                      //Redlist:
                     $items['iOnRedList'] == '1' ? $redlist_icon = "<i class=\"fa fa-star\" style=\"color:#e8051b;margin-right:4px;\"></i>" : $redlist_icon = ""; 
                     
                     //Revisar si esta marcado como enviado y se envio fuera del sistema:
                     $iEnviadoFuera == 1 ? $txtFechaApp = "<span style=\"font-size:9px;display:block;\">Mark As Sent</span>".$items['dFechaAplicacion'] : $txtFechaApp = $items['dFechaAplicacion'];
                 
                     //strlen($items['sDescripcion']) > 30 ? $polizaDesc = substr($items['sDescripcion'],0,30) : $polizaDesc = $items['sDescripcion'];
                     $items['iConsecutivoInvoice'] != "" ? $total = "\$ ".$items['dTotal']." ".$items['sCveMoneda'] : $total = "";
                     
                     $htmlTabla .= "<tr $class>".
                                   "<td id=\"iCve_".$items['iConsecutivoCompania']."\">".$redlist_icon.$items['sNombreCompania']."</td>".
                                   "<td id=\"iPol_".$items['iConsecutivoPoliza']."\" title=\"".$items['sDescripcion']."\">".$items['sNumeroPoliza']." / ".$items['sAlias']."</td>". 
                                   "<td id=\"iEnd_".$items['iConsecutivoEndoso']."\" class=\"text-center\">".$items['sNumeroEndosoBroker']."</td>". 
                                   "<td>".$detalle."</td>".
                                   "<td class=\"text-center\">".$items['dFechaAplicacion']."</td>".
                                   "<td title='$titleEstatus'>".$estado."</td>". 
                                   "<td class=\"text-right\">".$total."</td>". 
                                   "<td>".$btn_confirm."</td></tr>";
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
  
  function save_data(){
      
      $error   = '0'; 
      $valores = array();
      $campos  = array(); 
      $msj     = "";
      
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $_POST["iConsecutivo"] == "" ? $edit_mode= 'false' : $edit_mode = 'true';
      $_POST['dFechaInvoice'] =  date('Y-m-d',strtotime(trim($_POST['dFechaInvoice'])));
  
      $valid_user = valid_user($_SESSION['usuario_actual']);

      if(!($valid_user)){
          $error = '1';
          $msj   = "This user does not have the privileges to modify or add data to the system.";
      }
      
      # Archivo
      if($error == '0' && isset($_FILES['file-0'])){
          
          $file        = fopen($_FILES['file-0']["tmp_name"], 'r'); 
          $fileContent = fread($file, filesize($_FILES['file-0']["tmp_name"]));
          $fileName    = $_FILES['file-0']['name'];
          $fileType    = $_FILES['file-0']['type']; 
          $fileTmpName = $_FILES['file-0']['tmp_name']; 
          $fileSize    = $_FILES['file-0']['size']; 
          $fileError   = $_FILES['file-0']['error'];
          $fileExten   = explode(".",$fileName);
          
          //Validando nombre del archivo sin puntos...
          if(count($fileExten) != 2){$error = '1';$msj = "Error: Please check that the name of the file should not contain points.";}
          else{
              //Extension Valida:
              $fileExten = strtolower($fileExten[1]);
              if($fileExten != "pdf" && $fileExten != "jpg" && $fileExten != "jpeg" && $fileExten != "png" && $fileExten != "doc" && $fileExten != "docx" && $fileExten != "xlsx" && $fileExten != "xls" && $fileExten != "zip" && $fileExten != "ppt" && $fileExten != "pptx"){
                  $error = '1'; $msj="Error: The file extension is not valid, please check it.";
              }
              else{
                  //Verificar Tamaño:
                  if($fileSize > 0  && $fileError == 0){
                      #CONVERT FILE VAR TO POST ARRAY:
                      $_POST['hContenidoDocumentoDigitalizado'] = $conexion->real_escape_string($fileContent); //Contenido del archivo 
                      $_POST['sTipoArchivo']    = $fileType;
                      $_POST['iTamanioArchivo'] = $fileSize;
                      $_POST['sNombreArchivo']  = $fileName;
                  }
                  else{$error = '1';$msj = "Error: The file you are trying to upload is empty or corrupt, please check it and try again.";}
              }
          }
          
      }
      
      if($error == '0'){
          //Validar que la referencia no este repetida:
          $query  = "SELECT COUNT(iConsecutivo) AS total FROM cb_invoices WHERE sNoReferencia ='".$_POST['sNoReferencia']."' AND bEliminado='0'";
          $result = $conexion->query($query);
          $valida = $result->fetch_assoc();
          
          if($valida['total'] != '0'){
              if($edit_mode != 'true'){
                  $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>
                          Error: The Reference that you trying to add already exists. Please verify the data.</p>';
                  $error = '1';
              }else{
                 foreach($_POST as $campo => $valor){
                    if($campo != "accion" && $campo != "edit_mode" && $campo != "iConsecutivo" && $campo != 'iConsecutivoEndoso' && $campo != "iConsecutivoPoliza"){ //Estos campos no se insertan a la tabla
                        array_push($valores,"$campo='".trim($valor)."'");
                    }
                 }   
              }
          }
          else if($edit_mode != 'true'){
             foreach($_POST as $campo => $valor){
               if($campo != "accion" && $campo != "edit_mode" && $campo != "iConsecutivo" && $campo != 'iConsecutivoEndoso' && $campo != "iConsecutivoPoliza"){ //Estos campos no se insertan a la tabla
                    array_push($campos ,$campo); 
                    array_push($valores, trim($valor));
               }
             }  
          }
      }
      
      if($error == '0'){
          
          //GET CLIENTE DATOS:
          $query  = "SELECT sNombreCompania AS sReceptorNombre, CONCAT(sDireccion,', ',sCiudad,' ',sEstado,' ',sCodigoPostal) AS sReceptorDireccion ".
                    "FROM ct_companias WHERE iConsecutivo = '".trim($_POST['iConsecutivoCompania'])."'";
          $result = $conexion->query($query);
          
          if($result->num_rows > 0){
             $items = $result->fetch_assoc(); 
             if($edit_mode == 'true'){
                array_push($valores,"sReceptorNombre='".trim($items['sReceptorNombre'])."'"); 
                array_push($valores,"sReceptorDireccion='".trim($items['sReceptorDireccion'])."'");
             }else{
                array_push($campos ,'sReceptorNombre');    array_push($valores, trim($items['sReceptorNombre'])); 
                array_push($campos ,'sReceptorDireccion'); array_push($valores, trim($items['sReceptorDireccion']));
             }
          }
          
          if($edit_mode == 'true'){
              
            array_push($valores ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
            array_push($valores ,"sIP='".$_SERVER['REMOTE_ADDR']."'");
            array_push($valores ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'");
            
            $sql = "UPDATE cb_invoices SET ".implode(",",$valores)." WHERE iConsecutivo = '".$_POST['iConsecutivo']."'";
            $conexion->query($sql);
            
            if($conexion->affected_rows < 0){$transaccion_exitosa = false;$msj = "Error update invoice data, please try again.";$error='1';}
            else{$idFactura = $_POST['iConsecutivo']; $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The data has been updated successfully.</p>';}
            
          }
          else{
            array_push($campos ,"dFechaIngreso");
            array_push($valores ,date("Y-m-d H:i:s"));
            array_push($campos ,"sIP");
            array_push($valores ,$_SERVER['REMOTE_ADDR']);
            array_push($campos ,"sUsuarioIngreso");
            array_push($valores ,$_SESSION['usuario_actual']);
            
            $sql = "INSERT INTO cb_invoices (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')"; 
            $conexion->query($sql);
            
            if($conexion->affected_rows < 1){$transaccion_exitosa = false;$msj = "Error saving invoice data, please try again.";$error = '1';}
            else{$idFactura = $conexion->insert_id; $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The data has been saved successfully!</p>';}
            
            //ACTUALIZAR DATOS DEL ENDOSO:
            if($transaccion_exitosa && $error == '0'){
                $query = "UPDATE cb_endoso_estatus SET iConsecutivoInvoice='".$idFactura."' WHERE iConsecutivoEndoso='".$_POST['iConsecutivoEndoso']."' AND iConsecutivoPoliza='".$_POST['iConsecutivoPoliza']."'"; 
                $conexion->query($query);  
                
                if($conexion->affected_rows == 0){$error = '1'; $msj = "Error to update the endorsement data to link the invoice, please try again.";}
                else{
                    $query = "SELECT iConsecutivo AS iConsecutivoServicio, sClave, sDescripcion, sCveUnidadMedida, iPrecioUnitario, iPctImpuesto* FROM ct_productos_servicios WHERE sDescripcion LIKE '%ENDORSEMENT%' LIMIT 1"; 
                    $res   = $conexion->query($query);
                    
                    if($res->num_rows > 0){
                        $service = $res->fetch_assoc();
                        //Agregar registro automatico para ligar endoso desde la factura:
                        $campos_endoso = array();
                        $valores_endoso= array();
                        
                        foreach($service as $campo => $valor){
                            array_push($campos_endoso ,$campo); 
                            array_push($valores_endoso, trim($valor));
                        } 
                        
                        //Calcular precio extendido:
                        if($service['iPctImpuesto'] > 0){
                            $iImpuesto       = number_format(($servicio['iPrecioUnitario']*(($service['iPctImpuesto']/100))),2,'.',''); 
                            $precioExtendido = number_format(($servicio['iPrecioUnitario']+$iImpuesto),2,'.','');       
                        }
                        else{
                            $precioExtendido = 0;
                            $iImpuesto       = 0;
                        }
                        
                        //Campos adicionales:
                        array_push($campos_endoso ,"iCantidad");            array_push($valores_endoso ,'1');
                        array_push($campos_endoso ,"iConsecutivoInvoice");  array_push($valores_endoso ,"'".$idFactura."'");
                        array_push($campos_endoso ,"iEndorsementsApply");   array_push($valores_endoso ,'1');
                        array_push($campos_endoso ,"iMostrarEndorsements"); array_push($valores_endoso ,'1');
                        array_push($campos_endoso ,"iImpuesto");            array_push($valores_endoso ,$iImpuesto);
                        array_push($campos_endoso ,"iPrecioExtendido");     array_push($valores_endoso ,$precioExtendido);
                        array_push($campos_endoso ,"dFechaIngreso");        array_push($valores_endoso ,date("Y-m-d H:i:s"));
                        array_push($campos_endoso ,"sIP");                  array_push($valores_endoso ,$_SERVER['REMOTE_ADDR']);
                        array_push($campos_endoso ,"sUsuarioIngreso");      array_push($valores_endoso ,$_SESSION['usuario_actual']);
                        
                        $query = "INSET INTO cb_invoice_detalle (".implode(",",$campos_endoso).") VALUES ('".implode("','",$valores_endoso)."')";
                        $conexion->query($query);  
                
                        if($conexion->affected_rows == 0){$error = '1'; $msj = "Error to add the endorsement summary data to link the invoice, please try again.";}
                        else{
                            $idDetalle = $conexion->insert_id;
                            $query = "INSET INTO cb_invoice_detalle_endoso (iConsecutivoDetalle,iConsecutivoEndoso) VALUES ('".$idDetalle."','".$_POST['iConsecutivoEndoso']."')";
                            $conexion->query($query); 
                            if($conexion->affected_rows == 0){$error = '1'; $msj = "Error to update the endorsement summary data to link the invoice, please try again.";}   
                        }
                    }   
                } 
            }
            
          }
          
          
      }
      
      $transaccion_exitosa && $error == '0' ? $conexion->commit() :$conexion->rollback();
      $conexion->close();
      
      $response = array("error"=>"$error","msj"=>"$msj","idFactura"=>"$idFactura");
      echo json_encode($response);
  }
?>
