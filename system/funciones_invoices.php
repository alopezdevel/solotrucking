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
                            $btns_left  = "<div class=\"btn_apply btn-text send btn-center\" title=\"Apply Invoice\" style=\"width: 60px;\"><i class=\"fa fa-check-circle\"></i><span>Apply</span></div> "; 
                            $btns_right = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"Edit\"><i class=\"fa fa-pencil-square-o\"></i></div>".
                                          "<div class=\"btn_delete btn-icon trash btn-left\" title=\"Delete\"><i class=\"fa fa-trash\"></i></div>";
                         break;
                         case 'APPLIED': 
                            $btns_left = "<div class=\"btn_send_invoice btn-icon send-email btn-left\" title=\"Send to:\"><i class=\"fa fa-envelope\"></i></div>"; 
                            $btns_left.= "<div class=\"btn_pdf btn-icon pdf btn-left\" title=\"Open Invoice PDF\"><i class=\"fa fa-file-pdf-o\"></i></div>"; 
                            $btns_right= "<div class=\"btn_cancel btn-icon trash btn-left\" title=\"Cancel Invoice\"><i class=\"fa fa-times-circle\"></i></div>";
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
            $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The data has been updated successfully.</p>'; 
          }else{
            array_push($campos ,"dFechaIngreso");
            array_push($valores ,date("Y-m-d H:i:s"));
            array_push($campos ,"sIP");
            array_push($valores ,$_SERVER['REMOTE_ADDR']);
            array_push($campos ,"sUsuarioIngreso");
            array_push($valores ,$_SESSION['usuario_actual']);
            $sql = "INSERT INTO cb_invoices (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')";
            $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The data has been saved successfully!</p>';
          }
          
         
          $conexion->query($sql);
          $conexion->affected_rows < 1 ? $transaccion_exitosa = false : $transaccion_exitosa = true;
          
          if($transaccion_exitosa){$conexion->commit();$conexion->close();}
          else{
            $conexion->rollback();
            $conexion->close();
            $msj = "A general system error ocurred : internal error";
            $error = "1";
          }
          if($transaccion_exitosa)$msj = "The data has been saved successfully."; 
      }
      $response = array("error"=>"$error","msj"=>"$msj");
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
  
?>
