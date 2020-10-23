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
     $filtroQuery   = " WHERE A.eStatus = 'A' AND sNumeroEndosoBroker != '' AND D.iDeleted = '0' AND B.iConsecutivoTipoEndoso = '1' ".
                      "AND C.iDeleted = '0' AND C.dFechaCaducidad >= CURDATE() ";
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
             "D.iOnRedList, E.sAlias, F.iFinanciamiento,A.iConsecutivoInvoice, F.dTotal, F.sCveMoneda, F.eStatus, B.iEndosoMultiple, iConsecutivoFinanciera, sDiasFinanciamiento, dFinanciamientoMonto, iFinanciamiento,".
             "DATE_FORMAT(F.dFechaInvoice,'%m/%d/%Y') AS dFechaInvoice, F.dFechaInvoice AS FechaInvoiceFormat ".
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
                     $FinancingData= "---";
                     
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
                         
                         $description = "";   
                         $count       = 0;
                         $descTitle   = "";
                         
                         while($item = $r->fetch_assoc()){
                            
                            if($count == 0){
                                $firstA = $item['eAccion'];
                                $firstV = $item['sVIN'];
                                $description = "<tr style='background: none;'>".
                                                "<td style='border: 0;width:105px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['eAccion']."</td>".
                                                "<td style='border: 0;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['sVIN']."</td>".
                                                "<td style='border: 0;width: 70px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$items['dFechaAplicacion']."</td>".
                                                "</tr>";                    
                            }
                            else{
                                if($count == 1){
                                    $description = "<tr style='background: none;'>".
                                                    "<td style='border: 0;width:105px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$firstA."</td>".
                                                    "<td style='border: 0;padding: 0!important;min-height: auto!important;height:auto!important;'>".$firstV."... </td>".
                                                    "<td style='border: 0;width: 70px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$items['dFechaAplicacion']."</td>".
                                                    "</tr>"; 
                                }
                            } 
                            $descTitle == "" ? $descTitle .= $item['eAccion']." ".$item['sVIN'] : $descTitle.= "\n".$item['eAccion']." ".$item['sVIN'];
                            $count ++;
                         }
                         $detalle  = "<table title=\"$descTitle\" style=\"width:100%;padding:0!important;margin:0!important;border-collapse: collapse;\">";
                         $detalle .= $description."</table>";
                     } 
                     
                     // Revisar fecha limite de factuacion:
                     
                     
                     if($items['iConsecutivoInvoice'] == ''){
                         $estado      = '<i class="fa fa-circle-o icon-estatus" style=\"margin-right: -5px;\"></i><span style="font-size: 10px;position: relative; top: 2px;">NO BILLED</span>';
                         $titleEstatus= "The invoice the has not been created.";
                         $btn_confirm = '<div class="btn-icon btn-left btn-green btn_add" title="Add Invoice"><i class="fa fa-plus"></i></div>';
                         $invoiceID   = '';
                     }
                     else{
                         $invoiceID = "invID_".$items['iConsecutivoInvoice'];
                         
                         if($items['iFinanciamiento'] == "0"){
                            $FinancingData = 'N/A';  
                            //Validar fecha limite de pago para determinar el estatus:
                            $fechaLimite   = sumar_dias_fecha($items['dFechaAplicacion'],10);   
                         }
                         else{
                            $FinancingData = "<span style=\"font-weight: 700;\">".$items['sDiasFinanciamiento']." months</span>";
                            $FinancingData.= "<br><span style=\"font-size: 10px;opacity: 0.9;\">\$ ".$items['dFinanciamientoMonto']." ".$items['sCveMoneda']."</span>"; 
                        
                            // Calcular fecha de vencimiento:
                            $fecha_factura= $items['FechaInvoiceFormat'];
                            $today        = new DateTime($fecha_factura);            
                            $date         = $today->modify('+'.$items['sDiasFinanciamiento'].' months');
                            $fechaLimite  = $date->format('m/d/Y');
                         
                         }
                          
                         if($items['eStatus'] == 'EDITABLE'){
                            $estado      = '<i class="fa fa-circle-o icon-estatus" style="margin-right: -5px;"></i><span style="font-size: 10px;position: relative; top: 2px;">BILL NOT APPLIED</span>';
                            $titleEstatus= "The invoice has been created but not sent/applied, you can edit it.";
                            $btn_confirm = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"View and Edit Endorsement Status\"><i class=\"fa fa-pencil-square-o\"></i></div>";
                         }
                         else if($items['eStatus'] == 'APPLIED' || $items['eStatus'] == 'SENT'){
                     
                            $validExp= verificar_vencimiento($fechaLimite);
                            if(!($validExp)){
                                $estado      = '<i class="fa fa-exclamation-circle icon-estatus" style="background-color: #ffe2e2;color: #de3636;border-color: #ffacac;position: relative;top: 3px;"></i><span style="font-size: 10px;position: relative; top: 2px;">'.$items['eStatus'].
                                               '<br><span style="font-size: 9px;position: relative;top: -5px;">Payday Limit: <b>'.$fechaLimite.'</b></span></span>';
                                $titleEstatus= "The invoice has not been paid and has exceeded the 10 day limit, please check it.";
                                $class       = "class = \"red\"";
                            }
                            else{
                                $dias = calcula_dias_diff($fechaLimite,date('m/d/Y'));
                                if($dias <= 5){
                                    $estado      = '<i class="fa fa-exclamation-triangle status-process icon-estatus" style="color: #ffba00;background-color: #ffecba;border-color: #f2d176;"></i><span style="font-size: 10px;">'.$items['eStatus'].
                                                   '<br><span style="font-size: 9px;position: relative;top: -5px;">Payday Limit: <b>'.$fechaLimite.'</b></span></span>';
                                    $titleEstatus= "The invoice has not been paid and it is close to the 10 day limit, please check it.";
                                    $class       = "class = \"orange\"";    
                                }
                                else{
                                    $estado      = '<i class="fa fa-check-circle status-success icon-estatus "></i><span style="font-size: 10px;">'.$items['eStatus'].
                                                   '<br><span style="font-size: 9px;position: relative;top: -5px;">Payday Limit: <b>'.$fechaLimite.'</b></span></span>';
                                    $titleEstatus= "The invoice has not been paid but is on time.";
                                    $class       = "class = \"green\""; 
                                }
                            }
                            
                            $btn_confirm = '<div class="btn-icon btn-left btn-green btn_add_pay" title="Add Payments"><i class="fa fa-plus"></i></div>';
                            if($items['sNombreArchivo'] != ""){
                                $btn_confirm.= "<div class=\"btn-icon btn-left pdf\" title=\"Open file\" onclick=\"window.open('open_pdf.php?idfile=".$items['iConsecutivo']."&type=invoice');\"><i class=\"fa fa-file-pdf-o\"></i><span></span></div>";
                            }
                            else{
                                $btn_confirm.= "<div class=\"btn_pdf btn-icon pdf btn-left\" title=\"Open Invoice PDF\"><i class=\"fa fa-file-pdf-o\"></i></div>";     
                            }
                            
                         }
                         
                     }
                     
                     
                      //Redlist:
                     $items['iOnRedList'] == '1' ? $redlist_icon = "<i class=\"fa fa-star\" style=\"color:#e8051b;margin-right:4px;\"></i>" : $redlist_icon = ""; 
                     
                     //Revisar si esta marcado como enviado y se envio fuera del sistema:
                     $iEnviadoFuera == 1 ? $txtFechaApp = "<span style=\"font-size:9px;display:block;\">Mark As Sent</span>".$items['dFechaAplicacion'] : $txtFechaApp = $items['dFechaAplicacion'];
                 
                     $total      = "";
                     $titletotal = "";
                     if($items['iConsecutivoInvoice'] != ""){
                        $total     = "\$ ".number_format($items['dTotal'],2,'.',',')." ".$items['sCveMoneda'];
                        $titletotal= 'title="'.number_format($items['dTotal'],2,'.','').'"';    
                     }
                     
                     $htmlTabla .= "<tr $class>".
                                   "<td id=\"iCve_".$items['iConsecutivoCompania']."\" class=\"".$invoiceID."\">".$redlist_icon.$items['sNombreCompania']."</td>".
                                   "<td id=\"iPol_".$items['iConsecutivoPoliza']."\" title=\"".$items['sDescripcion']."\">".$items['sNumeroPoliza']." / ".$items['sAlias']."</td>". 
                                   "<td id=\"iEnd_".$items['iConsecutivoEndoso']."\" class=\"text-center\">".$items['sNumeroEndosoBroker']."</td>". 
                                   "<td>".$detalle."</td>".
                                   "<td class=\"text-center\">$FinancingData</td>".
                                   "<td title='$titleEstatus'>".$estado."</td>". 
                                   "<td class=\"text-right\" $titletotal>".$total."</td>". 
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
                    $query = "SELECT iConsecutivo AS iConsecutivoServicio, sClave, sDescripcion, sCveUnidadMedida, iPrecioUnitario, iPctImpuesto FROM ct_productos_servicios WHERE sDescripcion LIKE '%ENDORSEMENT%' LIMIT 1"; 
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
                        array_push($campos_endoso ,"iConsecutivoInvoice");  array_push($valores_endoso ,"$idFactura");
                        array_push($campos_endoso ,"iEndorsementsApply");   array_push($valores_endoso ,'1');
                        array_push($campos_endoso ,"iMostrarEndorsements"); array_push($valores_endoso ,'1');
                        array_push($campos_endoso ,"iImpuesto");            array_push($valores_endoso ,$iImpuesto);
                        array_push($campos_endoso ,"iPrecioExtendido");     array_push($valores_endoso ,$precioExtendido);
                        array_push($campos_endoso ,"dFechaIngreso");        array_push($valores_endoso ,date("Y-m-d H:i:s"));
                        array_push($campos_endoso ,"sIP");                  array_push($valores_endoso ,$_SERVER['REMOTE_ADDR']);
                        array_push($campos_endoso ,"sUsuarioIngreso");      array_push($valores_endoso ,$_SESSION['usuario_actual']);
                        
                        $query = "INSERT INTO cb_invoice_detalle (".implode(",",$campos_endoso).") VALUES ('".implode("','",$valores_endoso)."')";
                        $conexion->query($query);  
                
                        if($conexion->affected_rows == 0){$error = '1'; $msj = "Error to add the endorsement summary data to link the invoice, please try again.";}
                        else{
                            $idDetalle = $conexion->insert_id;
                            $query = "INSERT INTO cb_invoice_detalle_endoso (iConsecutivoDetalle,iConsecutivoEndoso) VALUES ('".$idDetalle."','".$_POST['iConsecutivoEndoso']."')";
                            $conexion->query($query); 
                            if($conexion->affected_rows == 0){$error = '1'; $msj = "Error to update the endorsement summary data to link the invoice, please try again.";}   
                        }
                    }   
                } 
            }
            
          }
          
          
      }
      
      $transaccion_exitosa && $error == '0' ? $conexion->commit() : $conexion->rollback();
      $conexion->close();
      
      $response = array("error"=>"$error","msj"=>"$msj","idFactura"=>"$idFactura");
      echo json_encode($response);
  }
  
  
  // EXTRAS:
  function get_policy_data(){
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $htmlTabla = "";
      $clave     = trim($_POST['iConsecutivoPoliza']);
      $clave2    = trim($_POST['iConsecutivoEndoso']);
      
      $sql   = "SELECT A.iConsecutivo AS clave, sNumeroPoliza, sNombreCompania, C.sName AS sBroker, E.sName AS sInsurance , D.sDescripcion, iOnRedList, DATE_FORMAT(dFechaInicio,'%m/%d/%Y') AS dFechaInicio, DATE_FORMAT(dFechaCaducidad,'%m/%d/%Y') AS dFechaCaducidad, iConsecutivoArchivo,A.iConsecutivoCompania, iTipoPoliza, iConsecutivoArchivoPFA ".
               "FROM      ct_polizas     AS A 
                LEFT JOIN ct_companias   AS B ON A.iConsecutivoCompania   = B.iConsecutivo
                LEFT JOIN ct_brokers     AS C ON A.iConsecutivoBrokers    = C.iConsecutivo
                LEFT JOIN ct_tipo_poliza AS D ON A.iTipoPoliza            = D.iConsecutivo 
                LEFT JOIN ct_aseguranzas AS E ON A.iConsecutivoAseguranza = E.iConsecutivo ".
               "WHERE A.iConsecutivo='".$clave."'";
      $result= $conexion->query($sql);
      $rows  = $result->num_rows; 
      if ($rows > 0) {$items = $result->fetch_assoc();}
      
      $sql2   = "SELECT sNumeroEndosoBroker, sComentarios, rImporteEndosoBroker FROM cb_endoso_estatus WHERE iConsecutivoEndoso = '".$clave2."'";
      $result2= $conexion->query($sql2);
      $rows2  = $result2->num_rows; 
      if ($rows2 > 0) {$items2 = $result2->fetch_assoc();}
      
      
      if($rows > 0 && $rows2 > 0){
          
        #CONSULTAR DETALLE DEL ENDOSO:
         $query = "SELECT A.sVIN, (CASE 
                    WHEN A.eAccion = 'ADDSWAP'    THEN 'ADD SWAP'
                    WHEN A.eAccion = 'DELETESWAP' THEN 'DELETE SWAP'
                    WHEN A.eAccion = 'CHANGEPD'   THEN 'CHANGE PD'
                    ELSE A.eAccion
                    END) AS eAccion FROM cb_endoso_unidad AS A WHERE A.iConsecutivoEndoso = '".$clave2."' ORDER BY sVIN ASC";
         $r     = $conexion->query($query);
         
         $description = "";   
         $count       = 0;
         $descTitle   = "";
         
         while($item = $r->fetch_assoc()){
            
            if($count == 0){
                $firstA = $item['eAccion'];
                $firstV = $item['sVIN'];
                $description = "<tr style='background: none;'>".
                                "<td style='border: 0;width:105px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['eAccion']."</td>".
                                "<td style='border: 0;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['sVIN']."</td>".
                                "<td style='border: 0;width: 70px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$items['dFechaAplicacion']."</td>".
                                "</tr>";                    
            }
            else{
                if($count == 1){
                    $description = "<tr style='background: none;'>".
                                    "<td style='border: 0;width:105px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$firstA."</td>".
                                    "<td style='border: 0;padding: 0!important;min-height: auto!important;height:auto!important;'>".$firstV."... </td>".
                                    "<td style='border: 0;width: 70px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$items['dFechaAplicacion']."</td>".
                                    "</tr>"; 
                }
            } 
            $descTitle == "" ? $descTitle .= $item['eAccion']." ".$item['sVIN'] : $descTitle.= "\n".$item['eAccion']." ".$item['sVIN'];
            $count ++;
         }
         $detalle  = "<table title=\"$descTitle\" style=\"width:100%;padding:0!important;margin:0!important;border-collapse: collapse;\">";
         $detalle .= $description."</table>"; 
          
          
        $insurance = strtoupper(utf8_decode($items['sInsurance']));
        if(strlen($insurance) > 15){$insurance = substr($insurance,0,15)."... ";}
        
        $broker = strtoupper(utf8_decode($items['sBroker']));
        if(strlen($broker) > 15){$broker = substr($broker,0,15)."... ";}
        
        $btn_pdf = '<a href="endorsement_files?idEndorsement='.$clave2.'" target="_blank" class="btn-text-2 btn-right" title="Endorsement files"><i class="fa fa-external-link" aria-hidden="true" style="margin-right:0px!important;"></i></a>';
        
        $htmlTabla = "<tr>".
                      "<td>".$items['sNumeroPoliza']."</td>".
                      "<td>".$items['sDescripcion']."</td>". 
                      "<td title=\"".strtoupper(utf8_decode($items['sBroker']))."\">".$broker."</td>".
                      "<td title=\"".strtoupper(utf8_decode($items['sInsurance']))."\">".$insurance."</td>". 
                      "<td>".$items['dFechaInicio']."</td>".
                      "<td>".$items['dFechaCaducidad']."</td>".  
                      "<td title=\"Customer service Comments: ".$items2['sComentarios']."\">".$items2['sNumeroEndosoBroker']."</td>".
                      "<td>".$detalle."</td>".
                      "<td class=\"text-right\"> \$".number_format($items2['rImporteEndosoBroker'],2,'.',',')."</td>". 
                      "<td>".$btn_pdf."</td>".                                                                                                                                                                                                                   
                      "</tr>"; 
      } 
      
      
      if($htmlTabla == ""){$htmlTabla ="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";} 
      $conexion->rollback();
      $conexion->close();  
      
      $response = array("html"=>"$htmlTabla");   
      echo json_encode($response);
  }
  function get_invoice_data(){
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $clave = trim($_POST['iConsecutivoInvoice']);
      
      $sql   = "SELECT A.iConsecutivo,sNoReferencia, sNombreCompania, B.sEmailContacto, sNombreContacto, dTotal, iFinanciamiento, sDiasFinanciamiento, eStatus, iOnRedList, DATE_FORMAT(dFechaInvoice, '%m/%d/%Y') AS  dFechaInvoice, sCveMoneda, A.sNombreArchivo ".   
               "FROM      cb_invoices  AS A ".
               "LEFT JOIN ct_companias AS B ON A.iConsecutivoCompania = B.iConsecutivo ".
               "WHERE A.iConsecutivo='".$clave."'";
      $result= $conexion->query($sql);
      $rows  = $result->num_rows; 
      if ($rows > 0) {    
        while ($items = $result->fetch_assoc()){ 
            $FinancingData = "";
            if($items['iFinanciamiento'] == "0"){
                $FinancingData = 'N/A';  
                //Validar fecha limite de pago para determinar el estatus:
                $fechaLimite   = sumar_dias_fecha($items['dFechaAplicacion'],10);   
            }
            else{
                $FinancingData = "<span style=\"font-weight: 700;\">".$items['sDiasFinanciamiento']." months</span>";
                $FinancingData.= "<br><span style=\"font-size: 10px;opacity: 0.9;\">\$ ".$items['dFinanciamientoMonto']." ".$items['sCveMoneda']."</span>"; 
            }
            
            $htmlTabla = "<tr ".$redlist_class.">".
                         "<td id=\"inv_".$items['iConsecutivo']."\">".$items['sNoReferencia']."</td>".
                         "<td class=\"text-center\">".$FinancingData."</td>".
                         "<td class=\"text-center\">".$items['dFechaInvoice']."</td>".
                         "<td class=\"text-right\" style=\"padding-right: 5px;\">\$ ".number_format($items['dTotal'],2,'.',',')." ".$items['sCveMoneda']."</td>".  
                         "</tr>"; 
        }
      }
      else{$htmlTabla ="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";} 
      $conexion->rollback();
      $conexion->close();  
      
      $response = array("html"=>"$htmlTabla");   
      echo json_encode($response);
  }
?>
