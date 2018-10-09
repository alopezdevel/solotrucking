<?php
  session_start();
  // Generic functions lib 
  include("functiones_genericas.php"); 
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId 
    
  //Catalogo de compañias:
  function get_datagrid(){
     
    include("cn_usuarios.php");
    $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
    $transaccion_exitosa = true;
    
    $registros_por_pagina  = $_POST["registros_por_pagina"];
    $pagina_actual         = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
    $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
        
    //Filtros de informacion //
    $filtroQuery = " WHERE A.iConsecutivo IS NOT NULL AND B.iDeleted = '0' ";
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
    $query_rows = "SELECT COUNT(A.iConsecutivo) AS total ".
                  "FROM cb_endoso_mensual  AS A ".
                  "INNER JOIN ct_companias AS B ON A.iConsecutivoCompania = B.iConsecutivo ".
                  "LEFT JOIN ct_brokers    AS C ON A.iConsecutivoBroker = C.iConsecutivo ".$filtroQuery;
    $Result     = $conexion->query($query_rows);
    $items      = $Result->fetch_assoc();
    $registros  = $items["total"];
    
    if($registros == "0"){$pagina_actual = 0;}
    $paginas_total = ceil($registros / $registros_por_pagina);
    
    if($registros == "0"){
        $limite_superior = 0;
        $limite_inferior = 0;
        $htmlTabla      .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";
    }
    else{
        $pagina_actual  == "0" ? $pagina_actual = 1 : false;
        $limite_superior = $registros_por_pagina;
        $limite_inferior = ($pagina_actual*$registros_por_pagina)-$registros_por_pagina;
        
        $sql    = "SELECT A.iConsecutivo, B.sNombreCompania, C.sName AS sNombreBroker, DATE_FORMAT(A.dFechaAplicacion,'%m/%d/%Y %H:%i') AS dFechaAplicacion,B.iOnRedList,A.sEmail ".
                  "FROM cb_endoso_mensual  AS A ".
                  "INNER JOIN ct_companias AS B ON A.iConsecutivoCompania = B.iConsecutivo ".
                  "LEFT JOIN ct_brokers    AS C ON A.iConsecutivoBroker = C.iConsecutivo ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
        $result = $conexion->query($sql);
        $rows   = $result->num_rows; 
           
        if($rows > 0){    
            while ($items = $result->fetch_assoc()) { 
                 //Redlist:
                 if($items['iOnRedList'] == '1'){$redlist_class = "class=\"row_red\"";$redlist_icon  = "<i class=\"fa fa-star\" style=\"color:#e8051b;margin-right:4px;\"></i>";}
                 else{$redlist_icon = ""; $redlist_class = "";}
                 
                 $btns = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"Edit\"><i class=\"fa fa-pencil-square-o\"></i> <span></span></div>".
                         "<div class=\"btn_delete btn-icon trash btn-left\" title=\"Delete\"><i class=\"fa fa-trash\"></i> <span></span></div>";
                 $htmlTabla .= "<tr ".$redlist_class.">
                                    <td>".$items['iConsecutivo']."</td>".
                                   "<td>".$redlist_icon.$items['sNombreCompania']."</td>".
                                   "<td>".$items['sNombreBroker']."</td>". 
                                   "<td>".$items['sEmail']."</td>".
                                   "<td>".$items['dFechaAplicacion']."</td>".
                                   "<td>$btns</td></tr>";
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
  function get_data(){
    
    #VARIABLES
    $error   = '0';
    $msj     = "";
    $fields  = "";
    $clave   = trim($_POST['clave']);
    $domroot = $_POST['domroot'];
    
    include("cn_usuarios.php");
    $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
    $transaccion_exitosa = true;
    $sql    = "SELECT iConsecutivo,iConsecutivoCompania,iConsecutivoBroker,eStatus,iRatePercent,sComentarios,sEmail,sMensajeEmail,iTipoReporte, DATE_FORMAT(dFechaInicio,'%m/%d/%Y') AS dFechaInicio,DATE_FORMAT(dFechaFin,'%m/%d/%Y') AS dFechaFin ".
              "FROM cb_endoso_mensual WHERE iConsecutivo = '$clave'";
    $result = $conexion->query($sql);
    $items  = $result->num_rows;   
    if ($items > 0) {     
        $drivers = $result->fetch_assoc();
        $llaves  = array_keys($drivers);
        $datos   = $drivers;
        foreach($datos as $i => $b){
             $fields .= "\$('#$domroot :input[id=".$i."]').val('".$datos[$i]."');"; 
        }  
    }
    $conexion->rollback();
    $conexion->close(); 
    $response = array("msj"=>"$msj","error"=>"$error","fields"=>"$fields");   
    echo json_encode($response);
  }
  function save_data(){
      
      #VARIABLES:
      $error   = '0'; 
      $valores = array();
      $campos  = array(); 
      $msj     = ""; 
      
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);
                                                                                                                                                                                                                                            
      $transaccion_exitosa = true;
      $edit_mode           = trim($_POST['edit_mode']);
      $iConsecutivo        = trim($_POST['iConsecutivo']);
      $iConsecutivoCompania= trim($_POST['iConsecutivoCompania']);
      $iConsecutivoBroker  = trim($_POST['iConsecutivoBroker']);
    //$sComentarios        = $_POST['sComentarios'] != "" ? "'".utf8_decode(trim($_POST['sComentarios']))."'" : "''";
      $sComentarios        = "''";
      $iRatePercent        = trim($_POST['iRatePercent']);
      $sEmail              = trim($_POST['sEmail']);  
      $sMensajeEmail       = $_POST['sMensajeEmail'] != "" ? "'".utf8_decode(trim($_POST['sMensajeEmail']))."'" : "''";
      $fecha_inicial       = date('Y-m-d',strtotime(trim($_POST['dFechaInicio'])));
      $fecha_final         = date('Y-m-d',strtotime(trim($_POST['dFechaFin'])));
      $dFechaActual        = date("Y-m-d H:i:s");
      $IP                  = $_SERVER['REMOTE_ADDR'];
      $sUsuario            = $_SESSION['usuario_actual'];
      $iTipoReporte        = trim($_POST['iTipoReporte']);
      
      #INSERT
      if($edit_mode == 'false'){
            $sql     = "INSERT INTO cb_endoso_mensual (iConsecutivoCompania,iConsecutivoBroker,eStatus,sComentarios,sEmail,sMensajeEmail,dFechaInicio,dFechaFin,dFechaIngreso,sIP,sUsuarioIngreso, iRatePercent, iTipoReporte) ".
                       "VALUES ('$iConsecutivoCompania','$iConsecutivoBroker','S',$sComentarios,'$sEmail',$sMensajeEmail,'$fecha_inicial','$fecha_final','$dFechaActual','$IP','$sUsuario','$iRatePercent','$iTipoReporte')";
            $success = $conexion->query($sql);
            if(!($success)){$transaccion_exitosa = false; $msj = "Error: The data of report has not been save successfully, please try again.";}
            else{
                $iConsecutivo = $conexion->insert_id;
                $add_endosos  = set_endosos_data($iTipoReporte,$iConsecutivo,$iConsecutivoCompania,$iConsecutivoBroker,$fecha_inicial,$fecha_final,false,$conexion); 
                if(!($add_endosos)){$transaccion_exitosa = false;}
            }
 
      }
      #UPDATE
      else if($edit_mode == "true"){
          $sql     = "UPDATE cb_endoso_mensual SET sComentarios=$sComentarios,sEmail='$sEmail',sMensajeEmail=$sMensajeEmail, dFechaActualizacion='$dFechaActual',sIP='$IP',sUsuarioActualizacion='$sUsuario',iRatePercent='$iRatePercent' ".
                     "WHERE iConsecutivo='$iConsecutivo' AND iConsecutivoCompania='$iConsecutivoCompania' ";
          $success = $conexion->query($sql);
          if(!($success)){$transaccion_exitosa = false; $msj = "Error: The data of report has not been save successfully, please try again.";}
      }
     
      if($transaccion_exitosa){$conexion->commit();$msj = "The data has been saved successfully.";}else{$conexion->rollback();$error="1";}
      
      $conexion->close();
      $response = array("error"=>"$error","msj"=>"$msj","iConsecutivo"=>"$iConsecutivo");
      echo json_encode($response);
  }  
  function delete_company(){
      
      $error = '0';  
      $msj   = "";  
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      //DESACTIVAR COMPANY:
      $query = "UPDATE ct_companias SET iDeleted = '1' WHERE iConsecutivo = '".$_POST["clave"]."'";
      $conexion->query($query);
      $conexion->affected_rows < 1 ? $transaccion_exitosa = false : $transaccion_exitosa = true;
      if($transaccion_exitosa){
        
        //DESACTIVAR SUS USUARIOS:
        $query   = "UPDATE cu_control_acceso SET iDeleted = '1', hActivado = '0' WHERE iConsecutivoCompania = '".$_POST["clave"]."' AND iConsecutivoTipoUsuario ='2' ";
        $success = $conexion->query($query);
        
        if(!($success)){$msj = "A general system error ocurred : internal error, please try again.";$error = "1";}
        else{$msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The company has been disabled succesfully!</p>';}
        
      }
      else{$msj = "A general system error ocurred : internal error";$error = "1";}
      
      if($error == "0"){$conexion->commit();$conexion->close();}
      else{$conexion->rollback();$conexion->close();}
        
      $response = array("msj"=>"$msj","error"=>"$error");   
      echo json_encode($response);
  }
  function get_broker_data(){
      include("cn_usuarios.php"); 
      $iConsecutivoBroker = trim($_POST['iConsecutivoBroker']);
      $query  = "SELECT sEmail FROM ct_brokers WHERE iConsecutivo = '$iConsecutivoBroker'";
      $result = $conexion->query($query);
      $items  = $result->fetch_assoc();
      echo $items['sEmail'];
  }
  
  #CARGAR DATOS DE ENDOSOS:
  function set_endosos_data($iTipoReporte = "",$iConsecutivo,$iConsecutivoCompania,$iConsecutivoBroker,$fecha_inicial,$fecha_final,$post=false,$conexion = ""){
      
      $valid = true;
      
      if($post){
        include("cn_usuarios.php");  
        $conexion->autocommit(FALSE);
        //$filtro = "AND iFolio NOT IN( SELECT iFolioDetalleFactura FROM add_audimx_partes WHERE iConsecutivoAddenda = '$iConsecutivoAddenda')";
      }
      
      //VERIFICAR TIPO DE REPORTE
      if($iTipoReporte == "") {$flt_query  = "";}else 
      if($iTipoReporte == "1"){$flt_query  = "AND A.iConsecutivoTipoEndoso = '1' ";}else 
      if($iTipoReporte == "2"){$flt_query  = "AND A.iConsecutivoTipoEndoso = '2' ";}
        
      $query  = "SELECT A.iConsecutivo AS iConsecutivoEndoso ".
                "FROM
                    cb_endoso AS A
                    LEFT JOIN cb_endoso_estatus AS B ON A.iConsecutivo = B.iConsecutivoEndoso 
                    LEFT JOIN ct_polizas AS C ON B.iConsecutivoPoliza = C.iConsecutivo 
                WHERE
                    A.iConsecutivoCompania = '$iConsecutivoCompania' 
                    AND C.iConsecutivoBrokers = '$iConsecutivoBroker' 
                    AND B.eStatus  = 'S'
                    AND C.iDeleted = '0' $flt_query
                    AND A.dFechaAplicacion BETWEEN '$fecha_inicial' 
                    AND '$fecha_final'";
      $result = $conexion->query($query);
      $rows   = $result->num_rows; 
      
      if($rows > 0){    
        while ($items = $result->fetch_assoc()){
            if($valid){
                $insert  = "INSERT cb_endoso_mensual_relacion (iConsecutivoEndosoMensual,iConsecutivoEndoso) VALUES('$iConsecutivo','".$items['iConsecutivoEndoso']."')";
                $success = $conexion->query($insert);
                if(!($success)){$valid = false;}
            }else{break;}
        } 
      }
      
      if($post){
          if($valid){$conexion->commit(); echo 'OK';}else{$conexion->rollback(); echo 'Error al actualizar los registros.';}
          $conexion->close();
      }
      
      else if(!($post) && $valid){return $valid;} 
  }
  
  #DETALLE DE ENDOSOS:
  function detalle_get_datagrid(){
    
    $iConsecutivo = trim($_POST['iConsecutivo']);
    $iTipoReporte = trim($_POST['iTipoReporte']); 
    
    include("cn_usuarios.php");
    $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
    $transaccion_exitosa   = true;
    $registros_por_pagina  = $_POST["registros_por_pagina"];
    $pagina_actual         = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
    $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
        
    //Filtros de informacion //
    $filtroQuery = " WHERE A.iConsecutivo='$iConsecutivo' ";
    $array_filtros = explode(",",$_POST["filtroInformacion"]);
    foreach($array_filtros as $key => $valor){
        if($array_filtros[$key] != ""){
            $campo_valor = explode("|",$array_filtros[$key]);
            $campo_valor[0] == 'A.iConsecutivo' ? $filtroQuery.= " AND  ".$campo_valor[0]."='".$campo_valor[1]."' " : $filtroQuery == "" ? $filtroQuery.= " AND  ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'": $filtroQuery.= " AND ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'";
        }
    }
    // ordenamiento//
    $ordenQuery = " ORDER BY ".$_POST["ordenInformacion"]." ".$_POST["sortInformacion"];
    
    if($iTipoReporte == "1"){
       $flt_join = "LEFT  JOIN ct_unidades                AS U ON C.iConsecutivoUnidad = U.iConsecutivo ";
       $flt_join.= "LEFT  JOIN ct_unidad_modelo           AS M ON U.iModelo            = M.iConsecutivo";
       $flt_field= "C.sVINUnidad, C.iConsecutivoUnidad, U.sVIN, U.iYear, M.sAlias AS sMake, U.sTipo, C.iPDAmount AS iPDAmount "; 
    }else if($iTipoReporte == "2"){
       $flt_join  = "LEFT JOIN ct_operadores AS U ON C.iConsecutivoOperador = U.iConsecutivo "; 
       $flt_field = "C.sNombreOperador, C.iConsecutivoOperador, U.sNombre, DATE_FORMAT(U.dFechaNacimiento,'%m/%d/%Y') AS dFechaNacimiento, U.iExperienciaYear, U.iNumLicencia, DATE_FORMAT(U.dFechaExpiracionLicencia,'%m/%d/%Y') AS dFechaExpiracionLicencia "; 
    }
    
    //contando registros // 
    $query_rows = "SELECT COUNT(A.iConsecutivo) AS total ".
                  "FROM cb_endoso_mensual AS A ".
                  "INNER JOIN cb_endoso_mensual_relacion AS B ON A.iConsecutivo       = B.iConsecutivoEndosoMensual ".
                  "INNER JOIN cb_endoso                  AS C ON B.iConsecutivoEndoso = C.iConsecutivo AND A.iTipoReporte = C.iConsecutivoTipoEndoso  ".
                  "INNER JOIN cb_endoso_estatus          AS D ON C.iConsecutivo       = D.iConsecutivoEndoso AND D.eStatus = 'S' ".
                  "INNER JOIN ct_polizas                 AS E ON D.iConsecutivoPoliza = E.iConsecutivo AND E.iDeleted = '0' AND A.iConsecutivoBroker = E.iConsecutivoBrokers  ".
                  "INNER JOIN ct_tipo_poliza             AS F ON E.iTipoPoliza        = F.iConsecutivo  ".$flt_join.$filtroQuery;
    $Result     = $conexion->query($query_rows);
    $items      = $Result->fetch_assoc();
    $registros  = $items["total"];
    
    if($registros == "0"){$pagina_actual = 0;}
    $paginas_total = ceil($registros / $registros_por_pagina);
    
    if($registros == "0"){
        $limite_superior = 0;
        $limite_inferior = 0;
        $htmlTabla      .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";
    }
    else{
        $pagina_actual  == "0" ? $pagina_actual = 1 : false;
        $limite_superior = $registros_por_pagina;
        $limite_inferior = ($pagina_actual*$registros_por_pagina)-$registros_por_pagina;
        
        $sql    = "SELECT A.iConsecutivo AS iConsecutivoEndosoMensual,C.iConsecutivo, A.iConsecutivoCompania, IF(C.eAccion = 'A','ADD','DELETE') AS eAccion, C.iConsecutivo AS iConsecutivoEndoso, E.sNumeroPoliza, F.sDescripcion,DATE_FORMAT(C.dFechaAplicacion,'%m/%d/%Y') AS dFechaAplicacion, ".
                  "$flt_field ".
                  "FROM cb_endoso_mensual AS A ".
                  "INNER JOIN cb_endoso_mensual_relacion AS B ON A.iConsecutivo       = B.iConsecutivoEndosoMensual ".
                  "INNER JOIN cb_endoso                  AS C ON B.iConsecutivoEndoso = C.iConsecutivo AND A.iTipoReporte = C.iConsecutivoTipoEndoso  ".
                  "INNER JOIN cb_endoso_estatus          AS D ON C.iConsecutivo       = D.iConsecutivoEndoso AND D.eStatus = 'S' ".
                  "INNER JOIN ct_polizas                 AS E ON D.iConsecutivoPoliza = E.iConsecutivo AND E.iDeleted = '0' AND A.iConsecutivoBroker = E.iConsecutivoBrokers  ".
                  "INNER JOIN ct_tipo_poliza             AS F ON E.iTipoPoliza        = F.iConsecutivo  ".$flt_join.$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
        $result = $conexion->query($sql);
        $rows   = $result->num_rows; 
           
        if($rows > 0){    
            while ($items = $result->fetch_assoc()) { 
                
                if($iTipoReporte == "1"){
                    $Descripcion = $items['iYear']." - ".$items['sMake']." - ".$items['sVIN']." - \$".number_format($items['iPDAmount'],2,'.','')." ".$items['sTipo'];
                }else if($iTipoReporte == "2"){
                    $Descripcion = $items['sNombre']." - DOB: ".$items['dFechaNacimiento']." - LIC: ".$items['iNumLicencia']." EXP.".$items['dFechaExpiracionLicencia'];
                }
                 
                $btns       = "<div class=\"btn_delete_detalle btn-icon trash btn-left\" title=\"Delete of this Report\"><i class=\"fa fa-trash\"></i> <span></span></div>";
                $htmlTabla .= "<tr>".
                              "<td style=\"width: 25px;\">".$items['iConsecutivo']."</td>".
                              "<td class=\"txt-c\">".$items['eAccion']."</td>".
                              "<td>".$items['sNumeroPoliza']." - ".$items['sDescripcion']."</td>". 
                              "<td>".$Descripcion."</td>".
                              "<td class=\"txt-c\">".$items['dFechaAplicacion']."</td>".
                              "<td>$btns</td></tr>";
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
?>
