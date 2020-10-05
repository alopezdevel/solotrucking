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
        $sql = "SELECT  C.hContenidoDocumentoDigitalizadoAdd, A.iConsecutivo as id, sNombreCompania, eEstatusCertificadoUpload as estatus_upload, sUsdot, eOrigenCertificado,
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
                     
                     $fecha_actual  = strtotime(date("d-m-Y",time()));
                     $fecha_entrada = strtotime($items['dFechaVencimiento']);
                     
                     $fecha_entrada < $fecha_actual ? $vencido = true : $vencido = false;
                     
                     //Revisar origen del certificado:
                     if($items['eOrigenCertificado'] == "LAYOUT"){
                        if($items['hActivado'] == "Loaded"){
                            if($vencido){$estatusC = '<i class="fa fa-times-circle" style="color:#8a0000;"></i> Certificate Uploaded Expired - ('.$items['eOrigenCertificado'].')';}
                            else{$estatusC = '<i class="fa fa-check-circle" style="color:#6ddc00;"></i> Certificate Uploaded & Valid - ('.$items['eOrigenCertificado'].')';}
                        }
                        else{
                            $estatusC = '<i class="fa fa-times-circle" style="color:#8a0000;"></i> Certificate not Uploaded ('.$items['eOrigenCertificado'].')';
                        }    
                     }else{
                        if($vencido){$estatusC = '<i class="fa fa-times-circle" style="color:#8a0000;"></i> Certificate Date Expired ('.$items['eOrigenCertificado'].')';}
                        else{$estatusC = '<i class="fa fa-check-circle" style="color:#6ddc00;"></i> Certificate Date Valid ('.$items['eOrigenCertificado'].')';}  
                     }
                     
                     $htmlTabla .= "<tr ".$redlist_class."><td>".$items['id']."</td>".
                                   "<td>".$redlist_icon.$items['sNombreCompania']."</td>".
                                   "<td>".$items['sUsdot']."</td>".
                                   "<td>".$items['dFechaActualizacion']."</td>".
                                   "<td>".$items['dFechaVencimiento']."</td>";   
                                   //if($items['hContenidoDocumentoDigitalizadoAdd'] != ""){$items['hActivado'] =  $items['hActivado']. ",<br /><i class=\"fa fa-check-circle\"></i> Additional remarks schedule Loaded";}                                 
                     $htmlTabla .= "<td>$estatusC</td>";
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
       
       $iConsecutivoCompania   =  $_POST['iConsecutivoCompania'];
       $_POST['iConsecutivo'] != '' ? $edit_mode = "true" : $edit_mode = "false";
       $_POST['sDescripcionOperaciones'] != "" ? $_POST['sDescripcionOperaciones'] = preg_replace("/\r\n|\r|\n/",'\n',trim($_POST['sDescripcionOperaciones'])) : "";
       $campos  = array();
       $valores = array();
       
       if($iConsecutivoCompania != ''){
           
           $_POST['dFechaVencimiento'] = format_date($_POST['dFechaVencimiento']);
           $_POST['sNombreCompania']   = clean_string($_POST['sNombreCompania']);
           
           // Si el archivo existe, entonces:
           if(isset($_FILES['userfile']["tmp_name"])){
               $oFichero   = fopen($_FILES['userfile']["tmp_name"], 'r'); 
               $sContenido = fread($oFichero, filesize($_FILES['userfile']["tmp_name"]));  
               $sContenido =  $conexion->real_escape_string($sContenido);
               #armando nombre del archivo:
               $_POST['sNombreCompania'] != '' ? $_POST['sNombreCompania'] = str_replace(' ','_',trim(strtolower($_POST['sNombreCompania']))) : $_POST['sNombreCompania'] = $_POST['iConsecutivoCompania'].'_co';
               $name_file = 'cert_'.$_POST['sNombreCompania'].'.pdf'; 
               
               //Asignar al POST:
               $_POST['sNombreArchivo']                  = $name_file; 
               $_POST['iTamanioArchivo']                 = $_FILES['userfile']["size"];
               $_POST['sTipoArchivo']                    = $_FILES['userfile']["type"];
               $_POST['hContenidoDocumentoDigitalizado'] = $sContenido;
                      
           }
           
           #UPDATE
           if($edit_mode == "true"){
                foreach($_POST as $campo => $valor){
                    if($campo != "accion" && $campo != "edit_mode" && $campo != "iConsecutivo" && $campo != "sNombreCompania" && $campo != "txtsCertificatePDF"){ //Estos campos no se insertan a la tabla
                        array_push($valores,"$campo='".($valor)."'");    
                    }
                } 
                
                // agregar campos adicionales:
                array_push($valores,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
                array_push($valores,"sIP='".$_SERVER['REMOTE_ADDR']."'");
                array_push($valores,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'"); 
                
                $sql     = "UPDATE cb_certificate_file SET ".implode(",",$valores)." WHERE iConsecutivo='".$_POST['iConsecutivo']."' AND iConsecutivoCompania = '$iConsecutivoCompania'";   
                $mensaje = "The data was updated successfully."; 
           }
           #INSERT
           else{
                foreach($_POST as $campo => $valor){
                    if($campo != "accion" && $campo != "edit_mode" && $campo != "iConsecutivo" && $campo != "sNombreCompania" && $campo != "txtsCertificatePDF"){ //Estos campos no se insertan a la tabla
                        array_push($campos , $campo);
                        array_push($valores, ($valor));      
                    }
                }
                // agregar campos adicionales:
                array_push($campos , "dFechaIngreso"); array_push($valores,date("Y-m-d H:i:s")); 
                array_push($campos , "sIP");           array_push($valores,$_SERVER['REMOTE_ADDR']);
                array_push($campos , "dFechaActualizacion"); array_push($valores,date("Y-m-d H:i:s")); 
                array_push($campos , "sUsuarioIngreso");     array_push($valores,$_SESSION['usuario_actual']);
                
                $sql     = "INSERT INTO cb_certificate_file (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')"; 
                $mensaje = "The data was saved successfully."; 
           }

           /*if($_POST['iConsecutivo'] != ''){
                  $valores_update  = ",dFechaActualizacion='".date("Y-m-d H:i:s")."'";
                  $valores_update .= ",sIP='".$_SERVER['REMOTE_ADDR']."'";
                  $valores_update .= ",sUsuarioActualizacion='".$_SESSION['usuario_actual']."'";
                $sql = "UPDATE cb_certificate_file SET dFechaVencimiento ='$FechaVencimiento', sNombreArchivo ='".$name_file."', sTipoArchivo ='".$_FILES['userfile']["type"]."', iTamanioArchivo ='".$_FILES['userfile']["size"]."', hContenidoDocumentoDigitalizado='$sContenido'".$valores_update." WHERE iConsecutivo ='".$_POST['iConsecutivo']."'"; 
           }else{
                $sql = "INSERT INTO cb_certificate_file (iConsecutivoCompania,dFechaVencimiento,sNombreArchivo,sTipoArchivo,iTamanioArchivo,hContenidoDocumentoDigitalizado,dFechaIngreso,sIP,sUsuarioIngreso,dFechaActualizacion)
                        VALUES('$iConsecutivoCompania','$FechaVencimiento','$name_file','".$_FILES['userfile']["type"]."','".$_FILES['userfile']["size"]."','$sContenido','".date("Y-m-d H:i:s")."', '".$_SERVER['REMOTE_ADDR']."','".$_SESSION['usuario_actual']."','".date("Y-m-d H:i:s")."')"; 
           }*/
         
           $success = $conexion->query($sql);
           !($success) ? $transaccion_exitosa = false : $transaccion_exitosa = true;
           
           if($transaccion_exitosa){
                #actualizar bandera en tabla de companias:
                if($_POST['iConsecutivo'] == ''){
                    $id_file = $conexion->insert_id;
                    $sql     = "UPDATE ct_companias SET eEstatusCertificadoUpload = '1'  WHERE iConsecutivo = '".$iConsecutivoCompania."'";
                    $conexion->query($sql);
                } 
                $conexion->commit();
                $conexion->close();
           }
           else{
                $conexion->rollback();
                $conexion->close();
                $mensaje = "A general system error ocurred : internal error";
                $error = "1";
           }
       
       
   }
   else{$error = '1'; $mensaje = "A general system error ocurred : internal error";} 
   
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
      $error   = '0';
      $msj     = "";
      $fields  = "";
      $clave   = trim($_POST['clave']);
      $domroot = $_POST['domroot'];
      isset($_POST['parametro']) == "" ? $parametro = "#" : $parametro = $_POST['parametro']; 
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);
      
      $valid_user = valid_user($_SESSION['usuario_actual']);

      if(!($valid_user)){
          $error = '1';
          $msj   = "This user does not have the privileges to modify or add data to the system.";
      }
      else{
          $sql    = "SELECT iConsecutivo, sNombreArchivo AS txtsCertificatePDF, sNombreArchivoAdd, DATE_FORMAT(dFechaVencimiento,'%m/%d/%Y') AS dFechaVencimiento, eOrigenCertificado, sDescripcionOperaciones  ".
                    "FROM cb_certificate_file WHERE iConsecutivoCompania = '".$clave."'";
          $result = $conexion->query($sql); 
          $rows   = $result->num_rows; 
          
          if ($rows > 0) {     
            $data    = $result->fetch_assoc();
            $llaves  = array_keys($data);
            $datos   = $data;
            foreach($datos as $i => $b){
                
                if(($data['eOrigenCertificado'] == "LAYOUT" && $i != "sDescripcionOperaciones") || ($data['eOrigenCertificado'] == "DATABASE")){
                    if($i == "sDescripcionOperaciones"){
                        $datos[$i] = preg_replace("/\r\n|\r|\n/",'\n',$datos[$i]);   
                    }
                    
                    if($parametro != "#" || $parametro == ""){$campo = "[$parametro=$i]";}else{$campo = $parametro.$i;}
                    $fields .= "\$('#$domroot ".$campo."').val('".$datos[$i]."');";     
                }
                
            }  
          }
      }

      /*if($items_files["sNombreArchivo"] != ''){
            $fields .= "\$('#$domroot :input[id=txtsCertificatePDF]').val('".$items_files['sNombreArchivo']."');";
            $fields .= "\$('#$domroot :input[id=iConsecutivo]').val('".$items_files['iConsecutivo']."');";  
      }
      if($items_files["sNombreArchivoAdd"] != ''){
            $fields .= "\$('#$domroot :input[id=txtsAdditionalPDF]').val('".$items_files['sNombreArchivoAdd']."');";  
      }
      if($items_files["dFechaVencimiento"] != ''){
            $fields .= "\$('#$domroot :input[id=dFechaVencimiento]').val('".$items_files['dFechaVencimiento']."');";  
      }*/
      $conexion->rollback();
      $conexion->close(); 
      $response = array("msj"=>"$msj","error"=>"$error","fields"=>"$fields");   
      echo json_encode($response);
                                                                                                                         
  }
  function get_policies(){
      
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);
      $company = trim($_POST['iConsecutivoCompania']);
      $error   = '0';

      $sql    = "SELECT A.iConsecutivo, sNumeroPoliza, C.sName AS BrokerName, sDescripcion, D.iConsecutivo AS TipoPoliza, D.sDescripcion AS sTipoPoliza, DATE_FORMAT(A.dFechaInicio, '%m/%d/%Y') AS dFechaInicio, DATE_FORMAT(A.dFechaCaducidad, '%m/%d/%Y') AS dFechaCaducidad ".
                "FROM ct_polizas          AS A ".
                "LEFT JOIN ct_brokers     AS C ON A.iConsecutivoBrokers = C.iConsecutivo ".
                "LEFT JOIN ct_tipo_poliza AS D ON A.iTipoPoliza         = D.iConsecutivo ".
                "WHERE iConsecutivoCompania = '".$company."' ".
                "AND  A.iDeleted = '0' AND dFechaCaducidad >= CURDATE() ".
                "ORDER BY A.iConsecutivo ASC";  
      $result = $conexion->query($sql);
      $rows   = $result->num_rows;
      
      if($rows > 0) {   
            while ($items = $result->fetch_assoc()) { 
            
               $htmlTabla .= "<tr>".
                             "<td style=\"border: 1px solid #dedede;\">".$items['sTipoPoliza']."</td>".
                             "<td style=\"border: 1px solid #dedede;\">".$items['sNumeroPoliza']."</td>". 
                             "<td style=\"border: 1px solid #dedede;\">".$items['dFechaInicio']."</td>".
                             "<td style=\"border: 1px solid #dedede;\">".$items['dFechaCaducidad']."</td>".
                             "</tr>";
      
                    
            }                                                                                                                                                                       
        }else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";}
        $conexion->rollback();
        $conexion->close();
        $response = array("mensaje"=>"$mensaje","error"=>"$error","policies_information"=>"$htmlTabla");   
        echo json_encode($response);
  }  
?>
