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
     $filtroQuery = " WHERE A.iDeleted='0' ";
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
     $ordenQuery = " ORDER BY A.iOnRedList DESC, ".$_POST["ordenInformacion"]." ".$_POST["sortInformacion"];

    //contando registros // 
    $query_rows = "SELECT COUNT(A.iConsecutivo) AS total FROM ct_companias AS A ".$filtroQuery;
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
      $sql    = "SELECT A.iConsecutivo, A.sNombreCompania, A.iOnRedList, (SELECT COUNT(B.iConsecutivo) FROM cb_endoso AS B WHERE B.iConsecutivoCompania = A.iConsecutivo) AS total_endosos ".
                "FROM ct_companias AS A ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
      $result = $conexion->query($sql);
      $rows   = $result->num_rows; 
         
        if ($rows > 0) {    
            while ($items = $result->fetch_assoc()) { 
              
                 $btn_confirm = "";
                 $descripcion = ""; 
                 
                 
                  //Redlist:
                 $items['iOnRedList'] == '1' ? $redlist_icon = "<i class=\"fa fa-star\" style=\"color:#e8051b;margin-right:4px;\"></i>" : $redlist_icon = ""; 
                 $htmlTabla .= "<tr>
                                    <td>".$items['iConsecutivo']."</td>".
                                   "<td>".$redlist_icon.$items['sNombreCompania']."</td>".
                                   "<td>".$items['total_endosos']."</td>". 
                                   "<td>".
                                   "<div class=\"btn_endorsement_list btn-icon edit btn-left\" title=\"View Endorsements\"><i class=\"fa fa-pencil-square-o\"></i> <span></span></div>".
                                        //"<div class=\"btn_open_files btn-icon edit btn-left\" title=\"View Files of Endorsement - ".$items['iConsecutivo']."\"><i class=\"fa fa-file-text\"></i> <span></span></div> ".
                                   "</td>".
                                   "</tr>";
                   
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
  function get_endorsement_files(){
      
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $registros_por_pagina = $_POST["registros_por_pagina"];
      $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
      $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
      $iConsecutivoEndoso = trim($_POST['iConsecutivo']);
      $error = '0';
      $mensaje = '';
      
      #TRAER CONSECUTIVO DE LA UNIDAD O DEL DRIVER:
      $query = "SELECT iConsecutivoUnidad, iConsecutivoOperador FROM  cb_endoso WHERE iConsecutivo = '$iConsecutivoEndoso'";
      $result_query = $conexion->query($query);
      $items_desc = $result_query->fetch_assoc(); 
      
      
      if($_POST['tipo'] == '1' && $items_desc['iConsecutivoUnidad'] != ''){ 
          //UNITS
          $tabla_consulta = 'cb_unidad_files';
          $iConsecutivoDesc = " iConsecutivoUnidad = '".trim($items_desc['iConsecutivoUnidad'])."'";
          $type = "unit";
          
      }else if($_POST['tipo'] == '2' && $items_desc['iConsecutivoOperador'] != ''){
          //DRIVER
          $tabla_consulta = 'cb_operador_files';
          $iConsecutivoDesc = " iConsecutivoOperador = '".trim($items_desc['iConsecutivoOperador'])."'";
          $type = "driver";
      }else{
          $error = '1';
          $mensaje = "Error: data files is not found";
      }
      
      if($error == '0'){
             
             //Filtros de informacion //
             $filtroQuery = " WHERE $iConsecutivoDesc ";
             $array_filtros = explode(",",$_POST["filtroInformacion"]);
             foreach($array_filtros as $key => $valor){
                if($array_filtros[$key] != ""){
                    $campo_valor = explode("|",$array_filtros[$key]); 
                    $filtroQuery.= " AND  ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%' ";
                    
                }
             }
             // ordenamiento//
             $ordenQuery = " ORDER BY ".$_POST["ordenInformacion"]." ".$_POST["sortInformacion"];

            //contando registros // 
            $query_rows = "SELECT COUNT(iConsecutivo) AS total FROM $tabla_consulta ".$filtroQuery;
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
              $sql = "SELECT iConsecutivo, sNombreArchivo, sTipoArchivo, iTamanioArchivo, eArchivo, dFechaIngreso, dFechaActualizacion ".
                     "FROM $tabla_consulta ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
              $result = $conexion->query($sql);
              $rows = $result->num_rows; 
                 
                if ($rows > 0) {    
                    while ($items = $result->fetch_assoc()) { 
                       if($items["iConsecutivo"] != ""){
                             if($items['sTipoArchivo'] == 'application/pdf'){$btnIcon = "fa-file-pdf-o";}else if($items['sTipoArchivo'] == 'image/jpeg'){$btnIcon = "fa-file-image-o";} 
                             $htmlTabla .= "<tr>".
                                           "<td id=\"".$items['iConsecutivo']."\">".$items['sNombreArchivo']."</td>".
                                           "<td>".$items['eArchivo']."</td>".                                                                                                                                                                                                                        
                                           "<td>".
                                                "<div onclick=\"window.open('open_pdf.php?idfile=".$items['iConsecutivo']."&type=$type');\" class=\"btn-icon pdf btn-left\" title=\"View PDF File\"><i class=\"fa $btnIcon\"></i><span></span></div>".
                                                "<div class=\"btn_delete_file btn-icon trash btn-left\" title=\"Delete file of Endorsement - ".$items['iConsecutivo']."\"><i class=\"fa fa-trash\"></i> <span></span></div>".
                                           "</td>". 
                                           "</tr>";
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
      }  
        
     
      $response = array("total"=>"$paginas_total","pagina"=>"$pagina_actual","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
      echo json_encode($response);   
      
  } 
  function save_file(){
      $error = "0";
      $mensaje = "";
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $categoria = $_POST['Categoria'];
      $oFichero = fopen($_FILES['userfile']["tmp_name"], 'r'); 
      $sContenido = fread($oFichero, filesize($_FILES['userfile']["tmp_name"]));  
      $sContenido =  $conexion->real_escape_string($sContenido);
      $eArchivo = $_POST['eArchivo'];
      
      //Revisamos el tamaño del archivo:
      if($_FILES['userfile']["size"] <= 1000000){
      
          //Saber si es edicion o insert:
          if($_POST['edit_mode'] != 'true'){
             #TRAER CONSECUTIVO DE LA UNIDAD O DEL DRIVER:
             $query = "SELECT iConsecutivoUnidad, iConsecutivoOperador FROM  cb_endoso WHERE iConsecutivo = '".$_POST['iConsecutivoEndoso']."'";
             $result_query = $conexion->query($query);
             $items_desc = $result_query->fetch_assoc(); 
             
             if($_POST['Categoria'] == '2' && $items_desc['iConsecutivoOperador'] != '')$iConsecutivoDesc = trim($items_desc['iConsecutivoOperador']); 
             if($_POST['Categoria'] == '1' && $items_desc['iConsecutivoUnidad'] != '')$iConsecutivoDesc = trim($items_desc['iConsecutivoUnidad']);
          }
   
          if($_POST['Categoria'] == '2'){$ct_tabla = "cb_operador_files";$idfield='iConsecutivoOperador';}else if($_POST['Categoria'] == '1'){$ct_tabla = "cb_unidad_files";$idfield='iConsecutivoUnidad';}
          
          $FileExt = explode('.',$_FILES['userfile']["name"]); 
          $countArray = count($FileExt);
          if($countArray == 2){
             if($_POST['eArchivo'] == 'OTHERS' && $_POST['sNombreArchivo'] != ''){
                $_POST['sNombreArchivo'] = str_replace(' ','_',$_POST['sNombreArchivo']); 
                $name_file = strtolower($_POST['sNombreArchivo']).'.'.$FileExt[1]; 
             }else{
                $name_file = strtolower($_POST['eArchivo']).'.'.$FileExt[1];     
             } 
              
          }else{
              $mensaje = "Error: Please verify that the file name does not contain any special characters..";
              $error = "1";
          }
          
          if($error == '0'){      
              if($_POST['edit_mode'] != 'true'){
                 
                 if($_POST['eArchivo'] != 'OTHERS'){
                    $query_eArchivo = "SELECT COUNT(iConsecutivo) AS total ".
                                      "FROM $ct_tabla ".
                                      "WHERE eArchivo = '".$_POST['eArchivo']."' AND $idfield = '$iConsecutivoDesc'";
                    $result_query = $conexion->query($query_eArchivo);
                    $items_eArchivo = $result_query->fetch_assoc();
                    if($items_eArchivo["total"] != '0'){
                       $sql = "UPDATE $ct_tabla SET sNombreArchivo ='".$name_file."', sTipoArchivo ='".$_FILES['userfile']["type"]."', iTamanioArchivo ='".$_FILES['userfile']["size"]."', hContenidoDocumentoDigitalizado='$sContenido', eArchivo='$eArchivo', ".
                              "dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."', sUsuarioActualizacion='".$_SESSION['usuario_actual']."'".
                              "WHERE $idfield ='$iConsecutivoDesc' AND eArchivo = '".$_POST['eArchivo']."'"; 
                    }else{
                       $sql = "INSERT INTO $ct_tabla (sNombreArchivo, sTipoArchivo, iTamanioArchivo, hContenidoDocumentoDigitalizado, eArchivo, dFechaIngreso, sIP, sUsuarioIngreso,$idfield) ".
                              "VALUES('".$name_file."','".$_FILES['userfile']["type"]."','".$_FILES['userfile']["size"]."','$sContenido','$eArchivo','".date("Y-m-d H:i:s")."', '".$_SERVER['REMOTE_ADDR']."', '".$_SESSION['usuario_actual']."','$iConsecutivoDesc')";  
                    }
                 }      
                  
              }else{
                 $sql = "UPDATE $ct_tabla SET sNombreArchivo ='".$name_file."', sTipoArchivo ='".$_FILES['userfile']["type"]."', iTamanioArchivo ='".$_FILES['userfile']["size"]."', hContenidoDocumentoDigitalizado='$sContenido', eArchivo='$eArchivo', ".
                        "dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."', sUsuarioActualizacion='".$_SESSION['usuario_actual']."'".
                        "WHERE iConsecutivo ='".$_POST['iConsecutivo']."'";  
              }

              if($conexion->query($sql)){
                     
                    $conexion->commit();
                    $conexion->close();
                    $mensaje = "The file was uploaded successfully.";  
              }else{
                    $conexion->rollback();
                    $conexion->close();
                    $mensaje = "A general system error ocurred : internal error";
                    $error = "1";
              }     
              
          }
      
      }else{             
         $mensaje = "Error: The file you are trying to upload exceeds the maximum size (1MB) allowed by the system, please check it and try again.";
         $error = "1"; 
      }
      
      $response = array("mensaje"=>"$mensaje","error"=>"$error",); 
      echo json_encode($response);             
      
  }
  function delete_file(){
      
         
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $categoria = $_POST['Categoria']; 
      $iConsecutivo = $_POST['iConsecutivoFile'];
      $error = '0';
      $msj = "";
      
      #REVISAMOS CATEGORIA:
      if($categoria == '2'){$ct_tabla = "cb_operador_files";}
      else if($categoria == '1'){$ct_tabla = "cb_unidad_files";}
      
      if($ct_tabla != ''){
          #borar archivos de drivers sin un id de driver asignado:
          $query_licen = "DELETE FROM $ct_tabla WHERE iConsecutivo = '$iConsecutivo'";
          $conexion->query($query_licen);
          
      }else{
         $error = '1'; 
      }
       
      
      
      if($error == '0'){
        $conexion->commit();
        $conexion->close();
        $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>
                The files has been deleted succesfully!</p>';
      }else{
        $conexion->rollback();
        $conexion->close();
        $msj = "A general system error ocurred : internal error";
      }
        
      $response = array("msj"=>"$msj","error"=>"$error");   
      echo json_encode($response);
  }

  
?>
