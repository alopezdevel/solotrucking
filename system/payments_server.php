<?php
  session_start();
  // Generic functions lib 
  include("functiones_genericas.php"); 
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId 
    
  //INVOICES:
  function get_payments(){
     
    include("cn_usuarios.php");
    $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
    $transaccion_exitosa = true;
    
    $registros_por_pagina = $_POST["registros_por_pagina"];
    $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
    $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
        
    //Filtros de informacion //
    $filtroQuery   = " WHERE A.iConsecutivoPago IS NOT NULL AND A.bEliminado = '0' AND B.iDeleted='0'";
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
    $query_rows = "SELECT COUNT(A.iConsecutivoPago) AS total FROM cb_pago A ".
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
        $sql = "SELECT A.iConsecutivoPago,sNoPago, sNombreCompania, B.sEmailContacto, sNombreContacto, sDescripcion, iMonto, iOnRedList, DATE_FORMAT(dFechaPago, '%m/%d/%Y') AS  dFechaPago, sCveMoneda,sMetodoPago, sNombreArchivo ".   
               "FROM      cb_pago      AS A ".
               "LEFT JOIN ct_companias AS B ON A.iConsecutivoCompania = B.iConsecutivo ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
        $result = $conexion->query($sql);
        $rows   = $result->num_rows;    
        if ($rows > 0) {    
            while ($items = $result->fetch_assoc()) { 
                
                     $btns_right = "";
                     if($items['sNombreArchivo'] != ""){
                         $btns_right .= "<div class=\"btn-icon btn-left pdf\" title=\"Open file\" onclick=\"window.open('open_pdf.php?idfile=".$items['iConsecutivoPago']."&type=pago');\"><i class=\"fa fa-file-pdf-o\"></i><span></span></div>";
                     }
                     $btns_right.= "<div class=\"btn_edit btn-icon edit btn-left\" title=\"Edit\"><i class=\"fa fa-pencil-square-o\"></i></div>".
                                   "<div class=\"btn_delete btn-icon trash btn-left\" title=\"Delete\"><i class=\"fa fa-trash\"></i></div>";
                     
                     //Redlist:
                     if($items['iOnRedList'] == '1'){
                        $redlist_class = "class=\"row_red\"";
                        $redlist_icon = "<i class=\"fa fa-star\" style=\"color:#e8051b;margin-right:4px;\"></i>"; 
                     }else{
                        $redlist_icon = ""; 
                        $redlist_class = "";
                     }
                     $botones = "";
                     
                     $htmlTabla .= "<tr ".$redlist_class.">".
                                   "<td id=\"pay_".$items['iConsecutivoPago']."\">".$items['sNoPago']."</td>".
                                   "<td>".$items['sNombreCompania']."</td>". 
                                   "<td class=\"text-center\">".$items['dFechaPago']."</td>".
                                   "<td>".$items['sDescripcion']."</td>".
                                   "<td class=\"text-center\">".$items['sMetodoPago']."</td>".
                                   "<td class=\"text-right\">\$ ".number_format($items['iMonto'],2,'.',',')." ".$items['sCveMoneda']."</td>".  
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
    $sql    = "SELECT iConsecutivoPago,iConsecutivoCompania, sNoPago, iMonto, DATE_FORMAT(dFechaPago, '%m/%d/%Y') AS  dFechaPago, ".
              "sDescripcion,sComentarios,sCveMoneda,sMetodoPago, sNombreArchivo, sTipoArchivo, iTamanioArchivo ".
              "FROM cb_pago WHERE iConsecutivoPago = '".$clave."'";
    $result = $conexion->query($sql);
    $items  = $result->num_rows;   
    if ($items > 0) {     
        $items  = $result->fetch_assoc();
        $llaves = array_keys($items);
        $datos  = $items;
        
        foreach($datos as $i => $b){  
            if($i != "sNombreArchivo" && $i != "sTipoArchivo" && $i != "iTamanioArchivo"){
                 if($i == 'iMonto'){$datos[$i] = number_format($datos[$i],2,'.','');}
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
      
      $error    = '0'; 
      $valores  = array();
      $campos   = array(); 
      $msj      = "";
      $_POST["iConsecutivoPago"] == "" ? $edit_mode= 'false' : $edit_mode = 'true';
    
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      $valid_user = valid_user($_SESSION['usuario_actual']);

      if(!($valid_user)){
          $error  = '1';
          $mensaje= "This user does not have the privileges to modify or add data to the system.";
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
      
      
      if($edit_mode == 'true' && $error == '0'){
          //Validar si esta aplicado:
          $query = "SELECT COUNT(iConsecutivoPago) AS total FROM cb_invoice_pago WHERE iConsecutivoPago='".trim($_POST["iConsecutivoPago"])."'";
          $result= $conexion->query($query);
          $items = $result->fetch_assoc();
          
          if($items['total'] != 0){
              $error = '1';
              $msj   = 'The payment can not be updated or deleted because It is already applied to an invoice/endorsement/policy.';
          } 
      }
      
      if($error == '0'){
         
           $_POST['dFechaPago'] =  date('Y-m-d',strtotime(trim($_POST['dFechaPago'])));
           
           //Validar que la referencia no este repetida:
           $query  = "SELECT COUNT(iConsecutivoPago) AS total FROM cb_pago WHERE sNoPago ='".$_POST['sNoPago']."' AND iConsecutivoCompania ='".$_POST['iConsecutivoCompania']."' AND bEliminado='0'";
           $result = $conexion->query($query);
           $valida = $result->fetch_assoc();
          
           if($valida['total'] != '0'){
              if($edit_mode != 'true'){
                  $msj   = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>
                            Error: The Payment No. that you trying to add already exists. Please verify the data.</p>';
                  $error = '1';
              }else{
                 foreach($_POST as $campo => $valor){
                    if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivoPago" ){ //Estos campos no se insertan a la tabla
                        
                        if($campo == 'sNoPago' || $campo == 'sMetodoPago'){$valor = strtoupper($valor);}
                        
                        array_push($valores,"$campo='".trim($valor)."'");
                    }
                 }   
              }
           }else if($_POST["edit_mode"] != 'true'){
             foreach($_POST as $campo => $valor){
               if($campo != "accion" && $campo != "edit_mode" && $campo != "iConsecutivoPago"){ //Estos campos no se insertan a la tabla
                    if($campo == 'sNoPago' || $campo == 'sMetodoPago'){$valor = strtoupper($valor);}
                    array_push($campos ,$campo); 
                    array_push($valores, trim($valor));
               }
             }  
          }
      }
      
      # Datos
      if($error == '0'){
          
          if($edit_mode == 'true'){
              
            array_push($valores ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
            array_push($valores ,"sIPIngreso='".$_SERVER['REMOTE_ADDR']."'");
            array_push($valores ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'");
            
            $sql = "UPDATE cb_pago SET ".implode(",",$valores)." WHERE iConsecutivoPago = '".$_POST['iConsecutivoPago']."'";
            $conexion->query($sql);
  
            if($conexion->affected_rows < 0){$transaccion_exitosa = false;}
            else{$msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The data has been updated successfully.</p>';}
            
          }
          else{
            array_push($campos ,"dFechaIngreso");
            array_push($valores ,date("Y-m-d H:i:s"));
            array_push($campos ,"sIPActualizacion");
            array_push($valores ,$_SERVER['REMOTE_ADDR']);
            array_push($campos ,"sUsuarioIngreso");
            array_push($valores ,$_SESSION['usuario_actual']);
            
            $sql = "INSERT INTO cb_pago (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')";
            $conexion->query($sql);
            
            if($conexion->affected_rows < 1){$transaccion_exitosa = false;}
            else{$msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The data has been saved successfully!</p>';}
            
          }
          
          if($transaccion_exitosa){$conexion->commit();$conexion->close();}
          else{
            $error_mysql = $conexion->error;  
            $conexion->rollback();
            $conexion->close();
            $msj   = "Error saving data, please try again.";
            $error = "1";
          }
          if($transaccion_exitosa)$msj = "The data has been saved successfully."; 
      }
      $response = array("error"=>"$error","msj"=>"$msj","error_mysql"=>"$error_mysql");
      echo json_encode($response);
  }
  
  

?>
