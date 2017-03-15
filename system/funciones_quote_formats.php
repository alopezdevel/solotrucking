<?php
  session_start();
  // Generic functions lib 
  include("functiones_genericas.php"); 
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId 
    
  //Catalogo de compañias:
  function get_formats(){
     
    include("cn_usuarios.php");
    $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
    $transaccion_exitosa = true;
    
    $registros_por_pagina = $_POST["registros_por_pagina"];
    $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
    $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
        
    //Filtros de informacion //
    $filtroQuery = " WHERE iConsecutivo IS NOT NULL";
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
    $query_rows = "SELECT COUNT(iConsecutivo) AS total FROM ct_formatos_PDF ".$filtroQuery;
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
        $sql = "SELECT iConsecutivo, sNombreJPGSistema, sNombreArchivoEmpresa, sTituloArchivoEmpresa, sComentarios ,sRuta ,sPDFRelacionFormatoSistema,sRutaArchivoOriginal,sNombreFormularioCaptura,sRutaArchivoWord ".
               "FROM  ct_formatos_PDF ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
        $result = $conexion->query($sql);
        $rows = $result->num_rows;    
        if ($rows > 0) {    
            while ($items = $result->fetch_assoc()) { 
               if($items["iConsecutivo"] != ""){
                     $items['sNombreFormularioCaptura'] != "" ? $funcion = " <div class=\"btn_new send-email btn-icon btn-left\" title=\"Generate this format\" onclick=\"fn_formats.".$items['sNombreFormularioCaptura'].".init();\" ><i class=\"fa fa-plus\"></i></div>" : $funcion = "";
                     $items['sRutaArchivoWord'] != "" ? $btn_word = "<div class=\"btn_new send-email btn-icon btn-left\" title=\"Download WordFile\"><a href=\"".$items['sRutaArchivoWord']."\"><i class=\"fa fa-file-word-o\"></i></a></div>" : $btn_word = "";
                     $htmlTabla .= "<tr>
                                        <td>".$items['iConsecutivo']."</td>".
                                       "<td>".$items['sTituloArchivoEmpresa']."</td>".
                                       "<td>".$items['sNombreArchivoEmpresa']."</td>".                                                                                                                                                                                                                         
                                       "<td>
                                            $funcion
                                            <div class=\"btn_new send-email btn-icon btn-left\" title=\"Open original format in a new window\"><a href=\"".$items['sRutaArchivoOriginal']."\" target=\"_blank\"><i class=\"fa fa-external-link\"></i></a></div>  
                                            $btn_word
                                       </td></tr>";
                                       //<div class=\"btn_edit btn-icon edit btn-left\" title=\"Edit Format Name\"><i class=\"fa fa-pencil-square-o\"></i><span></span></div>
                                       //<div style=\"display:none;\" class=\"btn_delete btn-icon trash btn-left\" title=\"Delete Company\"><i class=\"fa fa-trash\"></i> <span></span></div>
                 }else{                                                                                                                                                                                                        
                    
                     $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>"   ;
                 }    
            }
        
            
            $conexion->rollback();
            $conexion->close();                                                                                                                                                                       
        } else { 
            
            $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";    
            
        } 
    }
     $response = array("total"=>"$paginas_total","pagina"=>"$pagina_actual","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
     echo json_encode($response); 
  }
  function get_companies_info(){
    $error = '0';
    $msj = "";
    $iConsecutivo = trim($_POST['iConsecutivo']);
    $domroot = trim($_POST['domroot']);

    include("cn_usuarios.php");
    //$conexion->begin_transaction();
    $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
    $transaccion_exitosa = true;
    $sql = "SELECT * FROM ct_companias WHERE iConsecutivo = ".$iConsecutivo; 
    $result = $conexion->query($sql);
    $items = $result->num_rows;   
    if ($items > 0) {     
        $data = $result->fetch_assoc();
        $llaves  = array_keys($data);
        $datos   = $data;
        foreach($datos as $i => $b){
             if($i == 'iStartingYear' || $i == 'iStartingOperationYear'){
                $StartingYear = $datos[$i]; 
                $TodayYear = getdate(); 
                $StartingYear != '' ? $TotalYears = ($TodayYear['year']-$StartingYear) : $TotalYears = "";
                if($i == 'iStartingYear'){$campo = 'sYearsExperiencia';}
                else if($i == 'iStartingOperationYear'){$campo = 'sYearsOperation';}
                $fields .= "\$('#$domroot :input[id=$campo]').val('".$TotalYears."');";
             }else{
                $fields .= "\$('#$domroot :input[id=".$i."]').val('".$datos[$i]."');";  
             }
        }  
    }else{
        $error = '1';
        $msj = "Error: The data has been not found, please try again.";
    }
    $conexion->rollback();
    $conexion->close(); 
    $response = array("msj"=>"$msj","error"=>"$error","fields"=>"$fields");   
    echo json_encode($response);
  } 
  //drivers lista con checkbox:
  function get_drivers_list(){
      
      $iConsecutivoCompania = $_POST['iConsecutivoCompania'];
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $error = "0";
      
      $_POST['token'] != "" ? $filtrotoken = "AND iConsecutivo NOT IN (SELECT iConsecutivoOperador FROM cb_quote_format_operadores WHERE iConsecutivo = '".$_POST['token']."')" : $filtrotoken = "";
      
      $sql = "SELECT iConsecutivo, sNombre, iExperienciaYear, iNumLicencia,sAccidentesNum, DATE_FORMAT(dFechaNacimiento,  '%m/%d/%Y') AS dFechaNacimiento ".
             "FROM ct_operadores ".
             "WHERE iConsecutivoCompania ='$iConsecutivoCompania' $filtrotoken ORDER BY sNombre ASC"; 
        $result = $conexion->query($sql);
        $rows = $result->num_rows;   
        if ($rows > 0) { 
            $paginas_total = $rows;   
            while ($items = $result->fetch_assoc()){        
                     $htmlTabla .= "<tr>".
                                   "<td><input class=\"driverlist_id\" name=\"driverlist_id\" type=\"checkbox\" value=\"".$items['iConsecutivo']."\"></td>".
                                   "<td>".$items['sNombre']."</td>".
                                   "<td>".$items['iExperienciaYear']."</td>".
                                   "<td>".$items['dFechaNacimiento']."</td>".                                                                                                                                                                                                                   
                                   "</tr>"; 
            }
            $conexion->rollback();
            $conexion->close();                                                                                                                                                                       
        }else{
            
            $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";
            $mensaje = "There are no drivers for this company, please register them."; 
            $paginas_total = 0;   
        }  
    
     $htmlTabla = utf8_decode($htmlTabla);
     $response = array("total"=>"$paginas_total","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error");   
     echo json_encode($response);
  }
  function save_list(){
      $list = trim($_POST['list']);
      $list = explode('|',$list);
      $_POST['token'] != "" ? $token = trim($_POST['token']) : $token = ""; 
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $error = "0";
      $mensaje = "";
      //create a token id:
      if($token == ""){
         $query = "SELECT iConsecutivo FROM cb_quote_format_operadores ORDER BY iConsecutivo DESC LIMIT 1";
         $result = $conexion->query($query);
         $items = $result->fetch_assoc();
         $items["iConsecutivo"] != '' ? $token = $items["iConsecutivo"] + 1 : $token = 1; 
      } 
      for($i = 0; $i < count($list); $i++){
          
          $query = "INSERT INTO cb_quote_format_operadores (iConsecutivo,iConsecutivoOperador) VALUES('$token','".$list[$i]."')";
          $conexion->query($query);
          if($conexion->affected_rows < 1){ 
            $transaccion_exitosa = false;
            $mensaje = "The data of driver was not saved properly, please try again.";               
          }
      }
      
      if($transaccion_exitosa){
            $conexion->commit();
            $conexion->close();
            $mensaje = "The data has been uploaded successfully.";
      }else{
            $conexion->rollback();
            $conexion->close();
            $error = "1";
      }
      
      $response = array("mensaje"=>"$mensaje","error"=>"$error", "token" => "$token"); 
      echo json_encode($response);
      
      
  }
  #CARGAR TABLA NO LA DE DIALOGO... LA OTRA:
  function get_list_drivers(){
      
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $error = "0";
      $token = trim($_POST['token']);
      $form  = trim($_POST['form']);
      $type  = trim($_POST['type']);

      $sql = "SELECT B.iConsecutivo, sNombre, iExperienciaYear, iNumLicencia,sAccidentesNum,DATE_FORMAT(dFechaNacimiento,  '%m/%d/%Y') AS dFechaNacimiento ".
             "FROM cb_quote_format_operadores A  ".
             "INNER JOIN ct_operadores B ON A.iConsecutivoOperador = B.iConsecutivo  ".
             "WHERE A.iConsecutivo = '$token' ORDER BY sNombre ASC"; 
        $result = $conexion->query($sql);
        $rows = $result->num_rows;   
        if ($rows > 0) { 
            $paginas_total = $rows;   
            while ($items = $result->fetch_assoc()){        
                     $htmlTabla .= "<tr>".
                                   "<td id=\"".$items['iConsecutivo']."\">".$items['sNombre']."</td>".
                                   "<td>".$items['dFechaNacimiento']."</td>".
                                   "<td>".$items['iNumLicencia']."</td>".
                                   "<td>".$items['iExperienciaYear']."</td>".
                                   "<td><div class=\"btn_delete btn-icon trash btn-left\" title=\"Delete from list\" onclick=\"fn_formats.delete_tmp_du('".$items['iConsecutivo']."','$token','$form','$type');\"><i class=\"fa fa-minus-circle\"></i><span></span></div></td>".                                                                                                                                                                                                                   
                                   "</tr>"; 
            }
            $conexion->rollback();
            $conexion->close();                                                                                                                                                                       
        }else{
            
            $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";
            $mensaje = "There are no drivers for this company, please register them."; 
            $paginas_total = 0;   
        }  
    
     $htmlTabla = utf8_decode($htmlTabla);
     $response = array("total"=>"$paginas_total","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error");   
     echo json_encode($response);
  }
  function save_driver(){
      include("funciones_genericas.php"); 
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      #VARIABLES:
      $error = '0'; 
      $valores = array();
      $campos  = array(); 
      $msj = ""; 
      
      $_POST['sNombre']      = strtoupper($_POST['sNombre']);
      $_POST['iNumLicencia'] = strtoupper($_POST['iNumLicencia']); 
      $_POST['dFechaNacimiento'] = format_date($_POST['dFechaNacimiento']);
      $_POST['token'] != "" ? $token = trim($_POST['token']) : $token = "";
      
      //create a token id:
      if($token == ""){
         $query = "SELECT iConsecutivo FROM cb_quote_format_operadores ORDER BY iConsecutivo DESC LIMIT 1";
         $result = $conexion->query($query);
         $items = $result->fetch_assoc();
         $items["iConsecutivo"] != '' ? $token = $items["iConsecutivo"] + 1 : $token = 1; 
      }
      
      $query = "SELECT COUNT(iConsecutivo) AS total ".
               "FROM   ct_operadores ".
               "WHERE  iNumLicencia ='".$_POST['iNumLicencia']."' AND iConsecutivoCompania = '".$_POST['iConsecutivoCompania']."'";
      $result = $conexion->query($query);
      $valida = $result->fetch_assoc();
      
      if($valida['total'] != '0'){
          if($_POST["edit_mode"] != 'true'){
              $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Error: The Driver that you trying to add already exists in this list. Please you verify by License Number .</p>';
              $error = '1';
          }else{
             foreach($_POST as $campo => $valor){
                if($campo != "accion" and $campo != "edit_mode"  and $campo != "iConsecutivo" and $campo != "iNumLicencia"){ //Estos campos no se insertan a la tabla
                    if($valor){array_push($valores,"$campo='".trim($valor)."'");}
                }
             }   
          }
      }else if($_POST["edit_mode"] != 'true'){
         foreach($_POST as $campo => $valor){
            if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivo"){ //Estos campos no se insertan a la tabla
                if($valor != ''){array_push($campos ,$campo); array_push($valores, trim($valor)); }
            }
         }  
      }
      ////////
      if($error == '0'){
          if($_POST["edit_mode"] == 'true'){
            array_push($valores ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
            array_push($valores ,"sIP='".$_SERVER['REMOTE_ADDR']."'");
            array_push($valores ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'"); 
            $sql = "UPDATE ct_operadores SET ".implode(",",$valores)." WHERE iConsecutivo ='".$_POST['iConsecutivo']."' AND iConsecutivoCompania ='".$_POST['iConsecutivoCompania']."'";
            $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Data have been updated successfully.</p>'; 
          }else{
            array_push($campos ,"dFechaIngreso");
            array_push($valores ,date("Y-m-d H:i:s"));
            array_push($campos ,"sIP");
            array_push($valores ,$_SERVER['REMOTE_ADDR']);
            array_push($campos ,"sUsuarioIngreso");
            array_push($valores ,$_SESSION['usuario_actual']);
            $sql = "INSERT INTO ct_operadores (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')";
            $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Data have been added successfully.</p>';
          }
          $conexion->query($sql);
          $conexion->affected_rows < 1 ? $transaccion_exitosa = false : $transaccion_exitosa = true;
          if($transaccion_exitosa){
                //Revisamos si es un insert , actualizamos la tabla: 
                if($_POST['edit_mode'] == 'false'){
                    $id = $conexion->insert_id;
                    $query = "INSERT INTO cb_quote_format_operadores (iConsecutivo,iConsecutivoOperador) VALUES('$token','$id')";
                    $conexion->query($query);
                    if($conexion->affected_rows < 1){ $error = "1"; $mensaje = "The data of driver was not saved properly, please try again.";}
                }
          }else{$msj = "A general system error ocurred : internal error";$error = "1";} 
          
          if($error == '0'){$conexion->commit();$conexion->close();}else{$conexion->rollback();$conexion->close();}
      }
      $response = array("error"=>"$error","msj"=>"$msj","token"=>"$token");
      echo json_encode($response);
      
      
  }
  function save_company(){
      include("funciones_genericas.php");
      $error = '0'; 
      $valores = array();
      $campos  = array(); 
      $msj = "";
      $_POST['sNombreCompania'] = strtoupper($_POST['sNombreCompania']);  
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      $query = "SELECT COUNT(iConsecutivo) AS total FROM ct_companias WHERE sUsdot ='".$_POST['sUsdot']."'";
      $result = $conexion->query($query);
      $valida = $result->fetch_assoc();
      
      if($valida['total'] != '0'){
          if($_POST["edit_mode"] != 'true'){
              $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>
                      Error: The company trying to add already exists. Please verify the data.</p>';
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
           if($campo != "accion" and $campo != "edit_mode"){ //Estos campos no se insertan a la tabla
                array_push($campos ,$campo); 
                array_push($valores, trim($valor));
           }
         }  
      }
      
      if($error == '0'){
          if($_POST["edit_mode"] == 'true'){
            array_push($valores ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
            array_push($valores ,"sIP='".$_SERVER['REMOTE_ADDR']."'");
            array_push($valores ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'");
            $sql = "UPDATE ct_companias SET ".implode(",",$valores)." WHERE iConsecutivo = '".$_POST['iConsecutivo']."'";
            $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The data has been updated successfully.</p>'; 
          }else{
            array_push($campos ,"dFechaIngreso");
            array_push($valores ,date("Y-m-d H:i:s"));
            array_push($campos ,"sIP");
            array_push($valores ,$_SERVER['REMOTE_ADDR']);
            array_push($campos ,"sUsuarioIngreso");
            array_push($valores ,$_SESSION['usuario_actual']);
            $sql = "INSERT INTO ct_companias (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')";
            $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The data has been saved successfully!</p>';
          }
          $conexion->query($sql);
          $conexion->affected_rows < 1 ? $transaccion_exitosa = false : $transaccion_exitosa = true;
          if($transaccion_exitosa){
                    $conexion->commit();
                    $conexion->close();
          }else{
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
  //units y trailers:
  function get_unit_trailer_list(){
      
      $iConsecutivoCompania = $_POST['iConsecutivoCompania'];
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $error = "0";
      $tipo  = trim($_POST['tipo']); 
      $tipo != 'both' ? $filtrotipo = "AND sTipo = '$tipo'" : $filtrotipo = "";
      
      $_POST['token'] != "" ? $filtrotoken = "AND A.iConsecutivo NOT IN (SELECT iConsecutivoOperador FROM cb_quote_format_operadores WHERE iConsecutivo = '".$_POST['token']."')" : $filtrotoken = "";
      
      $sql = "SELECT A.iConsecutivo,iYear,iModelo,sVIN,sPeso,sTipo,iTotalPremiumPD,sDescripcion AS sModelo ".
             "FROM   ct_unidades A ".
             "LEFT JOIN ct_unidad_modelo B ON A.iModelo = B.iConsecutivo ".
             "WHERE  iConsecutivoCompania ='$iConsecutivoCompania' $filtrotipo $filtrotoken ORDER BY sDescripcion ASC"; 
      $result = $conexion->query($sql);
      $rows = $result->num_rows;   
        if ($rows > 0) { 
            $paginas_total = $rows;   
            while ($items = $result->fetch_assoc()){        
                     $htmlTabla .= "<tr>".
                                   "<td><input class=\"utlist_id\" name=\"utlist_id\" type=\"checkbox\" value=\"".$items['iConsecutivo']."\"></td>".
                                   "<td>".$items['iYear']."</td>".
                                   "<td>".$items['sModelo']."</td>".
                                   "<td>".$items['sVIN']."</td>". 
                                   "<td>".$items['sTipo']."</td>".  
                                   "<td>".$items['iTotalPremiumPD']."</td>".                                                                                                                                                                                                                  
                                   "</tr>"; 
            }
            $conexion->rollback();
            $conexion->close();                                                                                                                                                                       
        }else{
            
            $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";
            $mensaje = "There are no ".$tipo."s for this company, please register them."; 
            $paginas_total = 0;   
        }  
    
     $htmlTabla = utf8_decode($htmlTabla);
     $response = array("total"=>"$paginas_total","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error");   
     echo json_encode($response);
       
  } 
  function get_list_ut(){
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $error = "0";
      $token = trim($_POST['token']);
      $form  = trim($_POST['form']);
      $type  = trim($_POST['type']);

      $sql = "SELECT B.iConsecutivo,iYear,iModelo,sVIN,sPeso,sTipo,A.iTotalPremiumPD,sDescripcion AS sModelo,iValue  ".
             "FROM cb_quote_format_operadores A  ".
             "INNER JOIN ct_unidades B ON A.iConsecutivoOperador = B.iConsecutivo  ".
             "LEFT JOIN ct_unidad_modelo C ON B.iModelo = C.iConsecutivo ".
             "WHERE A.iConsecutivo = '$token' ORDER BY sDescripcion ASC";
      $result = $conexion->query($sql);
      $rows = $result->num_rows;   
        if ($rows > 0) { 
            $paginas_total = $rows;   
            while ($items = $result->fetch_assoc()){
                     $items["iValue"] != "" ? $iValue  = '$ '.number_format($items["iValue"]) : $iValue = ""; 
                     $items["iTotalPremiumPD"] != "" ? $iTotalPremiumPD  = '$ '.number_format($items["iTotalPremiumPD"]) : $iTotalPremiumPD = "";       
                     $htmlTabla .= "<tr>".
                                   "<td id=\"".$items['iConsecutivo']."\">".$items['sVIN']."</td>".
                                   "<td>".$items['iYear']."</td>".
                                   "<td>".$items['sModelo']."</td>".
                                   "<td>".$items['sTipo']."</td>". 
                                   "<td>".$items['sPeso']."</td>". 
                                   "<td>".$iValue."</td>". 
                                   "<td>".$iTotalPremiumPD."</td>". 
                                   "<td><div class=\"btn_delete btn-icon trash btn-left\" title=\"Delete from list\" onclick=\"fn_formats.delete_tmp_du('".$items['iConsecutivo']."','$token','$form','$type');\"><i class=\"fa fa-minus-circle\"></i><span></span></div></td>".                                                                                                                                                                                                                 
                                   "</tr>";
            }
            $conexion->rollback();
            $conexion->close();                                                                                                                                                                       
        }else{
            
            $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";
            $mensaje = "There are no unit/trailer for this company, please register them."; 
            $paginas_total = 0;   
        }  
    
     $htmlTabla = utf8_decode($htmlTabla);
     $response = array("total"=>"$paginas_total","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error");   
     echo json_encode($response); 
  }
  function save_ut(){
     include("funciones_genericas.php"); 
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      #VARIABLES:
      $error = '0'; 
      $valores = array();
      $campos  = array(); 
      $msj = ""; 
      
      $_POST['sVIN']  = strtoupper($_POST['sVIN']);
      $_POST['sTipo'] = strtoupper($_POST['sTipo']); 
      $_POST['token'] != "" ? $token = trim($_POST['token']) : $token = "";
      
      //create a token id:
      if($token == ""){
         $query = "SELECT iConsecutivo FROM cb_quote_format_operadores ORDER BY iConsecutivo DESC LIMIT 1";
         $result = $conexion->query($query);
         $items = $result->fetch_assoc();
         $items["iConsecutivo"] != '' ? $token = $items["iConsecutivo"] + 1 : $token = 1; 
      }
      
      $query = "SELECT COUNT(iConsecutivo) AS total ".
               "FROM   ct_unidades ".
               "WHERE  sVIN ='".$_POST['sVIN']."' AND iConsecutivoCompania = '".$_POST['iConsecutivoCompania']."'";
      $result = $conexion->query($query);
      $valida = $result->fetch_assoc();
      
      if($valida['total'] != '0'){
          if($_POST["edit_mode"] != 'true'){
              $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Error: The '.$_POST['sTipo'].' that you trying to add already exists in this list. Please you verify by VIN Number .</p>';
              $error = '1';
          }else{
             foreach($_POST as $campo => $valor){
                if($campo != "accion" and $campo != "edit_mode"  and $campo != "iConsecutivo" and $campo != "sVIN" and $campo != 'token'){ //Estos campos no se insertan a la tabla
                    if($valor){array_push($valores,"$campo='".trim($valor)."'");}
                }
             }   
          }
      }else if($_POST["edit_mode"] != 'true'){
         foreach($_POST as $campo => $valor){
            if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivo" and $campo != 'token'){ //Estos campos no se insertan a la tabla
                if($valor != ''){array_push($campos ,$campo); array_push($valores, trim($valor)); }
            }
         }  
      }
      ////////
      if($error == '0'){
          if($_POST["edit_mode"] == 'true'){
            array_push($valores ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
            array_push($valores ,"sIP='".$_SERVER['REMOTE_ADDR']."'");
            array_push($valores ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'"); 
            $sql = "UPDATE ct_unidades SET ".implode(",",$valores)." WHERE iConsecutivo ='".$_POST['iConsecutivo']."' AND iConsecutivoCompania ='".$_POST['iConsecutivoCompania']."'";
            $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Data have been updated successfully.</p>'; 
          }else{
            array_push($campos ,"dFechaIngreso");
            array_push($valores ,date("Y-m-d H:i:s"));
            array_push($campos ,"sIP");
            array_push($valores ,$_SERVER['REMOTE_ADDR']);
            array_push($campos ,"sUsuarioIngreso");
            array_push($valores ,$_SESSION['usuario_actual']);
            $sql = "INSERT INTO ct_unidades (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')";
            $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Data have been added successfully.</p>';
          }
          
          $conexion->query($sql);
          $conexion->affected_rows < 1 ? $transaccion_exitosa = false : $transaccion_exitosa = true;
          if($transaccion_exitosa){
                //Revisamos si es un insert , actualizamos la tabla: 
                if($_POST['edit_mode'] == 'false'){
                    $id = $conexion->insert_id;
                    $query = "INSERT INTO cb_quote_format_operadores (iConsecutivo,iConsecutivoOperador) VALUES('$token','$id')";
                    $conexion->query($query);
                    if($conexion->affected_rows < 1){ $error = "1"; $mensaje = "The data of driver was not saved properly, please try again.";}
                }
          }else{$msj = "A general system error ocurred : internal error";$error = "1";} 
          
          if($error == '0'){$conexion->commit();$conexion->close();}else{$conexion->rollback();$conexion->close();}
      }
      $response = array("error"=>"$error","msj"=>"$msj","token"=>"$token");
      echo json_encode($response); 
  } 
  //Commodities:
  function save_commodities(){
      include("funciones_genericas.php"); 
      //Conexion:
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      #VARIABLES:
      $error = '0'; 
      $valores = array();
      $campos  = array(); 
      $msj = ""; 
      $_POST['token'] != "" ? $token = trim($_POST['token']) : $token = "";
      //create a token id:
      if($token == ""){
         $query = "SELECT iConsecutivo FROM cb_quote_format_commodities ORDER BY iConsecutivo DESC LIMIT 1";
         $result = $conexion->query($query);
         $items = $result->fetch_assoc();
         $items["iConsecutivo"] != '' ? $token = $items["iConsecutivo"] + 1 : $token = 1; 
      }
      
      //validar si ya existe el commodity para esta lista:
      $query = "SELECT COUNT(iConsecutivo) AS total FROM cb_quote_format_commodities ".
               "WHERE  iConsecutivoCommodity ='".trim($_POST['iConsecutivoCommodity'])."' AND iConsecutivo = '$token'";
      $result = $conexion->query($query);
      $valida = $result->fetch_assoc();
      
      if($valida['total'] != '0'){
          $error = "1";
          $msj = "Error: The commodity that you tried to add has already exist.";
      }else{
          
          foreach($_POST as $campo => $valor){
            if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivoCompania" and $campo != "token"){ 
                if($valor != ''){array_push($campos,$campo); array_push($valores, trim($valor)); }
            }
          }
          array_push($campos,"iConsecutivo");
          array_push($valores,$token);
          $sql = "INSERT INTO cb_quote_format_commodities (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')";
          $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Data have been added successfully.</p>';  
          $conexion->query($sql);
          $conexion->affected_rows < 1 ? $transaccion_exitosa = false : $transaccion_exitosa = true;
          
          if($error == '0' && $transaccion_exitosa){$conexion->commit();$conexion->close();}else{$conexion->rollback();$conexion->close();}
          
      }
      $response = array("error"=>"$error","msj"=>"$msj","token"=>"$token");
      echo json_encode($response);
  }
  function get_list_commodities(){
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $error = "0";
      $token = trim($_POST['token']);

      $sql = "SELECT iConsecutivoCommodity,iPorcentajeHauled,iValorMinimo,iValorMaximo,sCommodities  ".
             "FROM cb_quote_format_commodities A  ".
             "LEFT JOIN ct_quotes_default_commodities B ON A.iConsecutivoCommodity = B.iConsecutivo ".
             "WHERE A.iConsecutivo = '$token' ORDER BY  A.iConsecutivo ASC";
        $result = $conexion->query($sql);
        $rows = $result->num_rows;   
        if ($rows > 0) { 
            $paginas_total = $rows;   
            while ($items = $result->fetch_assoc()){ 
                     $items["iValorMinimo"] != "" ? $iValorMinimo  = '$ '.number_format($items["iValorMinimo"]) : $iValorMinimo = ""; 
                     $items["iValorMaximo"] != "" ? $iValorMaximo  = '$ '.number_format($items["iValorMaximo"]) : $iValorMaximo = "";       
                     $htmlTabla .= "<tr>".
                                   "<td id=\"".$items['iConsecutivoCommodity']."\">".$items['sCommodities']."</td>".
                                   "<td>".$items['iPorcentajeHauled']." %</td>".
                                   "<td>".$iValorMinimo."</td>". 
                                   "<td>".$iValorMaximo."</td>".
                                   "<td><div class=\"btn_delete btn-icon trash btn-left\" title=\"Delete from list\" onclick=\"fn_formats.delete_commodities('".$items['iConsecutivoCommodity']."','$token','".$_POST['form']."');\"><i class=\"fa fa-minus-circle\"></i><span></span></div></td>".                                                                                                                                                                                                                   
                                   "</tr>";
            }
            $conexion->rollback();
            $conexion->close();                                                                                                                                                                       
        }else{
            
            $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";
            $mensaje = "There are no commodities for this company, please register them."; 
            $paginas_total = 0;   
        }  
    
     $htmlTabla = utf8_decode($htmlTabla);
     $response = array("total"=>"$paginas_total","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error");   
     echo json_encode($response); 
  }
  function delete_commodity(){
      include("funciones_genericas.php"); 
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      $token = trim($_POST['iConsecutivo']);
      $iCommodity = trim($_POST['iConsecutivoCommodity']);
      $error = "0";
      $msj = "";
      if($token != '' && $iCommodity !=''){
         $query = "DELETE FROM cb_quote_format_commodities WHERE iConsecutivo ='$token' AND iConsecutivoCommodity = '$iCommodity'"; 
         if($conexion->query($query)){
             $conexion->commit();
             $conexion->close();
         }else{
             $conexion->rollback();
             $conexion->close();
             $error = "1";
             $msg = "Error to delete the commodity from list, please try again.";
         }
      }
      
      $response = array("error"=>"$error","msj"=>"$msj");
      echo json_encode($response);
  }
  #DELETE TEMPORAL DRIVERS AND UNITS BY TOKEN ID:
  function delete_tmp_du(){
      
      include("funciones_genericas.php"); 
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      $token = trim($_POST['iConsecutivo']);
      $iConsecutivoDU = trim($_POST['iConsecutivodu']);
      $error = "0";
      $msj = "";
      if($token != '' && $iConsecutivoDU !=''){
         $query = "DELETE FROM cb_quote_format_operadores WHERE iConsecutivo ='$token' AND iConsecutivoOperador = '$iConsecutivoDU'"; 
         if($conexion->query($query)){
             $conexion->commit();
             $conexion->close();
         }else{
             $conexion->rollback();
             $conexion->close();
             $error = "1";
             $msg = "Error to delete the data from list, please try again.";
         }
      }
      
      $response = array("error"=>"$error","msj"=>"$msj");
      echo json_encode($response);
  }
  
  
?>
