<?php
  session_start();
  // Generic functions lib 
  include("functiones_genericas.php"); 
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId 
    
  //INVOICES:
  function get_invoices(){
     
    include("cn_usuarios.php");
    $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
    $transaccion_exitosa = true;
    
    $registros_por_pagina = $_POST["registros_por_pagina"];
    $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
    $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
        
    //Filtros de informacion //
    $filtroQuery = " WHERE A.iConsecutivo IS NOT NULL AND bEliminado = '0' AND eStatus != 'CANCELED'";
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
    $query_rows = "SELECT COUNT(A.iConsecutivo) AS total FROM cb_invoices A ".
                  "LEFT JOIN ct_companias B ON A.iConsecutivoCompania = B.iConsecutivo ".$filtroQuery;
    $Result     = $conexion->query($query_rows);
    $items      = $Result->fetch_assoc();
    $registros  = $items["total"];
    if($registros == "0"){$pagina_actual = 0;}
    $paginas_total = ceil($registros / $registros_por_pagina);
    
    if($registros == "0"){
        $limite_superior = 0;
        $limite_inferior = 0;
        $htmlTabla      .= "<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";
    }else{
        $pagina_actual == "0" ? $pagina_actual = 1 : false;
        $limite_superior = $registros_por_pagina;
        $limite_inferior = ($pagina_actual*$registros_por_pagina)-$registros_por_pagina;
        $sql = "SELECT A.iConsecutivo,sNoReferencia, sNombreCompania, B.sEmailContacto, sNombreContacto, dTotal, iFinanciamiento, sDiasFinanciamiento, eStatus, iOnRedList, DATE_FORMAT(dFechaInvoice, '%m/%d/%Y') AS  dFechaInvoice, sCveMoneda, A.sNombreArchivo ".   
               "FROM      cb_invoices  AS A ".
               "LEFT JOIN ct_companias AS B ON A.iConsecutivoCompania = B.iConsecutivo ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
        $result = $conexion->query($sql);
        $rows   = $result->num_rows;    
        if ($rows > 0) {    
            while ($items = $result->fetch_assoc()) { 

                     $btns_right= "";
                     $btns_left = "";
                     //Redlist:
                     if($items['iOnRedList'] == '1'){
                        $redlist_class = "class=\"row_red\"";
                        $redlist_icon = "<i class=\"fa fa-star\" style=\"color:#e8051b;margin-right:4px;\"></i>"; 
                     }else{
                        $redlist_icon = ""; 
                        $redlist_class = "";
                     }
                     
                     
                     switch($items['eStatus']){
                         case 'EDITABLE': 
                            $btns_left  = "<div class=\"btn_apply btn-text-2 send btn-center\" title=\"Apply Invoice\" style=\"width: 70px;text-transform: uppercase;\"><i class=\"fa fa-check-circle\"></i><span>Apply</span></div> "; 
                            
                            if($items['sNombreArchivo'] != ""){
                                $btns_right .= "<div class=\"btn-icon btn-left pdf\" title=\"Open file\" onclick=\"window.open('open_pdf.php?idfile=".$items['iConsecutivo']."&type=invoice');\"><i class=\"fa fa-file-pdf-o\"></i><span></span></div>";
                            }
                            
                            $btns_right.= "<div class=\"btn_edit btn-icon edit btn-left\" title=\"Edit\"><i class=\"fa fa-pencil-square-o\"></i></div>".
                                          "<div class=\"btn_delete btn-icon trash btn-left\" title=\"Delete\"><i class=\"fa fa-trash\"></i></div>";
                         break;
                         case 'APPLIED': 
                            //$btns_left = "<div class=\"btn_send_invoice btn-icon send-email btn-left\" title=\"Send to: ".$items['sEmailContacto']."\"><i class=\"fa fa-envelope\"></i></div>"; 
                            
                            if($items['sNombreArchivo'] != ""){
                                $btns_left .= "<div class=\"btn-icon btn-left pdf\" title=\"Open file\" onclick=\"window.open('open_pdf.php?idfile=".$items['iConsecutivo']."&type=invoice');\"><i class=\"fa fa-file-pdf-o\"></i><span></span></div>";
                            }
                            else{
                                $btns_left.= "<div class=\"btn_pdf btn-icon pdf btn-left\" title=\"Open Invoice PDF\"><i class=\"fa fa-file-pdf-o\"></i></div>";     
                            }
                            //$btns_right= "<div class=\"btn_cancel btn-icon trash btn-left\" title=\"Cancel Invoice\"><i class=\"fa fa-times-circle\"></i></div>";
                         break; 
                         case 'SENT': 
                            $btns_left  = "<div class=\"btn_send_invoice active btn-icon send-email btn-left\" title=\"Send to: ".$items['sEmailContacto']."\"><i class=\"fa fa-envelope\"></i></div>"; 
                            $btns_left .= "<div class=\"btn_pdf btn-icon pdf btn-left\" title=\"Open Invoice PDF\"><i class=\"fa fa-file-pdf-o\"></i></div>"; 
                            //$btns_right.= "<div class=\"btn_payments btn-icon view btn-left\" title=\"Payments\"><i class=\"fa fa-usd\"></i></div>";
                            //$btns_right.= "<div class=\"btn_cancel btn-icon trash btn-left\" title=\"Cancel Invoice\"><i class=\"fa fa-times-circle\"></i></div>";
                         break; 
                     }
                     
                     $htmlTabla .= "<tr ".$redlist_class.">".
                                   "<td>".$btns_left."</td>". 
                                   "<td id=\"inv_".$items['iConsecutivo']."\">".$items['sNoReferencia']."</td>".
                                   "<td>".$items['sNombreCompania']."</td>". 
                                   //"<td>".$items['eTipoInvoice']."</td>".
                                   "<td class=\"text-center\">".$items['dFechaInvoice']."</td>".
                                   //"<td>".$items['iFinanciamiento']."</td>".
                                   "<td class=\"text-right\">\$ ".number_format($items['dTotal'],2,'.',',')." ".$items['sCveMoneda']."</td>".  
                                   //"<td>".$items['eStatus']."</td>".                                                                                                                                                                                                                    
                                   "<td>".$btns_right."</td></tr>";
            }
        
            
            $conexion->rollback();
            $conexion->close();                                                                                                                                                                       
        } 
        else { $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";    } 
    }
     $response = array("total"=>"$paginas_total","pagina"=>"$pagina_actual","mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
     echo json_encode($response); 
  }
  
  function get_data(){
      
    $error   = '0';
    $msj     = "";
    $fields  = "";
    $clave   = trim($_POST['clave']);
    $domroot = $_POST['domroot'];
    
    include("cn_usuarios.php");
  
    $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
    $transaccion_exitosa = true;
    $sql    = "SELECT iConsecutivo,iConsecutivoCompania, sNoReferencia, dTotal, iFinanciamiento, sDiasFinanciamiento, eStatus, ".
              "DATE_FORMAT(dFechaInvoice, '%m/%d/%Y') AS  dFechaInvoice, dSubtotal,dPctTax,dTax,dAnticipo,dBalance,dTipoCambio,sComentarios,sCveMoneda,".
              "sNombreArchivo, sTipoArchivo, iTamanioArchivo, iConsecutivoFinanciera, sDiasFinanciamiento, dFinanciamientoMonto, iFinanciamiento ".
              "FROM cb_invoices WHERE iConsecutivo = '$clave'"; 
    $result = $conexion->query($sql);
    $items  = $result->num_rows;   
    if ($items > 0) {     
        $items  = $result->fetch_assoc();
        $llaves = array_keys($items);
        $datos  = $items;
        
        foreach($datos as $i => $b){
            if($i != "sNombreArchivo" && $i != "sTipoArchivo" && $i != "iTamanioArchivo"){
                $fields .= "\$('$domroot [name=".$i."]').val('".$datos[$i]."');";
            }
        } 
        
        $sArchivoNombre   = $items['sNombreArchivo'];
        $sArchivoMIMEType = $items['sTipoArchivo'];
        $sArchivoTamano   = $items['iTamanioArchivo'];
          
        if($sArchivoNombre != "" && $sArchivoMIMEType != "" && $sArchivoTamano != ""){
          $fields.= "\$('$domroot .file-message').empty().html('<b>Informaci&oacute;n del Archivo: </b>$sArchivoNombre".
                    " <b>Tipo: </b>".substr($sArchivoMIMEType,0,40)." <b>Tama&ntilde;o: </b>$sArchivoTamano bytes');";  
          $fields.= "\$('$domroot #fileselect').addClass('fileupload'); ";  
        }  
    }
    $conexion->rollback();
    $conexion->close(); 
    $response = array("msj"=>"$msj","error"=>"$error","fields"=>"$fields");   
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
                    if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivo" ){ //Estos campos no se insertan a la tabla
                        array_push($valores,"$campo='".trim($valor)."'");
                    }
                 }   
              }
          }
          else if($edit_mode != 'true'){
             foreach($_POST as $campo => $valor){
               if($campo != "accion" && $campo != "edit_mode" && $campo != "iConsecutivo"){ //Estos campos no se insertan a la tabla
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
            
            if($conexion->affected_rows < 0){$transaccion_exitosa = false;}
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
            
            if($conexion->affected_rows < 1){$transaccion_exitosa = false;}
            else{$idFactura = $conexion->insert_id; $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The data has been saved successfully!</p>';}
            
          }
          
          if($transaccion_exitosa){$conexion->commit();$conexion->close();}
          else{
            $conexion->rollback();
            $conexion->close();
            $msj = "Error saving data, please try again.";
            $error = "1";
          }
          if($transaccion_exitosa)$msj = "The data has been saved successfully."; 
      }
      $response = array("error"=>"$error","msj"=>"$msj","idFactura"=>"$idFactura");
      echo json_encode($response);
  }
  
  function actualiza_totales(){
      
      include("cn_usuarios.php");
  
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      //Variables:
      $clave      = trim($_POST["iConsecutivo"]);
      $fields     = "";
      $domroot    = trim($_POST['domroot']);
      $rSubtotal  = 0;
      $rTotal     = 0;
      $rTax       = 0;
      $error      = 0; 
      $rDescuento = 0;
      //$rPDescuento = trim($_POST['rPDescuento']); //% de descuento.
      //$sMotivosDes = trim($_POST['sMotivosDescuento']);
      
      if($clave == ""){/*$transaccion_exitosa = false; $msj = "Error to calculate the invoice total, please try again later.";$error = 1;*/}
      else{
           //Consultamos el Subtotal y las Taxas de la factura:
           $query  = "SELECT SUM((iPrecioUnitario*iCantidad)) AS subtotal, SUM(iImpuesto) AS tax ".
                     "FROM cb_invoice_detalle WHERE iConsecutivoInvoice = '".$clave."' ";
           $result = $conexion->query($query);
           $P      = $result->fetch_assoc();
           
           $P["subtotal"] == null ? $rSubtotal = 0 : $rSubtotal = number_format($P["subtotal"],2,'.','');   
           $P["tax"]      == null ? $rTax      = 0 : $rTax      = number_format($P["tax"],2,'.',''); 
          
           # TERMINA NUEVO CALCULO.
           $rTotal = $rSubtotal - $rDescuento + $rTax;
           $rTotal = number_format(number_round(($rTotal),2),2,'.', ''); 
           
           # ACTUALIZAMOS TOTAL A NIVEL FACTURA:
           $Q = "UPDATE cb_invoices SET dSubtotal = '$rSubtotal',dTax='$rTax',rPDescuento='',dDescuento='$rDescuento',dTotal = '$rTotal', sMotivosDescuento = '' ".
                "WHERE iConsecutivo = '".$clave."'";
           $conexion->query($Q);
           
           if($conexion->affected_rows < 0){$transaccion_exitosa = false; $error = 1; $msj = "Error to update the invoice total, please try again later.";}
           else{
               //JS:
               $fields .= "$('$domroot input[name=dSubtotal]').val('$rSubtotal');";
               $fields .= "$('$domroot #dTax').val('$rTax');";
               $fields .= "$('$domroot #dTotal').val('$rTotal');";
           }
      }
      
      $transaccion_exitosa && $error == 0 ? $conexion->commit() : $conexion->rollback();
      $conexion->close();
      $response = array("fields"=>"$fields","error"=>"$error","msj"=>"$msj");
      echo json_encode($response);
  }
  
  function delete_data(){
      
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE); 
      
      $error = 0; 
      $msj   = '';
      $clave = trim($_POST['iConsecutivo']);
      
      $valid_user = valid_user($_SESSION['usuario_actual']);

      if(!($valid_user)){
          $error = '1';
          $msj   = "This user does not have the privileges to modify or add data to the system.";
      }
      
      if($error == '0'){
          $query = "UPDATE cb_invoices SET bEliminado='1' WHERE iConsecutivo='".$clave."'";
          $conexion->query($query);
          
          if($conexion->affected_rows < 0){$error = 1; $msj = "Error to delete data, please try again.";}
          else{$msj = "The data has been deleted successfully!";}
      }
      
      $error == 0 ? $conexion->commit() : $conexion->rollback();
      $conexion->close(); 
              
      $response = array("msj"=>"$msj","error"=>"$error");   
      echo json_encode($response);
  }
  
  function apply_data(){
    
      $error   = '0';  
      $msj     = "";
      $clave   = trim($_POST['clave']);
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true; 
      
      $valid_user = valid_user($_SESSION['usuario_actual']);

      if(!($valid_user)){
          $error = '1';
          $msj   = "This user does not have the privileges to modify or add data to the system.";
      }
      
      if($error == 0){
          $query = "UPDATE cb_invoices SET eStatus='APPLIED' WHERE iConsecutivo='".$clave."'";  
          $conexion->query($query);
          
          if($conexion->affected_rows < 0){$error = '1'; $msj = "Error to apply the invoice, please try again.";$conexion->rollback();}
          else{$msj="The invoice has been applied successfully and the customer can see it in his account.";$conexion->commit();}
      }
      
      $conexion->close(); 
      $response = array("msj"=>"$msj","error"=>"$error");   
      echo json_encode($response); 
  }
  
  //PRODUCTOS Y SERVICIOS:
  function ps_get_dataset(){
      
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $iConsecutivoInvoice = trim($_POST['iConsecutivoInvoice']);
      !isset($_POST['iEndososBilling']) ? $_POST['iEndososBilling'] = 'false' : "";
    
      $sql    = "SELECT iConsecutivoDetalle, CONCAT(sClave,' - ',sDescripcion) AS sDescripcion, iCantidad, iPrecioUnitario, iPctImpuesto, iImpuesto, iPrecioExtendido ".
                "FROM cb_invoice_detalle WHERE iConsecutivoInvoice = '$iConsecutivoInvoice'";
      $result = $conexion->query($sql);
      $rows   = $result->num_rows;    
      
      if ($rows > 0) {  
          
            $iFolio = 1;
              
            while ($items = $result->fetch_assoc()) { 
                
                $_POST['iEndososBilling'] == 'true' && $iFolio == 1 ? $btn_delete = "" : $btn_delete = "<div class=\"btn-icon trash btn-left\" title=\"Delete\"><i class=\"fa fa-trash\"></i></div>"; 

                $htmlTabla .= "<tr>".
                              "<td id=\"srv_".$items['iConsecutivoDetalle']."\">".$iFolio."</td>". 
                              "<td>".$items['sDescripcion']."</td>".
                              "<td class=\"text-center\">".$items['iCantidad']."</td>". 
                              "<td class=\"text-right\">\$ ".number_format($items['iPrecioUnitario'],2,'.',',')."</td>".
                              "<td class=\"text-right\">\$ ".number_format($items['iImpuesto'],2,'.',',')." ".$items['sCveMoneda']."</td>". 
                              "<td class=\"text-right\">\$ ".number_format($items['iPrecioExtendido'],2,'.',',')." ".$items['sCveMoneda']."</td>". 
                              "<td>".
                                "<div class=\"btn-icon edit btn-left\" title=\"Edit\"><i class=\"fa fa-pencil-square-o\"></i></div>".
                                $btn_delete.
                              "</td>".
                              "</tr>";
                $iFolio++;
            }
      } 
      else { $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";} 
      
      $conexion->rollback();
      $conexion->close(); 
              
      $response = array("tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error");   
      echo json_encode($response); 
  }  
   
  function get_service_data(){
      
      $iConsecutivo = trim($_POST['iConsecutivo']);
      $domroot      = trim($_POST['domroot']);
      $error        = "0"; 
      
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);
      
      //Conultar Servicio
      $query  = "SELECT iPrecioUnitario,iPctImpuesto,sComentarios FROM ct_productos_servicios WHERE iConsecutivo='$iConsecutivo' AND bEliminado = '0'";
      $result = $conexion->query($query);
      
      if($result->num_rows > 0){
        $items  = $result->fetch_assoc();
        $llaves = array_keys($items);
        $datos  = $items;
        
        foreach($datos as $i => $b){$fields .= "\$('#$domroot :input[id=".$i."]').val('".$datos[$i]."');";}     
      }
      
      $conexion->rollback();
      $conexion->close(); 
              
      $response = array("fields"=>"$fields","mensaje"=>"$mensaje","error"=>"$error");   
      echo json_encode($response); 
  }
  
  //DETALLE:
  function detalle_save_data(){
      
      include("funciones_genericas.php");
      $error    = '0'; 
      $valores  = array();
      $campos   = array(); 
      $msj      = "";
      $edit_mode= trim($_POST["edit_mode"]);
      $clave    = trim($_POST['iConsecutivoDetalle']);
     
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      $valid_user = valid_user($_SESSION['usuario_actual']);

      if(!($valid_user)){
          $error = '1';
          $msj   = "This user does not have the privileges to modify or add data to the system.";
      }
      
      if($error == '0'){
          //Obtener datos del servicio:
          $query  = "SELECT sClave, sDescripcion, sCveUnidadMedida FROM ct_productos_servicios WHERE iConsecutivo='".trim($_POST['iConsecutivoServicio'])."'";
          $res    = $conexion->query($query);
          $service= $res->fetch_assoc(); 
          
          $_POST["sClave"]           = $service['sClave'];
          $_POST["sDescripcion"]     = $service['sDescripcion'];
          $_POST["sCveUnidadMedida"] = $service['sCveUnidadMedida'];
          
          #INSERT
          if($edit_mode != 'true'){
             foreach($_POST as $campo => $valor){
               if($campo != "accion" && $campo != "edit_mode" && $campo != "iConsecutivoEndosos" && $campo != "iConsecutivoDetalle"){ //Estos campos no se insertan a la tabla
                    if($campo == 'sComentarios' && $valor != ""){$valor = fix_string($valor);}
                    array_push($campos ,$campo); 
                    array_push($valores, trim($valor));
               }
             }  
             
             array_push($campos ,"dFechaIngreso");
             array_push($valores ,date("Y-m-d H:i:s"));
             array_push($campos ,"sIP");
             array_push($valores ,$_SERVER['REMOTE_ADDR']);
             array_push($campos ,"sUsuarioIngreso");
             array_push($valores ,$_SESSION['usuario_actual']);
                
             $sql = "INSERT INTO cb_invoice_detalle (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')";
             $conexion->query($sql);
                
             if($conexion->affected_rows < 1){$transaccion_exitosa = false;}
             else{
                 $clave= $conexion->insert_id;
                 $msj  = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The data has been saved successfully!</p>';
             }
               
          }
          #UPDATE
          else{
            foreach($_POST as $campo => $valor){
               if($campo != "accion" && $campo != "edit_mode" && $campo != "iConsecutivoDetalle" && $campo != 'iConsecutivoInvoice' && $campo != "iConsecutivoEndosos"){ //Estos campos no se insertan a la tabla
                    if($campo == 'sComentarios' && $valor != ""){$valor = fix_string($valor);}
                    array_push($valores,"$campo='".trim($valor)."'");
               }
            } 
            
            array_push($valores ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
            array_push($valores ,"sIP='".$_SERVER['REMOTE_ADDR']."'");
            array_push($valores ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'");
            $sql = "UPDATE cb_invoice_detalle SET ".implode(",",$valores)." WHERE iConsecutivoDetalle = '".$clave."'";
            $conexion->query($sql);
                
            if($conexion->affected_rows < 0){$transaccion_exitosa = false;}
            else{$msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The data has been updated successfully.</p>';}
                      
          } 
          
          #VALIDAMOS SI APLICA ENDOSOS:
          $endososApply = trim($_POST['iEndorsementsApply']);
          if($endososApply == '1' && $transaccion_exitosa && trim($_POST['iConsecutivoEndosos']) != ""){
             
              $endosos = trim($_POST['iConsecutivoEndosos']); 
              $endosos = explode("|",$endosos); 
              $count   = count($endosos);
              
              /*if($edit_mode == 'true'){
                  $sql = "DELETE FROM cb_invoice_detalle_endoso WHERE iConsecutivoDetalle='".$clave."'";
                  $conexion->query($sql);
                  if($conexion->affected_rows < 1){$transaccion_exitosa = false; $msj = "Endorsements could not be updated correctly, please try again.";}
              }  */
              
              if($transaccion_exitosa){
                for($x=0; $x<$count; $x++){
                    $sql = "INSERT INTO cb_invoice_detalle_endoso (iConsecutivoDetalle,iConsecutivoEndoso) VALUES('".$clave."','".$endosos[$x]."')"; 
                    $conexion->query($sql); 
                    if($conexion->affected_rows < 1){$transaccion_exitosa = false; $msj = "Endorsements could not be added correctly, please try again.";}  
                }    
              }

          }
          
          if($transaccion_exitosa){$conexion->commit(); $msj = "The data has been saved successfully.";}
          else{$conexion->rollback();$error = "1";} 
      }
      
          
      $conexion->close();
      $response = array("error"=>"$error","msj"=>"$msj");
      echo json_encode($response); 
  }
  
  function detalle_get_data(){
      
    $error   = '0';
    $msj     = "";
    $fields  = "";
    $clave   = trim($_POST['clave']);
    $domroot = $_POST['domroot'];
    
    include("cn_usuarios.php");
  
    $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
    $transaccion_exitosa = true;
    $sql    = "SELECT iConsecutivoDetalle,iConsecutivoInvoice, iConsecutivoServicio, iCantidad,iPrecioUnitario,iPctImpuesto, ".
              "iImpuesto,iPrecioExtendido,iEndorsementsApply, sComentarios, iMostrarEndorsements ".
              "FROM cb_invoice_detalle WHERE iConsecutivoDetalle = '$clave'";
    $result = $conexion->query($sql);
    $items  = $result->num_rows;   
    if ($items > 0) {     
        $items  = $result->fetch_assoc();
        $llaves = array_keys($items);
        $datos  = $items;
        
        foreach($datos as $i => $b){
            if($i == 'sComentarios'){$fields .= "\$('$domroot [name=".$i."]').val('".utf8_decode($datos[$i])."');";}else
            if($i == 'iEndorsementsApply'){
                $datos[$i] == '1' ? $datos[$i] = 'true' : $datos[$i] = 'false';
                $fields .= "\$('$domroot :input[name=".$i."]').prop('checked',".$datos[$i].");";
            }
            else{$fields .= "\$('$domroot [name=".$i."]').val('".$datos[$i]."');";}
        } 
        
        $iEndorsementsApply = $items['iEndorsementsApply'];
    }
    $conexion->rollback();
    $conexion->close(); 
    $response = array("msj"=>"$msj","error"=>"$error","fields"=>"$fields","endorsements_apply"=>"$iEndorsementsApply");   
    echo json_encode($response);
  }
  
  function detalle_delete(){
      
      include("funciones_genericas.php");
      $error= '0'; 
      $clave= trim($_POST["iConsecutivoDetalle"]);
      
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE); 
      
      $valid_user = valid_user($_SESSION['usuario_actual']);

      if(!($valid_user)){
          $error = '1';
          $msj   = "This user does not have the privileges to modify or add data to the system.";
      }
      
      if($error == '0'){
          //Revisamos si hay que eliminar endosos asociados:
          $query = "SELECT iEndorsementsApply FROM cb_invoice_detalle WHERE iConsecutivoDetalle='".$clave."'";
          $res   = $conexion->query($query);
          if($res->num_rows > 0){
              $valida = $res->fetch_assoc();
              if($valida['iEndorsementsApply'] == '1'){
                  $query = "DELETE FROM cb_invoice_detalle_endoso WHERE iConsecutivoDetalle='".$clave."'";
                  $conexion->query($query);
                  if($conexion->affected_rows <= 0){$error = '1'; $msj = "Error to delete the endorsement added, please try again later.";}
              }
          }
      }
     
      if($error == '0'){
          $query = "DELETE FROM cb_invoice_detalle WHERE iConsecutivoDetalle='".$clave."'";
          $conexion->query($query);
          if($conexion->affected_rows <= 0){$error = '1'; $msj = "Error to delete the data, please try again later.";}
          else{$msj = "Data has been deleted successfully!";}
      }
     
      $error == '0' ? $conexion->commit() : $conexion->rollback();
      $conexion->close();
      $response = array("error"=>"$error","msj"=>"$msj");
      echo json_encode($response);    
  }
  
  //ENDOSOS:
  function get_endorsements(){
     include("cn_usuarios.php");
     $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
     $transaccion_exitosa = true;
 
     //Filtros de informacion //
     $filtroQuery   = " WHERE A.eStatus != 'E' AND A.eStatus != 'S' AND A.iDeleted='0' AND A.iConsecutivoCompania='".trim($_POST['iConsecutivoCompania'])."' ";
     $array_filtros = explode(",",$_POST["filtroInformacion"]); 
     $filtroFecha1  = "";
     $filtroFecha2  = "";  
     foreach($array_filtros as $key => $valor){
        if($array_filtros[$key] != ""){
            $campo_valor = explode("|",$array_filtros[$key]);
            if($campo_valor[0] == 'A.iConsecutivoTipoEndoso' && $campo_valor[1] != 0){ $filtroQuery.= " AND  ".$campo_valor[0]."='".$campo_valor[1]."' ";}else
            if($campo_valor[0] == 'A.dFechaAplicacion') {$filtroFecha1 = $campo_valor[1];}else
            if($campo_valor[0] == 'A.dFechaAplicacionF'){$filtroFecha2 = $campo_valor[1];}
        }
     }
     
     $filtroFecha1= date_create_from_format('m/d/Y', $filtroFecha1);
     $filtroFecha2= date_create_from_format('m/d/Y', $filtroFecha2);
     $filtroQuery.= "AND A.dFechaAplicacion BETWEEN '".$filtroFecha1->format('Y-m-d')."' AND '".$filtroFecha2->format('Y-m-d')."'";
     
     $clave = trim($_POST['iConsecutivoDetalle']);
     if($clave != ""){
         $query = "SELECT * FROM cb_invoice_detalle_endoso WHERE iConsecutivoDetalle='".$clave."'";
         $res   = $conexion->query($query);
         if($res->num_rows > 0){
             $filtroQuery.= " AND A.iConsecutivo NOT IN(";
             $filtroTmp  = "";
             while ($endososAdded = $res->fetch_assoc()){
                 $filtroTmp == "" ? $filtroTmp .= $endososAdded['iConsecutivoEndoso'] : $filtroTmp .= ",".$endososAdded['iConsecutivoEndoso'];
             }
             $filtroQuery.= $filtroTmp.") ";
         }
     }
     
     // ordenamiento//
     $ordenQuery = " ORDER BY ".$_POST["ordenInformacion"]." ".$_POST["sortInformacion"];

    //contando registros // 
    $query_rows = "SELECT COUNT(A.iConsecutivo) AS total ".
                  "FROM cb_endoso AS A ".
                  "LEFT JOIN ct_tipo_endoso AS C ON A.iConsecutivoTipoEndoso = C.iConsecutivo ".$filtroQuery;
    $Result = $conexion->query($query_rows);
    $items = $Result->fetch_assoc();
    $registros = $items["total"];
    if($registros == "0"){$pagina_actual = 0;}
    $paginas_total = ceil($registros / $registros_por_pagina);
    
    if($registros == "0"){$limite_superior = 0;$limite_inferior = 0;$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";}
    else{
      
      $sql    = "SELECT A.iConsecutivo,A.iConsecutivoTipoEndoso,DATE_FORMAT( A.dFechaAplicacion, '%m/%d/%Y' ) AS dFechaAplicacion,C.sDescripcion,A.eStatus,eAccion, A.sVINUnidad AS sVIN,A.sNombreOperador AS sNombre, A.iEndosoMultiple ".
                "FROM cb_endoso AS A ".
                "LEFT JOIN ct_tipo_endoso AS C ON A.iConsecutivoTipoEndoso = C.iConsecutivo ".$filtroQuery.$ordenQuery;
      $result = $conexion->query($sql);
      $rows   = $result->num_rows; 
         
        if ($rows > 0) {    
            while ($items = $result->fetch_assoc()) { 
              
                     $btn_confirm = "";
                     $estado      = "";
                     $class       = "";
                     //$descripcion = ""; 
                     $titleEstatus="";
                     #ESTATUS DEL ENDOSO:
                     switch($items["eStatus"]){
                         case 'A': 
                            $estado      = '<i class="fa fa-check-circle status-success icon-estatus " aria-hidden=\"true\"></i><span style="font-size: 10px;position: relative;top: 4px;">APPROVED</span>';
                            $titleEstatus= "Your endorsement has been approved successfully.";
                            $class       = "class = \"green\"";
                         break;
                         case 'D': 
                            $estado      = '<i class="fa fa-times status-error icon-estatus " aria-hidden=\"true\"></i><span style="font-size: 10px;position: relative;top: 4px;">CANCELED</span>';
                            $titleEstatus= "Your endorsement has been canceled, please see the reasons on the comments.";
                            $class       = "class = \"red\"";
                         break;
                         case 'SB':                                                                                                             
                            $estado      = '<i class="fa fa-share-square-o status-process icon-estatus " aria-hidden=\"true\"></i><span style="font-size: 10px;position: relative;top: 4px;">SENT TO BROKERS</span>';
                            $titleEstatus= "Your endorsement has been sent to the brokers.";
                            $class       = "class = \"yellow\"";
                         break;
                         case 'P': 
                            $estado      = '<i class="fa fa-refresh status-process icon-estatus " aria-hidden=\"true\"></i><span style="font-size: 10px;position: relative;top: 4px;">IN PROCESS</span>';
                            $titleEstatus= "Your endorsement is being in process by the brokers.";
                            $class       = "class = \"orange\"";
                         break;
                     } 
                     
                     $color_action = "";
                     $action       = "";
                     $detalle      = "";
                     $polocies     = "";
                     
                     if($items['iEndosoMultiple'] == "0"){
                         switch($items["eAccion"]){
                             case 'A': $action = 'ADD'; break;
                             case 'D': $action = 'DELETE'; break;
                         }
                         $items['iConsecutivoTipoEndoso'] == '1' ? $descr = strtoupper($items['sVIN']) : $descr = strtoupper($items['sNombre']);
                         $detalle = "<table style=\"width:100%;padding:0!important;margin:0!important;\">";
                         $detalle.= "<tr style='background: none;'>".
                                    "<td style='border: 0;width:120px;padding: 0!important;'>".$action."</td>".
                                    "<td style='border: 0;padding: 0!important;'>".$descr."</td>".
                                    "</tr>";
                         $detalle.= "</table>";
                     }
                     else if($items['iEndosoMultiple'] == "1"){
                         
                         if($items['iConsecutivoTipoEndoso'] == '1'){
                             $tipo = "VEHICLE";
                             #CONSULTAR DETALLE DEL ENDOSO:
                             $query = "SELECT A.sVIN, (CASE 
                                        WHEN A.eAccion = 'ADDSWAP'    THEN 'ADD SWAP'
                                        WHEN A.eAccion = 'DELETESWAP' THEN 'DELETE SWAP'
                                        WHEN A.eAccion = 'CHANGEPD'   THEN 'CHANGE PD'
                                        ELSE A.eAccion
                                        END) AS eAccion FROM cb_endoso_unidad AS A WHERE A.iConsecutivoEndoso = '".$items['iConsecutivo']."' ORDER BY sVIN ASC";
                             $r     = $conexion->query($query);
                             
                             $detalle     = "<table style=\"width:100%;padding:0!important;margin:0!important;border-collapse: collapse;\">";
                             $description = "";
                             
                             while($item = $r->fetch_assoc()){
                                $description .= "<tr style='background: none;'>".
                                                "<td style='border: 0;width:100px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['eAccion']."</td>".
                                                "<td style='border: 0;width:90px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$tipo."</td>".
                                                "<td style='border: 0;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['sVIN']."</td>".
                                                "</tr>"; 
                             }
                             $detalle .= $description."</table>";    
                         }
                         else{
                             $tipo = "DRIVER";
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
                                "<td style='border: 0;width:100px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['eAccion']."</td>".
                                "<td style='border: 0;width:90px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$tipo."</td>".
                                "<td style='border: 0;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['sNombre']."</td>".
                                "</tr>"; 
                             }
                             $detalle .= $description."</table>";
                         }
                         
                     }
                     
                     //Consultar Estatus x poliza:
                     $query = "SELECT A.iConsecutivoPoliza,P.sNumeroPoliza, T.sDescripcion AS sTipoPoliza, B.sName AS sBrokerName ,A.eStatus, A.sNumeroEndosoBroker, A.rImporteEndosoBroker, A.iEnviadoFuera  
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
                            
                            $iEnviadoFuera = $item['iEnviadoFuera'];
                         }
                         $policies.="</table>";
                     }
                     
                      //Redlist:
                     $items['iOnRedList'] == '1' ? $redlist_icon = "<i class=\"fa fa-star\" style=\"color:#e8051b;margin-right:4px;\"></i>" : $redlist_icon = ""; 
                     
                     //Revisar si esta marcado como enviado y se envio fuera del sistema:
                     $iEnviadoFuera == 1 ? $txtFechaApp = "<span style=\"font-size:9px;display:block;\">Mark As Sent</span>".$items['dFechaAplicacion'] : $txtFechaApp = $items['dFechaAplicacion'];
                 
                     $htmlTabla .= "<tr $class>".
                                   "<td>".$detalle."</td>". 
                                   "<td>".$policies."</td>".
                                   "<td class=\"text-center\">".$txtFechaApp."</td>". 
                                   "<td title='$titleEstatus'>".$estado."</td>". 
                                   "<td><input type=\"checkbox\" name=\"chk_endorsement_invoice\" value=\"".$items['iConsecutivo']."\" /></td>".                                                                                                                                                                                                                      
                                   "</tr>";
            }
            $conexion->rollback();
            $conexion->close();                                                                                                                                                                       
        } else { 
            
            $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>"   ;    
            
        }
      }
      $response = array("tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error");   
      echo json_encode($response); 
  }
  
  function get_endorsements_added(){
    include("cn_usuarios.php");
    $conexion->autocommit(FALSE);
    $clave = trim($_POST['iConsecutivoDetalle']);
    !isset($_POST['iBtnsActive']) ? $_POST['iBtnsActive'] = 'true' : "";
     
    $query = "SELECT A.iConsecutivo,A.iConsecutivoTipoEndoso,DATE_FORMAT( A.dFechaAplicacion, '%m/%d/%Y' ) AS dFechaAplicacion,C.sDescripcion,A.eStatus,eAccion, A.sVINUnidad AS sVIN,A.sNombreOperador AS sNombre, A.iEndosoMultiple ".
             "FROM       cb_endoso                 AS A ".
             "INNER JOIN cb_invoice_detalle_endoso AS B ON B.iConsecutivoEndoso = A.iConsecutivo ".
             "LEFT  JOIN ct_tipo_endoso            AS C ON A.iConsecutivoTipoEndoso = C.iConsecutivo ".
             "WHERE B.iConsecutivoDetalle='".$clave."' AND A.eStatus != 'E' AND A.eStatus != 'S' AND A.iDeleted='0' ".
             "ORDER BY LEFT(A.dFechaAplicacion,10) DESC";
    $result = $conexion->query($query);
    $rows   = $result->num_rows; 
         
    if($rows == 0){$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>" ;}
    else{   
        while ($items = $result->fetch_assoc()) { 
          
          $btn_confirm = "";
          $estado      = "";
          $class       = "";
          $titleEstatus="";
          
          #ESTATUS DEL ENDOSO:
          switch($items["eStatus"]){
             case 'A': 
                $estado      = '<i class="fa fa-check-circle status-success icon-estatus " aria-hidden=\"true\"></i><span style="font-size: 10px;position: relative;top: 4px;">APPROVED</span>';
                $titleEstatus= "Your endorsement has been approved successfully.";
                $class       = "class = \"green\"";
             break;
             case 'D': 
                $estado      = '<i class="fa fa-times status-error icon-estatus " aria-hidden=\"true\"></i><span style="font-size: 10px;position: relative;top: 4px;">CANCELED</span>';
                $titleEstatus= "Your endorsement has been canceled, please see the reasons on the comments.";
                $class       = "class = \"red\"";
             break;
             case 'SB':                                                                                                             
                $estado      = '<i class="fa fa-share-square-o status-process icon-estatus " aria-hidden=\"true\"></i><span style="font-size: 10px;position: relative;top: 4px;">SENT TO BROKERS</span>';
                $titleEstatus= "Your endorsement has been sent to the brokers.";
                $class       = "class = \"yellow\"";
             break;
             case 'P': 
                $estado      = '<i class="fa fa-refresh status-process icon-estatus " aria-hidden=\"true\"></i><span style="font-size: 10px;position: relative;top: 4px;">IN PROCESS</span>';
                $titleEstatus= "Your endorsement is being in process by the brokers.";
                $class       = "class = \"orange\"";
             break;
          } 
                 
          $action       = "";
          $detalle      = "";
          $policies     = "";
                 
          if($items['iEndosoMultiple'] == "0"){
             switch($items["eAccion"]){
                 case 'A': $action = 'ADD'; break;
                 case 'D': $action = 'DELETE'; break;
             }
             $items['iConsecutivoTipoEndoso'] == '1' ? $descr = strtoupper($items['sVIN']) : $descr = strtoupper($items['sNombre']);
             $detalle = "<table style=\"width:100%;padding:0!important;margin:0!important;\">";
             $detalle.= "<tr style='background: none;'>".
                        "<td style='border: 0;width:120px;padding: 0!important;'>".$action."</td>".
                        "<td style='border: 0;padding: 0!important;'>".$descr."</td>".
                        "</tr>";
             $detalle.= "</table>";
          }
          else if($items['iEndosoMultiple'] == "1"){
                     
             if($items['iConsecutivoTipoEndoso'] == '1'){
                 $tipo = "VEHICLE";
                 #CONSULTAR DETALLE DEL ENDOSO:
                 $query = "SELECT A.sVIN, (CASE 
                            WHEN A.eAccion = 'ADDSWAP'    THEN 'ADD SWAP'
                            WHEN A.eAccion = 'DELETESWAP' THEN 'DELETE SWAP'
                            WHEN A.eAccion = 'CHANGEPD'   THEN 'CHANGE PD'
                            ELSE A.eAccion
                            END) AS eAccion FROM cb_endoso_unidad AS A WHERE A.iConsecutivoEndoso = '".$items['iConsecutivo']."' ORDER BY sVIN ASC";
                 $r     = $conexion->query($query);
                 
                 $detalle     = "<table style=\"width:100%;padding:0!important;margin:0!important;border-collapse: collapse;\">";
                 $description = "";
                 
                 while($item = $r->fetch_assoc()){
                    $description .= "<tr style='background: none;'>".
                                    "<td style='border: 0;width:100px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['eAccion']."</td>".
                                    "<td style='border: 0;width:90px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$tipo."</td>".
                                    "<td style='border: 0;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['sVIN']."</td>".
                                    "</tr>"; 
                 }
                 $detalle .= $description."</table>";    
             }
             else{
                 $tipo = "DRIVER";
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
                    "<td style='border: 0;width:100px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['eAccion']."</td>".
                    "<td style='border: 0;width:90px;padding: 0!important;min-height: auto!important;height:auto!important;'>".$tipo."</td>".
                    "<td style='border: 0;padding: 0!important;min-height: auto!important;height:auto!important;'>".$item['sNombre']."</td>".
                    "</tr>"; 
                 }
                 $detalle .= $description."</table>";
             }
             
          }
                 
          //Consultar Estatus x poliza:
          $query = "SELECT A.iConsecutivoPoliza,P.sNumeroPoliza, T.sDescripcion AS sTipoPoliza, B.sName AS sBrokerName ,A.eStatus, A.sNumeroEndosoBroker, A.rImporteEndosoBroker, A.iEnviadoFuera  
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
                
                $iEnviadoFuera = $item['iEnviadoFuera'];
             }
             $policies.="</table>";
          }
                      
          //Revisar si esta marcado como enviado y se envio fuera del sistema:
          $iEnviadoFuera == 1 ? $txtFechaApp = "<span style=\"font-size:9px;display:block;\">Mark As Sent</span>".$items['dFechaAplicacion'] : $txtFechaApp = $items['dFechaAplicacion'];
          
          $_POST['iBtnsActive'] == 'true' ? $btnsRight = "<td><div class=\"btn_delete btn-icon trash btn-left\" title=\"Cancel Add\"><i class=\"fa fa-times-circle\"></i></div></td>" : $btnsRight = "";    
          $htmlTabla .= "<tr $class>".
                        "<td id=\"iCve_".$items['iConsecutivo']."\">".$detalle."</td>". 
                        "<td>".$policies."</td>".
                        "<td class=\"text-center\">".$txtFechaApp."</td>". 
                        "<td title='$titleEstatus'>".$estado."</td>".
                        $btnsRight."</tr>";
        }                                                                                                                                                                      
    } 
    
    $response = array("html"=>"$htmlTabla");   
    echo json_encode($response);         
  }
  
  function delete_endorsement_added(){
      
      $error    = '0'; 
      $claveDetalle= trim($_POST["iConsecutivoDetalle"]);
      $claveEndoso = trim($_POST['iConsecutivoEndoso']);
     
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE); 
      
      $valid_user = valid_user($_SESSION['usuario_actual']);

      if(!($valid_user)){
          $error = '1';
          $msj   = "This user does not have the privileges to modify or add data to the system.";
      }
      else{
          $query = "DELETE FROM cb_invoice_detalle_endoso WHERE iConsecutivoEndoso='".$claveEndoso."' AND iConsecutivoDetalle='".$claveDetalle."'";
          $conexion->query($query);
          
          if($conexion->affected_rows <= 0){$error = '1'; $msj = "Error to delete the data, please try again later.";}
          else{$msj = "Data has been deleted successfully!";}
      }                                                                                                                                                                                                                                     
      
      $error == '0' ? $conexion->commit() : $conexion->rollback();
      $conexion->close();
      $response = array("error"=>"$error","msj"=>"$msj");
      echo json_encode($response); 
       
  }
  
  // PDF AND EMAIL
  function send_email_gmail(){
      
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);
      
      //variables:
      $error               = '0';
      $clave               = trim($_POST['clave']);
      $transaccion_exitosa = true;
      $envio_correo        = 1;
      $folio               = $clave;
      $ds                  = 'email';   
      
      require_once("pdf_invoice.php");
     
      if($nombre_pdf != ''){
          
           #CONSULTAR INVOICE:
           $sql    = "SELECT A.iConsecutivo, A.iConsecutivoCompania, B.sNombreCompania, B.sEmailContacto, sNoReferencia, DATE_FORMAT(dFechaInvoice,'%m/%d/%Y') AS dFechaInvoice,sReceptorNombre, sReceptorDireccion,sEmisorNombre, sLugarExpedicionDireccion, ".
                     "dSubtotal,rPDescuento,eStatus, dDescuento, sMotivosDescuento, dPctTax, dTax, dTotal, dAnticipo, dBalance, sCveMoneda, dTipoCambio, sComentarios ".
                     "FROM       cb_invoices  AS A ".
                     "INNER JOIN ct_companias AS B ON A.iConsecutivoCompania = B.iConsecutivo ".
                     "WHERE A.iConsecutivo = '".$folio."'";
           $result = $conexion->query($sql);
           $rows   = $result->num_rows;    
           if($rows == 0){ $error = '1'; $msj = "Error when consulting the invoice data, please try again.";}
           else{
               $invoice    = $result->fetch_assoc(); 

               #ARMANDO EMAIL:
               $subject = "sending invoice - ".trim($invoice['sNombreCompania']);
               #Building Email Body:                                   
               require_once("./lib/phpmailer_master/class.phpmailer.php");
               require_once("./lib/phpmailer_master/class.smtp.php"); 
               //header
               $htmlEmail .= "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"\"http://www.w3.org/TR/html4/strict.dtd\"><html>
                              <head><meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\">
                                <title>Sending Invoice from Solo-Trucking Insurance System</title>
                              </head>";
               //Body
               $htmlEmail .= "<body>".
                              "<table style=\"font-size:12px;border:1px solid #6191df;border-radius:3px;padding:10px;width:95%; margin:5px auto;font-family: Arial, Helvetica, sans-serif;\">".
                              "<tr>".
                                "<td>"."<h2 style=\"color:#313131;text-transform: uppercase; text-align:center;\">Sending your Invoice by Solo-Trucking Insurance System!</h2>"."</td>".
                              "</tr>".
                              "<tr>".
                                "<td>"."<b>".trim($invoice['sNombreCompania'])."</b>, Thank you for requesting our services <a href=\"http://www.solo-trucking.com/\">www.solo-trucking.com</a> the best option to choose the most convenient for your insurance."."</td>".
                              "</tr>".
                              "<tr>".
                                "<td><ul style=\"color:#010101;line-height:15px;\">".
                                "<li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">Invoice #: </strong>".$invoice['sNoReferencia']."</li>".
                                "<li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">Date: </strong>".$invoice['dFechaInvoice']."</li>".
                                "</ul></td>". 
                                "</tr><tr><td><b>If you have a doubt, please reply this email or call us.</b></td></tr>".
                              "<tr><td><p style=\"font-size:13px;\"><b>THANK YOU!!</b></p></td></tr>".
                              "<tr><td></td></tr>".
                              "<tr><td><p style=\"color:#858585;margin:5px auto; text-align:left;font-size:10px;\">E-mail sent from Solo-trucking System.</p></td></tr>".
                              "<tr></tr>". 
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
               
               #VERIFICAR SERVIDOR DONDE SE ENVIAN CORREOS:
               if($_SERVER["HTTP_HOST"]=="stdev.websolutionsac.com" || $_SERVER["HTTP_HOST"]=="www.stdev.websolutionsac.com"){
                  $mail->Username   = "systemsupport@solo-trucking.com";  // GMAIL username
                  $mail->Password   = "SL09100242";  
                  $mail->SetFrom('systemsupport@solo-trucking.com', 'Customer Service Solo-Trucking Insurance');
               }
               else if($_SERVER["HTTP_HOST"] == "solotrucking.laredo2.net" || $_SERVER["HTTP_HOST"] == "st.websolutionsac.com" || $_SERVER["HTTP_HOST"] == "www.solo-trucking.com"){
                  $mail->Username   = "customerservice@solo-trucking.com";  // GMAIL username
                  $mail->Password   = "1101W3bSTruck";
                  $mail->SetFrom('customerservice@solo-trucking.com', 'Customer Service Solo-Trucking Insurance'); 
                  $mail->AddReplyTo('customerservice@solo-trucking.com', 'Customer Service Solo-Trucking Insurance'); 
                  $mail->AddAddress('systemsupport@solo-trucking.com','System Support Solo-Trucking Insurance');  
               }
                
               $mail->Subject    = $subject;
               $mail->AltBody    = "To view the message, please use an HTML compatible email viewer!";  // optional, comment out and test
               $mail->MsgHTML($htmlEmail);
               $mail->IsHTML(true);  
               $mail->AddAddress(trim($invoice['sEmailContacto']));                          
               $mail->AddAttachment($pdfnew);
               $mail_error = false;
               if(!$mail->Send()){$mail_error = true; $mail->ClearAddresses();}
               if(!$mail_error){
                    $msj = "The e-mail was successfully sent.";
                    //Actualizar fecha de envio:
                    $query = "UPDATE cb_invoices SET dFechaEnvio = NOW(), sUsuarioEnvio = '".$_SESSION["usuario_actual"]."', eStatus='SENT' ".
                             "WHERE iConsecutivo='$clave' AND iConsecutivoCompania = '".$invoice['iConsecutivoCompania']."'";
                    $conexion->query($query);
                }
                else{
                    $msj = "Error: The e-mail cannot be sent.";
                    $error = "1";            
                }
                $mail->ClearAttachments();
                unlink($pdfnew);
               
           }
            
      }
          
      
      
      if($transaccion_exitosa){
            $conexion->commit();
            $conexion->close();
            
      }else{
            $conexion->rollback();
            $conexion->close();
            $error = "1"; 
            if($nombre_pdf != ""){unlink($pdfnew);}
      }
      $response = array("error"=>"$error","msj"=>"$msj");
      echo json_encode($response);
  }
  
  // PAYMENTS:
  function payment_invoice_getdata(){
    $error   = '0';
    $msj     = "";
    $fields  = "";
    $clave   = trim($_POST['clave']);
    $domroot = $_POST['domroot'];
    
    include("cn_usuarios.php");
  
    $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
    $transaccion_exitosa = true;
    $sql    = "SELECT iConsecutivo,iConsecutivoCompania, sNoReferencia, dTotal, iFinanciamiento, sDiasFinanciamiento, eStatus, ".
              "DATE_FORMAT(dFechaInvoice, '%m/%d/%Y') AS  dFechaInvoice, dSubtotal,dPctTax,dTax,dAnticipo,dBalance,dTipoCambio,sComentarios,sCveMoneda ".
              "FROM cb_invoices WHERE iConsecutivo = '$clave'";
    $result = $conexion->query($sql);
    $items  = $result->num_rows;   
    if ($items > 0) {     
        $items  = $result->fetch_assoc();
        $llaves = array_keys($items);
        $datos  = $items;
        
        foreach($datos as $i => $b){$fields .= "\$('#$domroot :input[name=".$i."]').val('".$datos[$i]."');";}  
        
        
        $query = "SELECT SUM(iMonto) AS iSaldoAplicado FROM cb_invoice_pago WHERE iConsecutivoInvoice = '$clave'";
        $res   = $conexion->query($query);
        
        if($res->num_rows == 0){$saldoA = 0;}
        else{
            $saldoA= $res->fetch_assoc();
            $saldoA= $saldoA['iSaldoAplicado'];
        }
        
        $saldoA  = number_format($saldoA,2,'.',','); 
        $fields .= "\$('#$domroot :input[name=iBalancePaid]').val('".$saldoA."');";
        
        $query = "SELECT iSaldoPendiente FROM cb_invoice_pago WHERE iConsecutivoInvoice = '1' ORDER BY iConsecutivoPago DESC LIMIT 1";
        $res   = $conexion->query($query);
        
        if($res->num_rows == 0){$saldoP = 0;}
        else{
            $saldoP= $res->fetch_assoc();
            $saldoP= $saldoP['iSaldoPendiente'];
        }

        $saldoP  = number_format($saldoP,2,'.',',');
        $fields .= "\$('#$domroot :input[name=iBalanceOutstanding]').val('".$saldoP."');"; 
    }
    $conexion->rollback();
    $conexion->close(); 
    $response = array("msj"=>"$msj","error"=>"$error","fields"=>"$fields");   
    echo json_encode($response);    
  }
  
  function payment_getdata(){
      
    include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $iConsecutivoInvoice = trim($_POST['iConsecutivoInvoice']);
    
      $sql    = "SELECT iConsecutivoPago,sNoPago, sDescripcion, DATE_FORMAT(dFechaPago, '%m/%d/%Y') AS dFechaPago, iMonto, sCveMoneda 
                 FROM cb_invoice_pago WHERE iConsecutivoInvoice = '$iConsecutivoInvoice' ORDER BY iConsecutivoPago DESC";
      $result = $conexion->query($sql);
      $rows   = $result->num_rows;    
      
      if ($rows > 0) {  
            $items  = mysql_fetch_all($result); //print_r($items);  
            foreach($items as $i => $l){ 
                $btns_der = "<div class=\"btn-icon edit btn-left\" title=\"Edit\"><i class=\"fa fa-pencil-square-o\"></i></div>";
                if($i == 0){$btns_der.="<div class=\"btn-icon trash btn-left\" title=\"Delete\"><i class=\"fa fa-trash\"></i></div>";}
                $htmlTabla .= "<tr>".
                              "<td id=\"pay_".$items[$i]['iConsecutivoPago']."\">".$items[$i]['sNoPago']."</td>". 
                              "<td class=\"text-center\">".$items[$i]['dFechaPago']."</td>".
                              "<td>".$items[$i]['sDescripcion']."</td>". 
                              "<td class=\"text-right\">\$ ".number_format($items[$i]['iMonto'],2,'.',',')." ".$items[$i]['sCveMoneda']."</td>".
                              "<td>".$btns_der."</td>".
                              "</tr>";
            }
      } 
      else { $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";} 
      
      $conexion->rollback();
      $conexion->close(); 
              
      $response = array("tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error");   
      echo json_encode($response);
  }
  
  

?>
