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
        $sql = "SELECT A.iConsecutivo,sNoReferencia, sNombreCompania, sNombreContacto, dTotal, iFinanciamiento, sDiasFinanciamiento, eStatus, iOnRedList, DATE_FORMAT(dFechaInvoice, '%m/%d/%Y') AS  dFechaInvoice, sCveMoneda ".   
               "FROM cb_invoices A ".
               "LEFT JOIN ct_companias B ON A.iConsecutivoCompania = B.iConsecutivo ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
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
                     $botones = "";
                     switch($items['eStatus']){
                         case 'EDITABLE': 
                            $btns_left  = "<div class=\"btn_apply btn-text-2 send btn-center\" title=\"Apply Invoice\" style=\"width: 70px;text-transform: uppercase;\"><i class=\"fa fa-check-circle\"></i><span>Apply</span></div> "; 
                            $btns_right = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"Edit\"><i class=\"fa fa-pencil-square-o\"></i></div>".
                                          "<div class=\"btn_delete btn-icon trash btn-left\" title=\"Delete\"><i class=\"fa fa-trash\"></i></div>";
                         break;
                         case 'APPLIED': 
                            $btns_left = "<div class=\"btn_send_invoice btn-icon send-email btn-left\" title=\"Send to:\"><i class=\"fa fa-envelope\"></i></div>"; 
                            $btns_left.= "<div class=\"btn_pdf btn-icon pdf btn-left\" title=\"Open Invoice PDF\"><i class=\"fa fa-file-pdf-o\"></i></div>"; 
                            //$btns_right= "<div class=\"btn_cancel btn-icon trash btn-left\" title=\"Cancel Invoice\"><i class=\"fa fa-times-circle\"></i></div>";
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
              "DATE_FORMAT(dFechaInvoice, '%m/%d/%Y') AS  dFechaInvoice, dSubtotal,dPctTax,dTax,dAnticipo,dBalance,dTipoCambio,sComentarios,sCveMoneda ".
              "FROM cb_invoices WHERE iConsecutivo = '$clave'";
    $result = $conexion->query($sql);
    $items  = $result->num_rows;   
    if ($items > 0) {     
        $items  = $result->fetch_assoc();
        $llaves = array_keys($items);
        $datos  = $items;
        
        foreach($datos as $i => $b){$fields .= "\$('$domroot :input[id=".$i."]').val('".$datos[$i]."');";}  
    }
    $conexion->rollback();
    $conexion->close(); 
    $response = array("msj"=>"$msj","error"=>"$error","fields"=>"$fields");   
    echo json_encode($response);
  }
  
  function save_data(){
      
      include("funciones_genericas.php");
      $error   = '0'; 
      $valores = array();
      $campos  = array(); 
      $msj     = "";
      
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      $_POST['dFechaInvoice'] = date('Y-m-d',strtotime(str_replace("/","-",$_POST['dFechaInvoice']))); 
      
      //Validar que la referencia no este repetida:
      $query  = "SELECT COUNT(iConsecutivo) AS total FROM cb_invoices WHERE sNoReferencia ='".$_POST['sNoReferencia']."' AND bEliminado='0'";
      $result = $conexion->query($query);
      $valida = $result->fetch_assoc();
      
      if($valida['total'] != '0'){
          if($_POST["edit_mode"] != 'true'){
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
      }else if($_POST["edit_mode"] != 'true'){
         foreach($_POST as $campo => $valor){
           if($campo != "accion" && $campo != "edit_mode" && $campo != "iConsecutivo"){ //Estos campos no se insertan a la tabla
                array_push($campos ,$campo); 
                array_push($valores, trim($valor));
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
             if($_POST["edit_mode"] == 'true'){
                array_push($valores,"sReceptorNombre='".trim($items['sReceptorNombre'])."'"); 
                array_push($valores,"sReceptorDireccion='".trim($items['sReceptorDireccion'])."'");
             }else{
                array_push($campos ,'sReceptorNombre');    array_push($valores, trim($items['sReceptorNombre'])); 
                array_push($campos ,'sReceptorDireccion'); array_push($valores, trim($items['sReceptorDireccion']));
             }
          }
          
          if($_POST["edit_mode"] == 'true'){
              
            array_push($valores ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
            array_push($valores ,"sIP='".$_SERVER['REMOTE_ADDR']."'");
            array_push($valores ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'");
            
            $sql = "UPDATE cb_invoices SET ".implode(",",$valores)." WHERE iConsecutivo = '".$_POST['iConsecutivo']."'";
            $conexion->query($sql);
            
            if($conexion->affected_rows < 0){$transaccion_exitosa = false;}
            else{$msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The data has been updated successfully.</p>';}
            
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
            else{$msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The data has been saved successfully!</p>';}
            
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
      $response = array("error"=>"$error","msj"=>"$msj");
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
      
      if($clave == ""){$transaccion_exitosa = false; $msj = "Error to calculate the invoice total, please try again later.";$error = 1;}
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
      
      $query = "UPDATE cb_invoices SET bEliminado='1' WHERE iConsecutivo='".$clave."'";
      $conexion->query($query);
      
      if($conexion->affected_rows < 0){$error = 1; $msj = "Error to delete data, please try again.";}
      else{$msj = "The data has been deleted successfully!";}
      
      $error == 0 ? $conexion->commit() : $conexion->rollback();
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
    
      $sql    = "SELECT iConsecutivoDetalle, CONCAT(sClave,' - ',sDescripcion) AS sDescripcion, iCantidad, iPrecioUnitario, iPctImpuesto, iImpuesto, iPrecioExtendido ".
                "FROM cb_invoice_detalle WHERE iConsecutivoInvoice = '$iConsecutivoInvoice'";
      $result = $conexion->query($sql);
      $rows   = $result->num_rows;    
      
      if ($rows > 0) {    
            while ($items = $result->fetch_assoc()) { 

                $iFolio = 1;
                $htmlTabla .= "<tr>".
                              "<td id=\"srv_".$items['iConsecutivoDetalle']."\">".$iFolio."</td>". 
                              "<td>".$items['sDescripcion']."</td>".
                              "<td class=\"text-center\">".$items['iCantidad']."</td>". 
                              "<td class=\"text-right\">\$ ".number_format($items['iPrecioUnitario'],2,'.',',')."</td>".
                              "<td class=\"text-right\">\$ ".number_format($items['iImpuesto'],2,'.',',')." ".$items['sCveMoneda']."</td>". 
                              "<td class=\"text-right\">\$ ".number_format($items['iPrecioExtendido'],2,'.',',')." ".$items['sCveMoneda']."</td>". 
                              "<td>".
                                "<div class=\"btn-icon edit btn-left\" title=\"Edit\"><i class=\"fa fa-pencil-square-o\"></i></div>".
                                "<div class=\"btn-icon trash btn-left\" title=\"Delete\"><i class=\"fa fa-trash\"></i></div>";
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
              "iImpuesto,iPrecioExtendido,iEndorsementsApply, sComentarios ".
              "FROM cb_invoice_detalle WHERE iConsecutivoDetalle = '$clave'";
    $result = $conexion->query($sql);
    $items  = $result->num_rows;   
    if ($items > 0) {     
        $items  = $result->fetch_assoc();
        $llaves = array_keys($items);
        $datos  = $items;
        
        foreach($datos as $i => $b){
            if($i == 'sComentarios'){$fields .= "\$('$domroot [name=".$i."]').val('".utf8_decode($datos[$i])."')";}else
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
             
          $htmlTabla .= "<tr $class>".
                        "<td id=\"iCve_".$items['iConsecutivo']."\">".$detalle."</td>". 
                        "<td>".$policies."</td>".
                        "<td class=\"text-center\">".$txtFechaApp."</td>". 
                        "<td title='$titleEstatus'>".$estado."</td>".
                        "<td><div class=\"btn_delete btn-icon trash btn-left\" title=\"Cancel Add\"><i class=\"fa fa-times-circle\"></i></div></td>".                                                                                                                                                                                                                       
                        "</tr>";
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

      $query = "DELETE FROM cb_invoice_detalle_endoso WHERE iConsecutivoEndoso='".$claveEndoso."' AND iConsecutivoDetalle='".$claveDetalle."'";
      $conexion->query($query);
      
      if($conexion->affected_rows <= 0){$error = '1'; $msj = "Error to delete the data, please try again later.";}
      else{$msj = "Data has been deleted successfully!";}
      
      $error == '0' ? $conexion->commit() : $conexion->rollback();
      $conexion->close();
      $response = array("error"=>"$error","msj"=>"$msj");
      echo json_encode($response); 
       
  }
  
  // PDF & E-MAIL:
  

?>
