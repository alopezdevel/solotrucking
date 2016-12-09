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
        $sql = "SELECT iConsecutivo, sNombreJPGSistema, sNombreArchivoEmpresa, sTituloArchivoEmpresa, sComentarios ,sRuta ,sPDFRelacionFormatoSistema,sRutaArchivoOriginal,sNombreFormularioCaptura ".
               "FROM  ct_formatos_PDF ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
        $result = $conexion->query($sql);
        $rows = $result->num_rows;    
        if ($rows > 0) {    
            while ($items = $result->fetch_assoc()) { 
               if($items["iConsecutivo"] != ""){
                     $items['sNombreFormularioCaptura'] != "" ? $funcion = "fn_formats.".$items['sNombreFormularioCaptura'].".init();" : $funcion = "";
                     $htmlTabla .= "<tr>
                                        <td>".$items['iConsecutivo']."</td>".
                                       "<td>".$items['sTituloArchivoEmpresa']."</td>".
                                       "<td>".$items['sNombreArchivoEmpresa']."</td>".                                                                                                                                                                                                                         
                                       "<td>
                                            <div class=\"btn_new send-email btn-icon btn-left\" title=\"Generate this format\" onclick=\"$funcion\"><i class=\"fa fa-plus\"></i></div>
                                            <div class=\"btn_new send-email btn-icon btn-left\" title=\"Open original format in a new window\"><a href=\"".$items['sRutaArchivoOriginal']."\" target=\"_blank\"><i class=\"fa fa-external-link\"></i></a></div>  
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
             $fields .= "\$('#$domroot :input[id=".$i."]').val('".$datos[$i]."');"; 
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
  function get_drivers_list(){
      
      $iConsecutivoCompania = $_POST['iConsecutivoCompania'];
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $error = "0";
      
      $_POST['token'] != "" ? $filtrotoken = "AND iConsecutivo NOT IN (SELECT iConsecutivoOperador FROM cb_quote_format_operadores WHERE iConsecutivo = '".$_POST['token']."')" : $filtrotoken = "";
      
      $sql = "SELECT iConsecutivo, sNombre, iExperienciaYear, iNumLicencia,sAccidentesNum ".
             "FROM ct_operadores ".
             "WHERE iConsecutivoCompania ='$iConsecutivoCompania'  ORDER BY sNombre ASC"; 
        $result = $conexion->query($sql);
        $rows = $result->num_rows;   
        if ($rows > 0) { 
            $paginas_total = $rows;   
            while ($items = $result->fetch_assoc()){        
                     $htmlTabla .= "<tr>".
                                   "<td><input class=\"driverlist_id\" name=\"driverlist_id\" type=\"checkbox\" value=\"".$items['iConsecutivo']."\"></td>".
                                   "<td>".$items['sNombre']."</td>".
                                   "<td>".$items['iExperienciaYear']."</td>".
                                   "<td>".$items['sAccidentesNum']."</td>".                                                                                                                                                                                                                   
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
  //save drivers:
  function save_list(){
      $list = trim($_POST['list']);
      strpos($list,'|') ?  $list = explode('|',$list) : $list = trim($_POST['list']);
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
         $items["iConsecutivo"] != '' ? $token = $items["iConsecutivo"] + 1 : $token = '1'; 
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
  function get_list_drivers(){
      
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $error = "0";
      $token = trim($_POST['token']);

      $sql = "SELECT B.iConsecutivo, sNombre, iExperienciaYear, iNumLicencia,sAccidentesNum ".
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
                                   "<td>".$items['iExperienciaYear']."</td>".
                                   "<td>".$items['sAccidentesNum']."</td>".
                                   "<td></td>".                                                                                                                                                                                                                   
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
                    $conexion->commit();
                    $conexion->close();
          }else{
                    $conexion->rollback();
                    $conexion->close();
                    $msj = "A general system error ocurred : internal error";
                    $error = "1";
          } 
      }
      $response = array("error"=>"$error","msj"=>"$msj");
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
  
?>
