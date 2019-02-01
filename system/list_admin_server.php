<?php
  session_start();
  // Generic functions lib 
  include("functiones_genericas.php"); 
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId 
  
  function get_datagrit(){
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
      $sql    = "SELECT A.iConsecutivo, A.sNombreCompania, A.iOnRedList FROM ct_companias AS A ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
      $result = $conexion->query($sql);
      $rows   = $result->num_rows; 
         
        if ($rows > 0) {    
            while ($items = $result->fetch_assoc()){ 
                
                // Contar total de endosos a polizas vigentes:
                $query  = "SELECT COUNT(B.iConsecutivo) ".
                          "FROM       cb_endoso         AS B ".
                          "INNER JOIN cb_endoso_estatus AS C ON B.iConsecutivo       = C.iConsecutivoEndoso ".
                          "LEFT JOIN  ct_polizas        AS P ON C.iConsecutivoPoliza = P.iConsecutivo AND P.iDeleted = '0' AND P.dFechaCaducidad >= CURDATE() ".
                          "WHERE B.iConsecutivoCompania = '".$items['iConsecutivo']."' GROUP BY B.iConsecutivo";
                $r      = $conexion->query($query);  
                $endosos= mysql_fetch_all($r);
                $total_endosos = count($endosos);
                
                // Contar unidades:
                $query  = "SELECT COUNT(A.iConsecutivo) AS total FROM  ct_unidades AS A WHERE A.iConsecutivoCompania = '".$items['iConsecutivo']."' AND A.iDeleted='0'";
                $r      = $conexion->query($query);  
                $unidad = $r->fetch_assoc();
                
                // Contar operadores:
                $query  = "SELECT COUNT(A.iConsecutivo) AS total FROM  ct_operadores AS A WHERE A.iConsecutivoCompania = '".$items['iConsecutivo']."' AND A.iDeleted='0'";
                $r      = $conexion->query($query);  
                $driver = $r->fetch_assoc();
              
                $btn_confirm = "";
                $descripcion = ""; 
                 
                //Redlist:
                $items['iOnRedList'] == '1' ? $redlist_icon = "<i class=\"fa fa-star\" style=\"color:#e8051b;margin-right:4px;\"></i>" : $redlist_icon = ""; 
                 $htmlTabla .= "<tr>
                                    <td>".$items['iConsecutivo']."</td>".
                                   "<td>".$redlist_icon.$items['sNombreCompania']."</td>".
                                   "<td class=\"txt-c\">".$driver['total']."</td>".
                                   "<td class=\"txt-c\">".$unidad['total']."</td>".
                                   "<td class=\"txt-c\">".$total_endosos."</td>". 
                                   "<td>".
                                   "<div class=\"btn_units_list btn-icon edit btn-left\" title=\"Open vehicles list\"><i class=\"fa fa-truck\"></i></div>".
                                   "<div class=\"btn_drivers_list btn-icon edit btn-left\" title=\"Open drivers list\"><i class=\"fa fa-users\"></i></div>".
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
  
  #OPERADORES:
  function get_drivers_active(){
      
      $iConsecutivoPoliza   = trim($_POST['iConsecutivoPoliza']);
      $iConsecutivoCompania = trim($_POST['iConsecutivoCompania']);
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
    
      $registros_por_pagina  = $_POST["registros_por_pagina"];
      $pagina_actual         = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
      $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
        
      //Filtros de informacion //
      $filtroQuery   = " WHERE  iConsecutivoCompania = '$iConsecutivoCompania' AND iDeleted='0' ";
      $array_filtros = explode(",",$_POST["filtroInformacion"]);
      foreach($array_filtros as $key => $valor){
        if($array_filtros[$key] != ""){
            $campo_valor     = explode("|",$array_filtros[$key]);
            $campo_valor[0] == 'iConsecutivo' ? $filtroQuery.= " AND  ".$campo_valor[0]."='".$campo_valor[1]."' " : $filtroQuery == "" ? $filtroQuery.= " AND  ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'": $filtroQuery.= " AND ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'";
        }
      }
    // ordenamiento//
    $ordenQuery = " ORDER BY ".$_POST["ordenInformacion"]." ".$_POST["sortInformacion"];
    
    //contando registros // 
    $query_rows = "SELECT COUNT(iConsecutivo) AS total FROM ct_operadores ".$filtroQuery;
    $Result     = $conexion->query($query_rows) or die($conexion->error);
    $items      = $Result->fetch_assoc();
    $registros  = $items["total"];
    
    if($registros == "0"){$pagina_actual = 0;}
    $paginas_total = ceil($registros / $registros_por_pagina);
    if($registros == "0"){
        $limite_superior = 0;
        $limite_inferior = 0;
        $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";
    }
    else{
        $pagina_actual == "0" ? $pagina_actual = 1 : false;
        $limite_superior = $registros_por_pagina;
        $limite_inferior = ($pagina_actual*$registros_por_pagina)-$registros_por_pagina;
        $sql    = "SELECT iConsecutivo, sNombre, DATE_FORMAT(dFechaNacimiento,'%m/%d/%Y') AS dFechaNacimiento, DATE_FORMAT(dFechaExpiracionLicencia,'%m/%d/%Y') AS dFechaExpiracionLicencia, iExperienciaYear, iNumLicencia, (CASE eTipoLicencia WHEN  'Federal/B1' THEN 'Federal / B1' WHEN  'Commercial/CDL-A' THEN 'Commercial / CDL - A' END) AS TipoLicencia,eModoIngreso ".
                  "FROM ct_operadores ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior; 
        $result = $conexion->query($sql) or die($conexion->error);
        $rows   = $result->num_rows;   
        if ($rows > 0){    
                while ($items = $result->fetch_assoc()){ 
                    
                    $action  = "";
                    $dateApp = ""; 
                    $endALNo = "";
                    $endAL   = "";
                    $endMTCNo= "";
                    $endMTC  = "";
                    $endPDNo = "";
                    $endPD   = "";
                    
                    //Revisar polizas:
                    $query  = "SELECT iConsecutivoPoliza, B.sNumeroPoliza, C.sDescripcion AS sTipoPoliza, C.sAlias, DATE_FORMAT(A.dFechaIngreso,'%m/%d/%Y') AS dFechaIngreso,eModoIngreso ".
                               "FROM cb_poliza_operador   AS A ".
                               "INNER JOIN ct_polizas     AS B ON A.iConsecutivoPoliza = B.iConsecutivo AND B.iDeleted = '0' AND B.dFechaCaducidad >= CURDATE() ".
                               "LEFT JOIN  ct_tipo_poliza AS C ON B.iTipoPoliza = C.iConsecutivo ".
                               "WHERE A.iConsecutivoOperador = '".$items['iConsecutivo']."' AND A.iDeleted = '0' ORDER BY dFechaIngreso DESC";
                    $r      = $conexion->query($query) or die($conexion->error);
                    $total  = $r->num_rows;
                    $PDApply= false;  
                    if($total > 0){
                        while ($row = $r->fetch_assoc()){
                            
                            //REVISAMO MODO DE INGRESO:
                            if($row['eModoIngreso'] != 'ENDORSEMENT'){
                                
                                $dateApp = $row['eModoIngreso']; //DATE OF APPLICATION
                                
                                switch($row['sAlias']){
                                    case 'AL'  : $endAL  = "BIND"; break;
                                    case 'PD'  : $endPD  = "BIND"; break;
                                    case 'MTC' : $endMTC = "BIND"; break;
                                }
                            }
                            else{
                                
                               $dateApp = $row['dFechaIngreso']; 
                               $query = "SELECT C.iConsecutivoEndoso, C.sNumeroEndosoBroker, C.rImporteEndosoBroker, IF (A.iEndosoMultiple = '1', B.eAccion, IF(A.eAccion = 'A', 'ADD','DELETE') ) AS eAccion ".
                                        "FROM cb_endoso AS A ".
                                        "INNER JOIN cb_endoso_estatus  AS C ON A.iConsecutivo = C.iConsecutivoEndoso ".
                                        "LEFT  JOIN cb_endoso_operador AS B ON A.iConsecutivo = B.iConsecutivoEndoso ".
                                        "WHERE A.iDeleted = '0' AND C.iConsecutivoPoliza = '".$row['iConsecutivoPoliza']."' ".
                                        "AND IF(A.iEndosoMultiple = '0', A.iConsecutivoOperador = '".$items['iConsecutivo']."', B.iConsecutivoOperador = '".$items['iConsecutivo']."') ".
                                        "ORDER BY C.iConsecutivoEndoso DESC LIMIT 1";
                               $r2    = $conexion->query($query) or die($conexion->error);
                               $endo  = $r2->fetch_assoc();
                               switch($row['sAlias']){
                                    case 'AL'  : 
                                        $endALNo = $endo['sNumeroEndosoBroker']; 
                                        $endAl   = $endo['rImporteEndosoBroker'] > 0 ? "\$ ".number_format($endo['rImporteEndosoBroker'],2,'.', ',') : ""; 
                                        $action  = $endo['eAccion']; 
                                    break;
                                    case 'PD'  : 
                                        $endPDNo = $endo['sNumeroEndosoBroker']; 
                                        $endPD   = $endo['rImporteEndosoBroker'] > 0 ? "\$ ".number_format($endo['rImporteEndosoBroker'],2,'.', ',') : "";
                                        $action  = $endo['eAccion']; 
                                    break;
                                    case 'MTC' : 
                                        $endMTCNo= $endo['sNumeroEndosoBroker']; 
                                        $endMTC  = $endo['rImporteEndosoBroker'] > 0 ? "\$ ".number_format($endo['rImporteEndosoBroker'],2,'.', ',') : ""; 
                                        $action  = $endo['eAccion']; 
                                    break;
                               }
                            } 
                            
                            if($row['sAlias'] == "PD"){$PDApply = true;}
                        }
                        $polizas .= "</table>"; 
                    }
                    
                    
                    $htmlTabla .= "<tr>".
                                  "<td id=\"".$items['iConsecutivo']."\">".$items['sNombre']."</td>".
                                  "<td class=\"txt-c\">".$items['dFechaNacimiento']."</td>".
                                  "<td>".$items['iNumLicencia']."</td>". 
                                  "<td class=\"txt-c\">".$items['dFechaExpiracionLicencia']."</td>".  
                                  "<td class=\"txt-c\">$action</td>".
                                  "<td class=\"txt-c\">$dateApp</td>".  
                                  "<td class=\"txt-c\">$endALNo</td>".
                                  "<td class=\"txt-c\">$endAL</td>".
                                  "<td class=\"txt-c\">$endMTCNo</td>".
                                  "<td class=\"txt-c\">$endMTC</td>".
                                  "<td class=\"txt-c\">$endPDNo</td>".
                                  "<td class=\"txt-c\">$endPD</td>".
                                  //"<td style=\"padding: 0px!important;\">".$polizas."</td>".                                                                                                                                                                                                                    
                                  "<td>".
                                  //"<div class=\"btn_edit btn-icon edit btn-left\" title=\"Edit data\"><i class=\"fa fa-pencil-square-o\"></i></div>".
                                  "</td>".
                                  "</tr>";  
                }
                $conexion->rollback();
                $conexion->close();                                                                                                                                                                       
        }else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>"   ;    }  
    }
    $htmlTabla = utf8_decode($htmlTabla);
    $response  = array("total"=>"$paginas_total","pagina"=>"$pagina_actual","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error");   
    echo json_encode($response);  
  }
  
  #UNIDADES:
  function get_units_active(){
      
      $iConsecutivoPoliza   = trim($_POST['iConsecutivoPoliza']);
      $iConsecutivoCompania = trim($_POST['iConsecutivoCompania']); 
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
    
      $registros_por_pagina = $_POST["registros_por_pagina"];
      $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
      $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
        
      //Filtros de informacion //
      $filtroQuery = " WHERE iConsecutivoCompania = '$iConsecutivoCompania' AND iDeleted='0' ";
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
    $query_rows = "SELECT COUNT(A.iConsecutivo) AS total FROM ct_unidades A ".
                  "LEFT JOIN ct_unidad_radio B ON A.iConsecutivoRadio = B.iConsecutivo ".
                  "LEFT JOIN ct_unidad_modelo C ON A.iModelo = C.iConsecutivo ".$filtroQuery;
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
        $sql = "SELECT A.iConsecutivo, C.sAlias AS Make, C.sDescripcion AS sMakeDescription, B.sDescripcion AS Radio, iYear, sVIN, sPeso, sTipo, sModelo, eModoIngreso, iTotalPremiumPD ".
               "FROM ct_unidades A ".
               "LEFT JOIN ct_unidad_radio B ON A.iConsecutivoRadio = B.iConsecutivo ".
               "LEFT JOIN ct_unidad_modelo C ON A.iModelo = C.iConsecutivo ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior; 
        $result = $conexion->query($sql);
        $rows = $result->num_rows;   
        if ($rows > 0) {    
                while ($items = $result->fetch_assoc()){
                
                    $action  = "";
                    $dateApp = ""; 
                    $endALNo = "";
                    $endAL   = "";
                    $endMTCNo= "";
                    $endMTC  = "";
                    $endPDNo = "";
                    $endPD   = "";
                    
                    //Revisar polizas:
                    $query  = "SELECT iConsecutivoPoliza, B.sNumeroPoliza, C.sDescripcion AS sTipoPoliza, C.sAlias, DATE_FORMAT(A.dFechaIngreso,'%m/%d/%Y') AS dFechaIngreso,eModoIngreso ".
                               "FROM cb_poliza_unidad     AS A ".
                               "INNER JOIN ct_polizas     AS B ON A.iConsecutivoPoliza = B.iConsecutivo AND B.iDeleted = '0' AND B.dFechaCaducidad >= CURDATE() ".
                               "LEFT JOIN  ct_tipo_poliza AS C ON B.iTipoPoliza = C.iConsecutivo ".
                               "WHERE A.iConsecutivoUnidad = '".$items['iConsecutivo']."' AND A.iDeleted = '0' ORDER BY dFechaIngreso DESC";
                    $r      = $conexion->query($query);
                    $total  = $r->num_rows;
                    $PDApply= false;  
                    if($total > 0){
                        while ($row = $r->fetch_assoc()){
                            
                            //REVISAMO MODO DE INGRESO:
                            if($row['eModoIngreso'] != 'ENDORSEMENT'){
                                
                                $dateApp = $row['eModoIngreso']; //DATE OF APPLICATION
                                
                                switch($row['sAlias']){
                                    case 'AL'  : $endAL  = "BIND"; break;
                                    case 'PD'  : $endPD  = "BIND"; break;
                                    case 'MTC' : $endMTC = "BIND"; break;
                                }
                            }
                            else{
                                
                               $dateApp = $row['dFechaIngreso']; 
                               $query = "SELECT C.iConsecutivoEndoso, C.sNumeroEndosoBroker, C.rImporteEndosoBroker, IF(A.iEndosoMultiple='1',B.eAccion, A.eAccion) AS eAccion ".
                                        "FROM cb_endoso AS A ".
                                        "INNER JOIN cb_endoso_estatus AS C ON A.iConsecutivo = C.iConsecutivoEndoso ".
                                        "LEFT JOIN cb_endoso_unidad   AS B ON A.iConsecutivo = B.iConsecutivoEndoso ".
                                        "WHERE A.iDeleted = '0' AND C.iConsecutivoPoliza = '".$row['iConsecutivoPoliza']."' AND (A.iConsecutivoUnidad='".$items['iConsecutivo']."' OR B.iConsecutivoUnidad ='".$items['iConsecutivo']."') ".
                                        "ORDER BY C.iConsecutivoEndoso DESC LIMIT 1";
                               $r2    = $conexion->query($query);
                               $endo  = $r2->fetch_assoc();
                               
                               switch($row['sAlias']){
                                    case 'AL'  : $endALNo = $endo['sNumeroEndosoBroker']; $endAl = $endo['rImporteEndosoBroker'] > 0 ? "\$ ".number_format($endo['rImporteEndosoBroker'],2,'.', ',') : ""; $action = $endo['eAccion']; break;
                                    case 'PD'  : $endPDNo = $endo['sNumeroEndosoBroker']; $endPD = $endo['rImporteEndosoBroker'] > 0 ? "\$ ".number_format($endo['rImporteEndosoBroker'],2,'.', ',') : ""; $action = $endo['eAccion']; break;
                                    case 'MTC' : $endMTCNo= $endo['sNumeroEndosoBroker']; $endMTC= $endo['rImporteEndosoBroker'] > 0 ? "\$ ".number_format($endo['rImporteEndosoBroker'],2,'.', ',') : ""; $action = $endo['eAccion']; break;
                               }
                            } 
                            
                            if($row['sAlias'] == "PD"){$PDApply = true;}
                        }
                        $polizas .= "</table>"; 
                    }
                    
                    $PDApply && $items['iTotalPremiumPD'] > 0 ? $value = "\$ ".number_format($items['iTotalPremiumPD'],2,'.',',') : $value = "";           
                    $htmlTabla .= "<tr>".
                                  "<td id=\"".$items['iConsecutivo']."\" class=\"txt-c\">".$items['iYear']."</td>".
                                  "<td>".$items['Make']."</td>".
                                  "<td>".$items['sVIN']."</td>".
                                  "<td class=\"txt-r\">".$value."</td>".
                                  "<td class=\"txt-c\">$action</td>".
                                  "<td class=\"txt-c\">$dateApp</td>".  
                                  "<td class=\"txt-c\">$endALNo</td>".
                                  "<td class=\"txt-c\">$endAL</td>".
                                  "<td class=\"txt-c\">$endMTCNo</td>".
                                  "<td class=\"txt-c\">$endMTC</td>".
                                  "<td class=\"txt-c\">$endPDNo</td>".
                                  "<td class=\"txt-c\">$endPD</td>".
                                  //"<td style=\"padding: 0px!important;\">".$polizas."</td>".                                                                                                                                                                                                                    
                                  "<td>".
                                    //"<div class=\"btn_edit btn-icon edit btn-left\" title=\"Edit data\"><i class=\"fa fa-pencil-square-o\"></i></div>".
                                  "</td>".
                                  "</tr>";
                      
                }
                $conexion->rollback();
                $conexion->close();                                                                                                                                                                       
        }else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>"   ;    }  
    }

     $response = array("total"=>"$paginas_total","pagina"=>"$pagina_actual","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
     echo json_encode($response);
  }
  
  
  /*************/
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
