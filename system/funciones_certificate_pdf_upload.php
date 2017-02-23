<?php
  session_start();
  // Generic functions lib 
  include("functiones_genericas.php"); 
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId 
  
  function get_companies_certificates(){
    include("cn_usuarios.php");
    $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
    $transaccion_exitosa = true;
    
    $registros_por_pagina = $_POST["registros_por_pagina"];
    $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
    $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
        
    //Filtros de informacion //
    $filtroQuery = " WHERE A.iConsecutivo IS NOT NULL";
    $array_filtros = explode(",",$_POST["filtroInformacion"]);
    foreach($array_filtros as $key => $valor){
        if($array_filtros[$key] != ""){
            $campo_valor = explode("|",$array_filtros[$key]);
            $campo_valor[0] == 'A.iConsecutivo' ? $filtroQuery.= " AND  ".$campo_valor[0]."='".$campo_valor[1]."' " : $filtroQuery == "" ? $filtroQuery.= " AND  ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'": $filtroQuery.= " AND ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'";
        }
    }
    // ordenamiento//
    $ordenQuery = " ORDER BY ".$_POST["ordenInformacion"]." ".$_POST["sortInformacion"];
    //contando registros // 
    $query_rows = "SELECT COUNT(A.iConsecutivo) AS total FROM  ct_companias A 
                   LEFT JOIN cb_certificate_file C ON A.iConsecutivo = C.iConsecutivoCompania ".$filtroQuery;
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
        $sql = "SELECT  C.hContenidoDocumentoDigitalizadoAdd, A.iConsecutivo as id, sNombreCompania, eEstatusCertificadoUpload as estatus_upload, sUsdot,
                CASE WHEN eEstatusCertificadoUpload = '0' then 'Pending' Else 'Loaded' END AS  hActivado, iOnRedList, 
                DATE_FORMAT(C.dFechaIngreso,'%m/%d/%Y') AS dFechaIngreso, DATE_FORMAT(C.dFechaActualizacion,'%m/%d/%Y') AS dFechaActualizacion, DATE_FORMAT(C.dFechaVencimiento,'%m/%d/%Y') AS dFechaVencimiento  
                FROM  ct_companias A 
                LEFT JOIN cb_certificate_file C ON A.iConsecutivo = C.iConsecutivoCompania ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior; 
        $result = $conexion->query($sql);
        $rows   = $result->num_rows; 
        if($rows > 0){     
            while ($items = $result->fetch_assoc()){                   
                     $color = "#800000";
                     //Redlist:
                     if($items['iOnRedList'] == '1'){
                        $redlist_class = "class=\"row_red\"";
                        $redlist_icon = "<i class=\"fa fa-star\" style=\"color:#e8051b;margin-right:4px;\"></i>"; 
                     }else{
                        $redlist_icon = ""; 
                        $redlist_class = "";
                     }             
                     
                     //Variables para usar Preview PDF:
                     $variables = "";
                     $variables = "?id=".$items['id']."&ca=COMPANY%20NAME&cb=NUMBER%20AND%20ADDRESS&cc=CITY&cd=STATE&ce=ZIPCODE";
                     
                     $htmlTabla .= "<tr ".$redlist_class."><td>".$items['id']."</td>".
                                   "<td>".$redlist_icon.$items['sNombreCompania']."</td>".
                                   "<td>".$items['sUsdot']."</td>".
                                   "<td>".$items['dFechaActualizacion']."</td>".
                                   "<td>".$items['dFechaVencimiento']."</td>";
    
                                   if($items['hActivado'] == "Loaded"){$color = "#000080";$items['hActivado'] = "<i class=\"fa fa-check-circle\" style=\"color:#6ddc00;\"></i> Certificate Loaded";}
                                   else{$items['hActivado'] = "<i class=\"fa fa-times-circle\"></i> Certificate not Loaded";}
                                       
                                   if($items['hContenidoDocumentoDigitalizadoAdd'] != ""){$items['hActivado'] =  $items['hActivado']. ",<br /><i class=\"fa fa-check-circle\"></i> Additional remarks schedule Loaded";}                                 
                                   $htmlTabla .= "<td><b><font color ='$color'> ".$items['hActivado']."</font></b></td>";
                                   $htmlTabla .= "<td>".
                                                     "<div class=\"btn_edit btn-icon edit btn-left\" title=\"Edit Certificate\"><i class=\"fa fa-pencil-square-o\"></i> <span></span></div>".
                                                     "<div class=\"btn-icon btn-left pdf\" title=\"View the PDF\" onclick=\"window.open('pdf_certificate.php".$variables."');\"><i class=\"fa fa-file-pdf-o\"></i><span></span></div>".
                                                  "</td>";  
                                                                                                                                                                                                                                          
                                "</tr>";
                    
            }
            $conexion->rollback();
            $conexion->close();                                                                                                                                                                       
        }else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";}
    }
    $response = array("total"=>"$paginas_total","pagina"=>"$pagina_actual","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
    echo json_encode($response);
  }  
  function upload_certificate(){
       
       $error = "0";
       include("cn_usuarios.php");
       $conexion->autocommit(FALSE);
       $transaccion_exitosa = true;
       
       $iConsecutivoCompania =  $_POST['iConsecutivoCompania'];
       if($iConsecutivoCompania != ''){
           
           $FechaVencimiento = format_date($_POST['dFechaVencimiento']);
           $oFichero = fopen($_FILES['userfile']["tmp_name"], 'r'); 
           $sContenido = fread($oFichero, filesize($_FILES['userfile']["tmp_name"]));  
           $sContenido =  $conexion->real_escape_string($sContenido);
           #armando nombre del archivo:
           $_POST['sNombreCompania'] != '' ? $_POST['sNombreCompania'] = str_replace(' ','_',trim(strtolower($_POST['sNombreCompania']))) : $_POST['sNombreCompania'] = $_POST['iConsecutivoCompania'].'_co';
           $name_file = 'cert_'.$_POST['sNombreCompania'].'.pdf';
           
           if($_POST['iConsecutivo'] != ''){
                  $valores_update  = ",dFechaActualizacion='".date("Y-m-d H:i:s")."'";
                  $valores_update .= ",sIP='".$_SERVER['REMOTE_ADDR']."'";
                  $valores_update .= ",sUsuarioActualizacion='".$_SESSION['usuario_actual']."'";
                $sql = "UPDATE cb_certificate_file SET dFechaVencimiento ='$FechaVencimiento', sNombreArchivo ='".$name_file."', sTipoArchivo ='".$_FILES['userfile']["type"]."', iTamanioArchivo ='".$_FILES['userfile']["size"]."', hContenidoDocumentoDigitalizado='$sContenido'".$valores_update." WHERE iConsecutivo ='".$_POST['iConsecutivo']."'"; 
           }else{
                $sql = "INSERT INTO cb_certificate_file (iConsecutivoCompania,dFechaVencimiento,sNombreArchivo,sTipoArchivo,iTamanioArchivo,hContenidoDocumentoDigitalizado,dFechaIngreso,sIP,sUsuarioIngreso,dFechaActualizacion)
                        VALUES('$iConsecutivoCompania','$FechaVencimiento','$name_file','".$_FILES['userfile']["type"]."','".$_FILES['userfile']["size"]."','$sContenido','".date("Y-m-d H:i:s")."', '".$_SERVER['REMOTE_ADDR']."','".$_SESSION['usuario_actual']."','".date("Y-m-d H:i:s")."')"; 
           }
           $conexion->query($sql);
           $conexion->affected_rows < 1 ? $transaccion_exitosa = false : $transaccion_exitosa = true;
           
           if($transaccion_exitosa){
                #actualizar bandera en tabla de companias:
                if($_POST['iConsecutivo'] == ''){
                    $id_file = $conexion->insert_id;
                    $sql = "UPDATE ct_companias SET eEstatusCertificadoUpload = '1'  WHERE iConsecutivo = '".$iConsecutivoCompania."'  ";
                    $conexion->query($sql);
                } 
                $conexion->commit();
                $conexion->close();
                $mensaje = "The file was uploaded successfully.";  
           }else{
                $conexion->rollback();
                $conexion->close();
                $mensaje = "A general system error ocurred : internal error";
                $error = "1";
           }
       
       
   }else{
       $error = '1';
       $mensaje = "A general system error ocurred : internal error";
   } 
   
   $response = array("mensaje"=>"$mensaje","error"=>"$error", "id_file"=>"$id_file", "name_file" => "$name_file"); 
   echo json_encode($response); 
       
  }
  function upload_additional(){
       
       $error = "0";
       include("cn_usuarios.php");
       $conexion->autocommit(FALSE);
       $transaccion_exitosa = true;
       
       $iConsecutivo =  $_POST['iConsecutivo'];
       if($iConsecutivo != ''){
           $oFichero = fopen($_FILES['userfile']["tmp_name"], 'r'); 
           $sContenido = fread($oFichero, filesize($_FILES['userfile']["tmp_name"]));  
           $sContenido =  $conexion->real_escape_string($sContenido);
           #armando nombre del archivo:
           $_POST['sNombreCompania'] != '' ? $_POST['sNombreCompania'] = str_replace(' ','_',trim(strtolower($_POST['sNombreCompania']))) : $_POST['sNombreCompania'] = $_POST['iConsecutivoCompania'].'_co';
           $name_file = 'additional_'.$_POST['sNombreCompania'].'.pdf';
           
           $sql = "UPDATE cb_certificate_file SET sNombreArchivoAdd ='".$name_file."', sTipoArchivoAdd ='".$_FILES['userfile']["type"]."', iTamanioArchivoAdd ='".$_FILES['userfile']["size"]."', hContenidoDocumentoDigitalizadoAdd='$sContenido' WHERE iConsecutivo ='".$_POST['iConsecutivo']."' AND iConsecutivoCompania = '".$_POST['iConsecutivoCompania']."'"; 
           $conexion->query($sql);
           $conexion->affected_rows < 1 ? $transaccion_exitosa = false : $transaccion_exitosa = true;
           
           if($transaccion_exitosa){
                $conexion->commit();
                $conexion->close();
                $mensaje = "The file was uploaded successfully.";  
           }else{
                $conexion->rollback();
                $conexion->close();
                $mensaje = "A general system error ocurred : internal error";
                $error = "1";
           }
           
           
       }else{
           $error = '1';
           $mensaje = "A general system error ocurred : internal error";
       } 
       
       $response = array("mensaje"=>"$mensaje","error"=>"$error", "name_file" => "$name_file"); 
       echo json_encode($response); 
       
  }
  function get_files(){
      $error = '0';
      $msj = "";
      $fields = "";
      $clave = trim($_POST['clave']);
      $domroot = $_POST['domroot'];
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);
      
      $sql = "SELECT iConsecutivo, sNombreArchivo, sNombreArchivoAdd  FROM cb_certificate_file 
              WHERE  iConsecutivoCompania = '".$clave."'";
      $result = $conexion->query($sql); 
      $items_files = $result->fetch_assoc();
      if($items_files["sNombreArchivo"] != ''){
            $fields .= "\$('#$domroot :input[id=txtsCertificatePDF]').val('".$items_files['sNombreArchivo']."');";
            $fields .= "\$('#$domroot :input[id=iConsecutivoCertificate]').val('".$items_files['iConsecutivo']."');";  
      }
      if($items_files["sNombreArchivoAdd"] != ''){
            $fields .= "\$('#$domroot :input[id=txtsAdditionalPDF]').val('".$items_files['sNombreArchivoAdd']."');";  
      }
      $conexion->rollback();
      $conexion->close(); 
      $response = array("msj"=>"$msj","error"=>"$error","fields"=>"$fields");   
      echo json_encode($response);
                                                                                                                         
  }
?>
