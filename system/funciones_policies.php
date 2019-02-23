<?php
  session_start();
  // Generic functions lib 
  include("functiones_genericas.php"); 
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId  
 
  function get_policies(){
    include("cn_usuarios.php");
    $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
    $transaccion_exitosa = true;
    
    $registros_por_pagina = $_POST["registros_por_pagina"];
    $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
    $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
        
    //Filtros de informacion //
    $filtroQuery = " WHERE A.iDeleted = '0' AND dFechaCaducidad >= CURDATE()";
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
    $query_rows = "SELECT COUNT(A.iConsecutivo) AS total FROM ct_polizas A 
                   LEFT JOIN ct_companias B ON A.iConsecutivoCompania = B.iConsecutivo
                   LEFT JOIN ct_brokers C ON A.iConsecutivoBrokers = C.iConsecutivo
                   LEFT JOIN ct_tipo_poliza D ON A.iTipoPoliza = D.iConsecutivo ".$filtroQuery;
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
        $sql = "SELECT A.iConsecutivo AS clave, sNumeroPoliza, sNombreCompania, sName, sDescripcion, iOnRedList, DATE_FORMAT(dFechaInicio,'%m/%d/%Y') AS dFechaInicio, DATE_FORMAT(dFechaCaducidad,'%m/%d/%Y') AS dFechaCaducidad, iConsecutivoArchivo,A.iConsecutivoCompania, iTipoPoliza, iConsecutivoArchivoPFA ".
               "FROM ct_polizas A ".
               "LEFT JOIN ct_companias B ON A.iConsecutivoCompania = B.iConsecutivo ".
               "LEFT JOIN ct_brokers C ON A.iConsecutivoBrokers = C.iConsecutivo ".
               "LEFT JOIN ct_tipo_poliza D ON A.iTipoPoliza = D.iConsecutivo ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
        $result = $conexion->query($sql);
        $rows = $result->num_rows;   
        if ($rows > 0) {    
                while ($items = $result->fetch_assoc()){ 
                   //if($items["sNumeroPoliza"] != ""){
                           
                         //Redlist:
                         $items['iOnRedList'] == '1' ? $redlist_icon = "<i class=\"fa fa-star\" style=\"color:#e8051b;margin-right:4px;\"></i>" : $redlist_icon = "";
                         
                         $items['iConsecutivoArchivo'] == '' ? $btn_pdf = "" : $btn_pdf = "<div class=\"btn_view_pdf btn-icon pdf btn-left\" title=\"view Policy Jacker\" onclick=\"window.open('open_pdf.php?idfile=".$items['iConsecutivoArchivo']."&type=company');\"><i class=\"fa fa-file-pdf-o\"></i> <span></span></div>" ;  
                         if($items['iConsecutivoArchivoPFA'] != '') $btn_pdf .= "<div class=\"btn_view_pdf btn-icon pdf btn-left\" title=\"view Policy PFA\" onclick=\"window.open('open_pdf.php?idfile=".$items['iConsecutivoArchivoPFA']."&type=company');\"><i class=\"fa fa-file-pdf-o\"></i> <span></span></div>" ;  
                
                
                         if($items['dFechaCaducidad'] != ''){
                            
                            $fechaCaducidad = format_date($items['dFechaCaducidad']);
                            $fechaHoy = date("Y-m-d"); 
                            $EstatusPoliza = "";
                            $dias = (strtotime($fechaCaducidad)-strtotime($fechaHoy))/86400; 
                            $dias = floor($dias); 
     
                            if($dias >= 0 ){
                               if($dias <= 15){
                                  $EstatusPoliza = "class=\"yellow\"";
                               }else if($dias > 15){
                                  $EstatusPoliza = "class=\"green\""; 
                               } 
                            }else{
                                $EstatusPoliza = "class=\"red\"";
                            }
                            
                             
                         }
                         if($dias >= 0){
                            $iConsecutivoPoliza = $items['clave'];
                            //$btn_drivers  = "<div class=\"btn-icon view btn-left\" title=\"View list of Drivers & Units\" onclick=\"fn_policies.get_list_description('$iConsecutivoPoliza','".$items['iConsecutivoCompania']."','".$items['sNumeroPoliza']."');\"><i class=\"fa fa-list-alt\"></i> <span></span></div>"; 
         
                            $htmlTabla .= "<tr $EstatusPoliza >
                                            <td>".$items['clave']."</td>".
                                           "<td>".$redlist_icon.$items['sNombreCompania']."</td>".
                                           "<td>".$items['sNumeroPoliza']."</td>".
                                           "<td>".$items['sDescripcion']."</td>".  
                                           "<td>".$items['dFechaInicio']."</td>".
                                           "<td>".$items['dFechaCaducidad']."</td>".                                                                                                                                                                                                                     
                                           "<td>
                                                <div class=\"btn_edit btn-icon edit btn-left\" title=\"Edit Policy\"><i class=\"fa fa-pencil-square-o\"></i> <span></span></div>
                                                <div class=\"btn_delete btn-icon trash btn-left\" title=\"Mark Policy as Canceled\"><i class=\"fa fa-trash\"></i> <span></span></div>
                                                $btn_pdf 
                                                $btn_drivers
                                           </td></tr>";   
                         }   
                }
                $conexion->rollback();
                $conexion->close();                                                                                                                                                                       
        }else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>"   ;    }  
    }

     $response = array("total"=>"$paginas_total","pagina"=>"$pagina_actual","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
     echo json_encode($response);
  }
  function get_expired_policies(){
    include("cn_usuarios.php");
    $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
    $transaccion_exitosa = true;
    
    $registros_por_pagina = $_POST["registros_por_pagina"];
    $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
    $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
        
    //Filtros de informacion //
    $filtroQuery = " WHERE A.iDeleted = '1' OR A.dFechaCaducidad < CURDATE() ";
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
    $query_rows = "SELECT COUNT(A.iConsecutivo) AS total ".
                  "FROM      ct_polizas     AS A ".
                  "LEFT JOIN ct_companias   AS B ON A.iConsecutivoCompania = B.iConsecutivo ".
                  "LEFT JOIN ct_brokers     AS C ON A.iConsecutivoBrokers = C.iConsecutivo ".
                  "LEFT JOIN ct_tipo_poliza AS D ON A.iTipoPoliza = D.iConsecutivo".$filtroQuery;
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
        $sql = "SELECT A.iConsecutivo AS clave, sNumeroPoliza, sNombreCompania, sName, sDescripcion, iOnRedList, DATE_FORMAT(dFechaInicio,'%m/%d/%Y') AS dFechaInicio, DATE_FORMAT(dFechaCaducidad,'%m/%d/%Y') AS dFechaCaducidad, iConsecutivoArchivo,A.iConsecutivoCompania, iTipoPoliza, iConsecutivoArchivoPFA,IF(A.iDeleted = '1','DELETED','EXPIRED') AS Estatus, A.iDeleted ".
               "FROM      ct_polizas     AS A ".
               "LEFT JOIN ct_companias   AS B ON A.iConsecutivoCompania = B.iConsecutivo ".
               "LEFT JOIN ct_brokers     AS C ON A.iConsecutivoBrokers = C.iConsecutivo ".
               "LEFT JOIN ct_tipo_poliza AS D ON A.iTipoPoliza = D.iConsecutivo ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
        $result = $conexion->query($sql);
        $rows = $result->num_rows;   
        if ($rows > 0) {    
                while ($items = $result->fetch_assoc()){ 
                  
                     //Redlist:
                     $items['iOnRedList'] == '1' ? $redlist_icon = "<i class=\"fa fa-star\" style=\"color:#e8051b;margin-right:4px;\"></i>" : $redlist_icon = "";
                     
                     $items['iConsecutivoArchivo'] == '' ? $btn_pdf = "" : $btn_pdf = "<div class=\"btn_view_pdf btn-icon pdf btn-left\" title=\"view Policy Jacker\" onclick=\"window.open('open_pdf.php?idfile=".$items['iConsecutivoArchivo']."&type=company');\"><i class=\"fa fa-file-pdf-o\"></i> <span></span></div>" ;  
                     if($items['iConsecutivoArchivoPFA'] != '') $btn_pdf .= "<div class=\"btn_view_pdf btn-icon pdf btn-left\" title=\"view Policy PFA\" onclick=\"window.open('open_pdf.php?idfile=".$items['iConsecutivoArchivoPFA']."&type=company');\"><i class=\"fa fa-file-pdf-o\"></i> <span></span></div>" ;  
            
            
                     /*if($items['dFechaCaducidad'] != ''){
                        
                        $fechaCaducidad = format_date($items['dFechaCaducidad']);
                        $fechaHoy = date("Y-m-d"); 
                        $EstatusPoliza = "";
                        $dias = (strtotime($fechaCaducidad)-strtotime($fechaHoy))/86400; 
                        $dias = floor($dias);     
                        if($dias <= 0 ){$EstatusPoliza = "class=\"red\"";}
                     }*/
                     
                     $items['iDeleted'] == 0 ? $EstatusPoliza = "class=\"red\"" : $EstatusPoliza = "";
                     
                     $iConsecutivoPoliza = $items['clave'];
                     $htmlTabla .= "<tr $EstatusPoliza>
                                        <td>".$items['clave']."</td>".
                                       "<td>".$redlist_icon.$items['sNombreCompania']."</td>".
                                       "<td>".$items['sNumeroPoliza']."</td>".
                                       //"<td>".$items['sName']."</td>". 
                                       "<td>".$items['sDescripcion']."</td>".  
                                       "<td>".$items['dFechaInicio']."</td>".
                                       "<td>".$items['dFechaCaducidad']."</td>".                                                                                                                                                                                                                     
                                       "<td style=\"text-align:center;\">".$items['Estatus']."</td></tr>";  
                        
                }
                $conexion->rollback();
                $conexion->close();                                                                                                                                                                       
        }else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>"   ;    }  
    }

     $response = array("total"=>"$paginas_total","pagina"=>"$pagina_actual","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
     echo json_encode($response);
  }
  function get_policy(){
      $error = '0';
      $msj = "";
      $fields = "";
      $clave = trim($_POST['clave']);
      $domroot = $_POST['domroot'];
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                 
      $sql = "SELECT A.iConsecutivo, sNumeroPoliza, A.iConsecutivoCompania, iConsecutivoBrokers, iTipoPoliza,iConsecutivoAseguranza, A.eTipoCategoria, A.eTipoEnvio, ". 
             "DATE_FORMAT(dFechaInicio,'%m/%d/%Y') AS dFechaInicio, DATE_FORMAT(dFechaCaducidad,'%m/%d/%Y') AS dFechaCaducidad, ".
             "B.sNombreArchivo AS txtPolicyJacker, iConsecutivoArchivo, iConsecutivoArchivoPFA, C.sNombreArchivo AS txtPolicyPFA, iConsecutivoInsurancePremiumFinancing, ".
             "iPremiumAmount, iDeductible, iDeductibleAdditional, iPremiumAmountAdditional,iCGL_EachOccurrence,iCGL_DamageRented,iCGL_MedExp,iCGL_PersonalAdvInjury, ".
             "iCGL_GeneralAggregate,iCGL_ProductsComp ".
             "FROM ct_polizas A ".
             "LEFT JOIN cb_company_files B ON A.iConsecutivoArchivo = B.iConsecutivo ".
             "LEFT JOIN cb_company_files C ON A.iConsecutivoArchivoPFA = C.iConsecutivo ".
             "WHERE A.iConsecutivo = '".$clave."'";  
      $result = $conexion->query($sql);
      $items = $result->num_rows;   
      if ($items > 0) {     
        $data = $result->fetch_assoc();
        $llaves  = array_keys($data);
        $datos   = $data;
        foreach($datos as $i => $b){
            $fields .= "\$('#$domroot :input[id=".$i."]').val('".$datos[$i]."');"; 
        }  
      }
      $conexion->rollback();
      $conexion->close(); 
      $response = array("msj"=>"$msj","error"=>"$error","fields"=>"$fields");   
      echo json_encode($response);
  }
  function save_policy(){
      include("funciones_genericas.php");
      $error = '0'; 
      $valores = array();
      $campos  = array(); 
      $msj = "";  
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $_POST['sNumeroPoliza']   = strtoupper($_POST['sNumeroPoliza']);
      $_POST['dFechaInicio']    = format_date($_POST['dFechaInicio']); 
      $_POST['dFechaCaducidad'] = format_date($_POST['dFechaCaducidad']); 
      $_POST["edit_mode"] == 'true' ? $filtroQ = " AND iConsecutivo != '".$_POST['iConsecutivo']."'" : $filtroQ = ""; 
      
      //VERIFICAR QUE NO EXISTA UN MISMO TIPO Y NUMERO DE POLIZA PARA ESA COMPANIA QUE NO ESTE VENCIDA O BORRADA.
      $query = "SELECT COUNT(iConsecutivo) AS total 
                FROM   ct_polizas 
                WHERE  sNumeroPoliza ='".$_POST['sNumeroPoliza']."' AND iTipoPoliza = '".$_POST['iTipoPoliza']."'
                AND iConsecutivoCompania = '".$_POST['iConsecutivoCompania']."' 
                AND iDeleted = '0' AND dFechaCaducidad >= CURDATE() $filtroQ";
      $result = $conexion->query($query);
      $valida = $result->fetch_assoc();
      
      if($valida['total'] != '0'){
         // if($_POST["edit_mode"] != 'true'){
              $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>
                      Error: The Policy that you trying to add already exists. Please you verify the data.</p>';
              $error = '1';
          /*}else{
             foreach($_POST as $campo => $valor){
                if($campo != "accion" and $campo != "edit_mode" and $campo != "sNumeroPoliza" and $campo != "iConsecutivo" and $campo != 'txtPolicyJacker' and $campo != 'iConsecutivoArchivo' and $campo != 'txtPolicyPFA' and $campo != 'iConsecutivoArchivoPFA' ){ //Estos campos no se insertan a la tabla
                    if(($campo == "iConsecutivoAseguranza" || $campo == "iConsecutivoInsurancePremiumFinancing" || $campo == "iConsecutivoBrokers") && $valor == ""){
                        $valor = "NULL";
                    }else{
                        $valor = "'".trim($valor)."'";
                    }
                    array_push($valores,"$campo=$valor");
                }
             } 
 
          } */
      }
      
      if($error == '0'){
          if($_POST["edit_mode"] == 'true'){
              foreach($_POST as $campo => $valor){
                if($campo != "accion" and $campo != "edit_mode" and $campo != "sNumeroPoliza" and $campo != "iConsecutivo" and $campo != 'txtPolicyJacker' and $campo != 'iConsecutivoArchivo' and $campo != 'txtPolicyPFA' and $campo != 'iConsecutivoArchivoPFA' ){ //Estos campos no se insertan a la tabla
                    if(($campo == "iConsecutivoAseguranza" || $campo == "iConsecutivoInsurancePremiumFinancing" || $campo == "iConsecutivoBrokers") && $valor == ""){
                        $valor = "NULL";
                    }else{
                        $valor = "'".trim($valor)."'";
                    }
                    array_push($valores,"$campo=$valor");
                }
             } 
          }else if($_POST["edit_mode"] != 'true'){
             foreach($_POST as $campo => $valor){
                if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivo" and $campo != 'txtPolicyJacker' and $campo != 'iConsecutivoArchivo' and $campo != 'txtPolicyPFA' and $campo != 'iConsecutivoArchivoPFA'){ //Estos campos no se insertan a la tabla
                       if($valor != ""){
                          array_push($campos ,$campo); 
                          array_push($valores, trim($valor)); 
                       }
                }
             }  
          }
          
      }
      
      if($error == '0'){
          if($_POST["edit_mode"] == 'true'){
            array_push($valores ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
            array_push($valores ,"sIP='".$_SERVER['REMOTE_ADDR']."'");
            array_push($valores ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'"); 
            $sql = "UPDATE ct_polizas SET ".implode(",",$valores)." WHERE iConsecutivo ='".$_POST['iConsecutivo']."' AND sNumeroPoliza = '".$_POST['sNumeroPoliza']."' AND iConsecutivoCompania ='".$_POST['iConsecutivoCompania']."'";
            $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Data have been updated successfully.</p>'; 
          }else{
            array_push($campos ,"dFechaIngreso");
            array_push($valores ,date("Y-m-d H:i:s"));
            array_push($campos ,"sIP");
            array_push($valores ,$_SERVER['REMOTE_ADDR']);
            array_push($campos ,"sUsuarioIngreso");
            array_push($valores ,$_SESSION['usuario_actual']);
            $sql = "INSERT INTO ct_polizas (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')";
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
  function delete_policy(){
      $error = '0';  
      $msj = "";  
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      $query = "UPDATE ct_polizas SET iDeleted = '1' WHERE iConsecutivo = '".$_POST["clave"]."'"; 
      $conexion->query($query);
      $conexion->affected_rows < 1 ? $transaccion_exitosa = false : $transaccion_exitosa = true;
      if($transaccion_exitosa){
        $conexion->commit();
        $conexion->close();
        $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>
                The user has been deleted succesfully!</p>';
      }else{
        $conexion->rollback();
        $conexion->close();
        $msj = "A general system error ocurred : internal error";
        $error = "1";
      }
        
      $response = array("msj"=>"$msj","error"=>"$error");   
      echo json_encode($response);
  }
  
  #File:
  function upload_policy(){
      
      $error = "0";
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      $idCompany = $_POST['iConsecutivoCompania'];
      $_POST['sNombreCompania'] != '' ? $nameCompany = str_replace(' ','',$_POST['sNombreCompania']).'_' : $nameCompany =  '';
      $idFile = $_POST['iConsecutivo'];  
      
      $oFichero = fopen($_FILES['userfile']["tmp_name"], 'r'); 
      $sContenido = fread($oFichero, filesize($_FILES['userfile']["tmp_name"]));  
      $sContenido =  $conexion->real_escape_string($sContenido);
      $typeFile = $_POST['eArchivo'];
      
       
      //Revisamos el tamaño del archivo:
      //if($_FILES['userfile']["size"] <= 1000000){
          
          $FileExt = explode('.',$_FILES['userfile']["name"]); 
          $countArray = count($FileExt);
          if($countArray == 2){   
              $name_file = strtolower($nameCompany).$typeFile.'.'.$FileExt[1]; 
              
              if($_POST['iConsecutivo'] != ''){
                 $sql = "UPDATE cb_company_files SET iConsecutivoCompania='$idCompany', sNombreArchivo ='$name_file', sTipoArchivo ='".$_FILES['userfile']["type"]."', iTamanioArchivo ='".$_FILES['userfile']["size"]."', hContenidoDocumentoDigitalizado='$sContenido', ".
                        "dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."', sUsuarioActualizacion='".$_SESSION['usuario_actual']."'".
                        "WHERE iConsecutivo ='".$_POST['iConsecutivo']."'"; 
              }else{
                 $sql = "INSERT INTO cb_company_files (iConsecutivoCompania, sNombreArchivo, sTipoArchivo, iTamanioArchivo, hContenidoDocumentoDigitalizado, dFechaIngreso, sIP, sUsuarioIngreso) ".
                        "VALUES('$idCompany','$name_file','".$_FILES['userfile']["type"]."','".$_FILES['userfile']["size"]."','$sContenido','".date("Y-m-d H:i:s")."', '".$_SERVER['REMOTE_ADDR']."', '".$_SESSION['usuario_actual']."')"; 
              }
              if($conexion->query($sql)){
                    
                    if($_POST['iConsecutivo'] != ''){
                       //Si fue un UPDATE, ENTONCES: 
                       $id_file = $_POST['iConsecutivo'];
                       $conexion->commit();
                       $conexion->close();
                       $mensaje = "The file was updated successfully."; 
                       
                    }else{
                       
                        //Si fue un INSERT, ENTONCES:
                        $id_file = $conexion->insert_id;  
                        //Actualizamos la poliza:
                        $typeFile == 'pfa' ? $iArchivo = 'iConsecutivoArchivoPFA' : $iArchivo = 'iConsecutivoArchivo';
                        
                        if($id_file != '' && $_POST['iConsecutivoPoliza'] != '' && $_POST['iConsecutivoCompania'] != ''){
                            $sql_poliza = "UPDATE ct_polizas SET $iArchivo = '$id_file' ".
                                          "WHERE iConsecutivo = '".$_POST['iConsecutivoPoliza']."' AND iConsecutivoCompania = '".$_POST['iConsecutivoCompania']."'";
                          if($conexion->query($sql_poliza)){
                              $conexion->commit();
                              $conexion->close();
                              $mensaje = "The file was uploaded successfully.";
                          }else{
                              $mensaje = "Error: The file was not uploaded successfully";
                              $error = "1"; 
                          }  
                        }else{
                            $mensaje = "Error: The file was not uploaded successfully";
                            $error = "1";
                        } 
                    }
                   
              }else{
                    $mensaje = "A general system error ocurred : internal error";
                    $error = "1";    
              }
          }else{
              $mensaje = "Error: Please verify that the file name does not contain any special characters..";
              $error = "1";
          }
          
          
          if($error == '1'){
              $conexion->rollback();
              $conexion->close();
          }    
          
      /*}else{
         $mensaje = "Error: The file you are trying to upload exceeds the maximum size (1MB) allowed by the system, please check it and try again.";
         $error = "1"; 
      }  */
      
      $response = array("mensaje"=>"$mensaje","error"=>"$error", "id_file"=>"$id_file", "name_file" => "$name_file"); 
      echo json_encode($response);             
  }
  
  /*-------FILE DRIVERS UNITS UPLOAD FUNCTIONS -----------*/
  function get_company_policies(){
      include("cn_usuarios.php");   
      $company = $_POST['company'];
      $error = "0";
      $mensaje = "";
      $check_items = "";
      
      $query = "SELECT A.iConsecutivo, sNumeroPoliza, sName, sDescripcion ".
               "FROM ct_polizas A ".
               "LEFT JOIN ct_brokers C ON A.iConsecutivoBrokers = C.iConsecutivo ".
               "LEFT JOIN ct_tipo_poliza D ON A.iTipoPoliza = D.iConsecutivo ".
               "WHERE A.iConsecutivoCompania = '$company' AND A.iDeleted = '0' AND dFechaCaducidad >= CURDATE()";
      $result = $conexion->query($query);
      $rows = $result->num_rows;   

      if($rows > 0){    
        while ($items = $result->fetch_assoc()){
           $check_items .= "<input id=\"iPoliza_".$items['iConsecutivo']."\" class=\"num_policies\" type=\"checkbox\" value=\"".$items['iConsecutivo']."\" ><label class=\"check-label\"> ".$items['sNumeroPoliza']."/".$items['sDescripcion']."/".$items['sName']."</label><br>"; 
        }
      }else{
          $check_items .= "<p style=\"text-align:center;\">Sorry, this company has not policies in progress.</p>";
      }

      $response = array("checkboxes"=>"$check_items","mensaje"=>"$mensaje","error"=>"$error");   
      echo json_encode($response);
      
  }
  function upload_list_file(){
      require('./lib/excel/reader.php');
      
      
      #variables:
      $iConsecutivoCompania = $_POST['iConsecutivoCompania'];
      $iConsecutivoPolizas  = $_POST['iConsecutivoPolizas'];
      $iConsecutivoPolizas  = explode(',',$iConsecutivoPolizas);
      $error = 0;
      $mensaje = "";
      $htmlTabla = ""; 
      //Archivo Data:
      $fileName = $_FILES['userfile']['name'];
      $tmpName  = $_FILES['userfile']['tmp_name'];
      
      if($iConsecutivoCompania != '' && $iConsecutivoPolizas != ''){
          #creamos instancia para la clase Excel Reader: 
          $data = new Spreadsheet_Excel_Reader();
          $data->setOutputEncoding('UTF-8');
          $url =  $tmpName.'.xls'; 
          //Crear archivo fisico en el Temporal:
          $fp = fopen($tmpName, 'r'); 
          $content = fread($fp, filesize($tmpName));  
          $fp = fopen($url,"w") or die("Error al momento de crear el archivo. Favor de verificarlo.");
          fwrite($fp,$content); 
          fclose($fp);
          $data->read($url);
          error_reporting(E_ALL ^ E_NOTICE);
          
          foreach($data->sheets as $x => $y){
                 
               //$rows = $y['numRows'];
               $rows = count($y['cells']); 
               $col  = $y['numCols']; 
 
               if($data->boundsheets[$x]['name'] == 'UNITS' && $error == '0'){ 
                   //Open Connection.
                   include("cn_usuarios.php");  
                   $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
                   $transaccion_exitosa = true;
                   
                   for($i = 2; $i <= $rows; $i++){
                      //Arrays:
                      $campos_unit       = array(); //<--- insert
                      $valores_unit      = array(); //<--- insert
                      $update_unit_array = array(); //<--- update
                      
                      $success_unit = 0;
                      $existe = '0';             
                      for($z = 1; $z <= $col; $z++){
                              if($y['cells'][1][$z] == 'VIN'){
                                  
                                  $sVIN = trim($y['cells'][$i][$z]); 
                                  $sVIN = str_replace(' ','',nl2br($sVIN));
                                  if(strpos($sVIN,'<br/>')){$sVIN =  str_replace('<br/>','',nl2br($sVIN));}
                              
                                  if($sVIN != ""){
                                      
                                          $sVIN = strtoupper(trim($sVIN));
                                          $sVIN = ereg_replace("[^A-Za-z0-9]", "", $sVIN);
                                          
                                          if(strlen($sVIN) > 18){
                                             $error = '1';
                                             $mensaje = "Error: Please verify the VIN on the column $z row $i from XLS file.";   
                                          }else{  
                                              //Revisamos si ya existe la unidad para esta compañia:
                                              $query = "SELECT COUNT(iConsecutivo) AS total FROM ct_unidades WHERE sVIN = '$sVIN' AND iConsecutivoCompania = '$iConsecutivoCompania'";
                                              $result = $conexion->query($query);
                                              $items = $result->fetch_assoc();
                                              $existe = $items["total"];
                                              if($existe == "0"){
                                                  array_push($campos_unit ,"sVIN"); 
                                                  array_push($valores_unit,$sVIN); 
                                              } 
                                          } 
                                
                                  }else{
                                      $error = '1';
                                      $mensaje = "Error: Please verify the VIN \"$sVIN\" on the row $i from XLS file.";
                                      $transaccion_exitosa = false;
                                      
                                  }
                              }
                              else if($y['cells'][1][$z] == 'MAKE'){
                                  $iMake = strtoupper(trim($y['cells'][$i][$z]));
                                  //Revisamos si existe el modelo en el catalogo:
                                  $query = "SELECT iConsecutivo AS Make FROM ct_unidad_modelo WHERE sDescripcion = '$iMake' OR sAlias = '$iMake'";
                                  $result = $conexion->query($query);
                                  $items = $result->fetch_assoc();
                                  $iMake = $items["Make"];
                                  if($iMake != ""){
                                     //UPDATE 
                                     array_push($update_unit_array,"iModelo='$iMake'");
                                     //INSERT 
                                     array_push($campos_unit ,"iModelo");
                                     array_push($valores_unit,$iMake); 
                                  }
                                  
                              }
                              else if($y['cells'][1][$z] == 'YEAR'){
                                  $iYear = trim($y['cells'][$i][$z]);
                                  if($iYear != ""){
                                     //UPDATE  
                                     array_push($update_unit_array,"iYear='$iYear'");
                                     //INSERT  
                                     array_push($campos_unit ,"iYear");
                                     array_push($valores_unit,$iYear);  
                                  }
                              }
                              else if($y['cells'][1][$z] == 'RADIUS'){
                                  $iRadius = trim($y['cells'][$i][$z]);
                                  $iRadius = str_replace(' ','',$iRadius);
                                  //Revisamos si existe el Radius en el catalogo: 
                                  $query = "SELECT iConsecutivo AS Radius FROM ct_unidad_radio WHERE sDescripcion = '$iRadius'";
                                  $result = $conexion->query($query);
                                  $items = $result->fetch_assoc();
                                  $iRadius = $items["Radius"];
                                  if($iRadius != ""){
                                      //UPDATE 
                                      array_push($update_unit_array,"iConsecutivoRadio='$iRadius'");
                                      //INSERT
                                      array_push($campos_unit ,"iConsecutivoRadio");
                                      array_push($valores_unit,$iRadius); 
                                  }
                              }
                              else if($y['cells'][1][$z] == 'TYPE'){
                                  $sTypeU = trim($y['cells'][$i][$z]); 
                                  if($sTypeU == 'UNIT' || $sTypeU == 'TRAILER'){
                                      //UPDATE 
                                      array_push($update_unit_array,"sTipo='$sTypeU'");
                                      //INSERT 
                                      array_push($campos_unit ,"sTipo");
                                      array_push($valores_unit,$sTypeU);
                                  }
                              }
                              else if($y['cells'][1][$z] == 'TOTALPREMIUM' || $y['cells'][1][$z] == 'PD'){
                                  
                                  $totalP = trim($y['cells'][$i][$z]);
                                  $totalP = str_replace(" ","",$totalP);
                                  $totalP = str_replace(",","",$totalP);
                                  $totalP = str_replace("\$","",$totalP);
                                  $totalP = preg_replace('/[^0-9]+/', '', $totalP);
                                  $totalP = intval(trim($totalP)); 
                                  if($totalP != ''){
                                      //UPDATE 
                                      array_push($update_unit_array,"iTotalPremiumPD='$totalP'");
                                      //INSERT 
                                      array_push($campos_unit ,"iTotalPremiumPD");
                                      array_push($valores_unit,$totalP); 
                                  }
                              }
                      }
                      if($error != '0'){$transaccion_exitosa = false;}
                      else{
                        if($sVIN != ""){  
                            if($existe != "0"){
                                #UPDATE INFORMATION:
                                //Agregando campos adicionales:
                                array_push($update_unit_array ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
                                array_push($update_unit_array ,"sIP='".$_SERVER['REMOTE_ADDR']."'");
                                array_push($update_unit_array ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'");
                                //array_push($update_unit_array ,"siConsecutivosPolizas='$iConsecutivoPolizas'");
                                array_push($update_unit_array ,"iDeleted='0'"); 
                                array_push($update_unit_array ,"eModoIngreso='EXCEL'");
                                $query = "UPDATE ct_unidades SET ".implode(",",$update_unit_array)." WHERE sVIN = '$sVIN' AND iConsecutivoCompania ='".trim($iConsecutivoCompania)."'";
                                if(!($conexion->query($query))){ $transaccion_exitosa = false;$msj = "The data of unit was not saved properly, please try again.";}
                                else{
                                    #ACTUALIZAR TABLA DE POLIZAS/UNIDADES:
                                    $query = "SELECT iConsecutivo FROM ct_unidades WHERE sVIN = '$sVIN' AND iConsecutivoCompania ='".trim($iConsecutivoCompania)."'";
                                    $result= $conexion->query($query);
                                    $row   = $result->fetch_assoc();
                                    
                                    $count = count($iConsecutivoPolizas);
                                    for($p=0;$p<$count;$p++){
                                        $query = "SELECT COUNT(iConsecutivoUnidad) AS total FROM cb_poliza_unidad WHERE iConsecutivoPoliza='".$iConsecutivoPolizas[$p]."' AND iConsecutivoUnidad='".$row['iConsecutivo']."'";
                                        $result= $conexion->query($query);
                                        $valid = $result->fetch_assoc();
                                        
                                        if($valid['total'] == 0){
                                            $query = "INSERT INTO cb_poliza_unidad (iConsecutivoPoliza,iConsecutivoUnidad) VALUES('".$iConsecutivoPolizas[$p]."','".$row['iConsecutivo']."')";
                                            if(!($conexion->query($query))){ $transaccion_exitosa = false;$msj = "The data of unit in the policy was not saved properly, please try again.";}
                                            else{$success_unit ++;}
                                        }
                                    }
               
                                } 
                                
                            }
                            else{
                                #INSERT INFORMATION:
                                //Agregando campos adicionales:
                                array_push($campos_unit ,"iConsecutivoCompania"); array_push($valores_unit,trim($iConsecutivoCompania)); //<-- Compania
                                array_push($campos_unit ,"dFechaIngreso"); array_push($valores_unit,date("Y-m-d H:i:s"));
                                array_push($campos_unit ,"sIP"); array_push($valores_unit,$_SERVER['REMOTE_ADDR']);
                                array_push($campos_unit ,"sUsuarioIngreso"); array_push($valores_unit,$_SESSION['usuario_actual']);
                                array_push($campos_unit ,"eModoIngreso"); array_push($valores_unit,'EXCEL');
                                
                                $query = "INSERT INTO ct_unidades (".implode(",",$campos_unit).") VALUES ('".implode("','",$valores_unit)."')";
                                $conexion->query($query);
                                
                                if($conexion->affected_rows < 1){ $transaccion_exitosa = false;$msj = "The data of unit was not saved properly, please try again.";}
                                else{
                                    #ACTUALIZAR TABLA DE POLIZAS/UNIDADES:
                                    $iConsecutivoUnidad = $conexion->insert_id;
                                    $count              = count($iConsecutivoPolizas);
                                    
                                    for($p=0;$p<$count;$p++){
                                        $query = "INSERT INTO cb_poliza_unidad (iConsecutivoPoliza,iConsecutivoUnidad) VALUES('".$iConsecutivoPolizas[$p]."','$iConsecutivoUnidad')";
                                        if(!($conexion->query($query))){ $transaccion_exitosa = false;$msj = "The data of unit was not saved properly, please try again.";}
                                        else{$success_unit ++;}
                                        
                                    }
                                }
                            }
                        } 
                      }
                      
                   }
 
                   if($transaccion_exitosa){
                        $conexion->commit();
                        $conexion->close();
                        $mensaje .= "The data of units has been uploaded successfully, please verify the data in the company policies.<br><br>";
                   }
                   else{
                        $conexion->rollback();
                        $conexion->close();
                        $mensaje .= 'Error to upload the units information.<br>';
                   }
               }
               
               if($data->boundsheets[$x]['name'] == 'DRIVERS' && $error == '0'){
                    //Open Connection.
                    include("cn_usuarios.php");  
                    $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
                    $transaccion_exitosa = true;
                    for($i = 2; $i <= $rows; $i++){ 
                        //Arrays:
                        $campos_driv         = array(); //insert
                        $valores_driv        = array(); //insert
                        $update_driver_array = array(); // update
                        
                        $success_driv   = 0;
                        $existe = '0';
                        for($z = 1; $z <= $col; $z++){
                               if($y['cells'][1][$z] == 'LICENSE'){
                                  
                                  $iLicense = strtoupper(trim($y['cells'][$i][$z]));
                                  $iLicense = str_replace(' ','',nl2br($iLicense));
                                  $iLicense = trim($iLicense);

                                  if($iLicense != ""){
                                        if(strpos($iLicense,'<br/>')){
                                              $error = '1';
                                              $mensaje = "Error: Please verify the LICENSE on the column $z row $i from XLS file."; 
                                        }
                                        else{ 
                                          $pos = strpos($iLicense,'-');
                                          if (!($pos === false)) {
                                              $iLicense = substr($iLicense,0,$pos);
                                          }  
                                          //Revisamos si ya existe el driver para esta compañia:
                                          $query  = "SELECT COUNT(iConsecutivo) AS total FROM ct_operadores WHERE iNumLicencia = '".trim($iLicense)."' AND iConsecutivoCompania = '$iConsecutivoCompania'";
                                          $result = $conexion->query($query);
                                          $items  = $result->fetch_assoc();
                                          $existe = $items["total"];
                                          if($existe == "0"){
                                              array_push($campos_driv,"iNumLicencia"); 
                                              array_push($valores_driv,trim($iLicense)); 
                                          }
                                        }       
                                  } 
                               }
                               else if($y['cells'][1][$z] == 'NAME'){
                                   $sName = strtoupper(trim($y['cells'][$i][$z]));
                                   if($sName != ""){
                                       $sName = str_replace(",","",$sName);
                                       //UPDATE 
                                       array_push($update_driver_array,"sNombre='".trim($sName)."'"); 
                                       //INSERT 
                                       array_push($campos_driv ,"sNombre");
                                       array_push($valores_driv,trim($sName)); 
                                   } 
                                    
                               }
                               else if($y['cells'][1][$z] == 'DOB'){
                                   $DOB = trim($y['cells'][$i][$z]); 
                                   if($DOB != ""){
                                       $unixDate  = ($DOB - 25569) * 86400;
                                       $ExcelDate = 25569 + ($unixDate / 86400);
                                       $unixDate  = ($ExcelDate - 25569) * 86400;
                                       $DOB = gmdate("Y-m-d", $unixDate); 
                                        
                                       //UPDATE 
                                       array_push($update_driver_array,"dFechaNacimiento='$DOB'"); 
                                       //INSERT 
                                       array_push($campos_driv ,"dFechaNacimiento");
                                       array_push($valores_driv,$DOB);
                                   } 
                                    
                               }
                               else if($y['cells'][1][$z] == 'YOE'){
                                   $YOE = trim($y['cells'][$i][$z]); 
                                   if($YOE != ""){
                                       $YOE = str_replace("+",'',$YOE);
                                       //UPDATE 
                                       array_push($update_driver_array,"iExperienciaYear='$YOE'"); 
                                       //INSERT 
                                       array_push($campos_driv ,"iExperienciaYear");
                                       array_push($valores_driv,$YOE);
                                   } 
                                    
                               }
                               else if($y['cells'][1][$z] == 'EXPIREDATE'){
                                   $ExpireDate = trim($y['cells'][$i][$z]); 
                                   if($ExpireDate != ""){
                                       $UNIX_DATE = ($ExpireDate - 25569) * 86400;
                                       $EXCEL_DATE = 25569 + ($UNIX_DATE / 86400);
                                       $UNIX_DATE = ($EXCEL_DATE - 25569) * 86400;
                                       $ExpireDate = gmdate("Y-m-d", $UNIX_DATE);
                                       //UPDATE 
                                       array_push($update_driver_array,"dFechaExpiracionLicencia='$ExpireDate'"); 
                                       //INSERT 
                                       array_push($campos_driv ,"dFechaExpiracionLicencia");
                                       array_push($valores_driv,$ExpireDate); 
                                   }

                               }
                               else if($y['cells'][1][$z] == 'TYPE'){
                                   $Type = strtoupper(trim($y['cells'][$i][$z])); 
                                   if($Type != ""){
                                       //UPDATE 
                                       array_push($update_driver_array,"eTipoLicencia='$Type'"); 
                                       //INSERT 
                                       array_push($campos_driv ,"eTipoLicencia");
                                       array_push($valores_driv,$Type); 
                                   } 
                                    
                               }  
                        }
   
                        #INSERT: 
                        if($error != '0'){$transaccion_exitosa = false;}
                        else{
                          if(count($update_driver_array) != "0" && count($campos_driv) != "0"){  
                              if($iLicense != ""){  
                                if($existe != "0"){
                                    #UPDATE:
                                    //Agregando campos adicionales:
                                    array_push($update_driver_array ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
                                    array_push($update_driver_array ,"sIP='".$_SERVER['REMOTE_ADDR']."'");
                                    array_push($update_driver_array ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'");
                                    array_push($update_driver_array ,"iDeleted='0'");
                                    array_push($update_driver_array ,"eModoIngreso='EXCEL'");
                                    $query = "UPDATE ct_operadores SET ".implode(",",$update_driver_array)." WHERE iNumLicencia = '$iLicense' AND iConsecutivoCompania = '$iConsecutivoCompania' ";
                                    
                                    if(!($conexion->query($query))){ $transaccion_exitosa = false;$msj = "The data of driver was not saved properly, please try again.";}
                                    else{
                                        #ACTUALIZAR TABLA DE POLIZAS/UNIDADES:
                                        $query = "SELECT iConsecutivo FROM ct_operadores WHERE iNumLicencia='$iLicense' AND iConsecutivoCompania='$iConsecutivoCompania'";
                                        $result= $conexion->query($query);
                                        $row   = $result->fetch_assoc();
                                        
                                        $count = count($iConsecutivoPolizas);
                                        for($p=0;$p<$count;$p++){
                                            $query = "SELECT COUNT(iConsecutivoOperador) AS total FROM cb_poliza_operador WHERE iConsecutivoPoliza='".$iConsecutivoPolizas[$p]."' AND iConsecutivoOperador='".$row['iConsecutivo']."'";
                                            $result= $conexion->query($query);
                                            $valid = $result->fetch_assoc();
                                            
                                            if($valid['total'] == 0){
                                                $query = "INSERT INTO cb_poliza_operador (iConsecutivoPoliza,iConsecutivoOperador) VALUES('".$iConsecutivoPolizas[$p]."','".$row['iConsecutivo']."')";
                                                if(!($conexion->query($query))){ $transaccion_exitosa = false;$msj = "The data of driver in the policy was not saved properly, please try again.";}
                                                else{$success_driv ++;}
                                            }
                                        }
                                    }
                                    
                                }
                                else{
                                    #INSERT:
                                    //Agregando campos adicionales:
                                    array_push($campos_driv ,"iConsecutivoCompania"); array_push($valores_driv,trim($iConsecutivoCompania)); //<-- Compania
                                    array_push($campos_driv ,"dFechaIngreso"); array_push($valores_driv,date("Y-m-d H:i:s"));
                                    array_push($campos_driv ,"sIP"); array_push($valores_driv,$_SERVER['REMOTE_ADDR']);
                                    array_push($campos_driv ,"sUsuarioIngreso"); array_push($valores_driv,$_SESSION['usuario_actual']);
                                    array_push($campos_driv ,"eModoIngreso"); array_push($valores_driv,'EXCEL');
                                    
                                    $query = "INSERT INTO ct_operadores (".implode(",",$campos_driv).") VALUES ('".implode("','",$valores_driv)."')";
                                    $conexion->query($query);
                                    
                                    if($conexion->affected_rows < 1){ $transaccion_exitosa = false;$msj = "The data of driver was not saved properly, please try again.";}
                                    else{
                                        #ACTUALIZAR TABLA DE POLIZAS/UNIDADES:
                                        $iConsecutivoOperador = $conexion->insert_id;
                                        $count                = count($iConsecutivoPolizas);
                                        
                                        for($p=0;$p<$count;$p++){
                                            $query = "INSERT INTO cb_poliza_operador (iConsecutivoPoliza,iConsecutivoOperador) VALUES('".$iConsecutivoPolizas[$p]."','$iConsecutivoOperador')";
                                            if(!($conexion->query($query))){ $transaccion_exitosa = false;$msj = "The data of driver in policy was not saved properly, please try again.";}
                                            else{$success_driv ++;}
                                            
                                        }
                                    }  
                                }
                              }else{
                                  $error = '1';
                                  $mensaje = "Error: Please verify the LICENSE# on the row $i from XLS file.";
                                  $transaccion_exitosa = false;
                              }
                          }
                        }
                    }  
                    if($transaccion_exitosa){
                        $conexion->commit();
                        $conexion->close();
                        $mensaje .= "The data of drivers has been uploaded successfully, please verify the data in the company policies.";
                    }else{
                        $conexion->rollback();
                        $conexion->close();
                        $mensaje .= 'Error to upload the drivers information.<br>'; 
                    }

               }       
               
               if($data->boundsheets[$x]['name'] != 'UNITS' && $data->boundsheets[$x]['name'] != 'DRIVERS'){
                   $error = '1';
                   $mensaje = "Error: Please upload the file with the layout format and try again.";
               } 
               
          }
      }
      
      $htmlTabla = $htmlTabla.$tabla_error;
      $response = array("mensaje"=>"$mensaje","error"=>"$error", "name_file" => "$fileName"); 
      echo json_encode($response); 
      
  }
  function upload_unit_txt(){
      
      include("cn_usuarios.php");  
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      #variables:
      $iConsecutivoCompania = $_POST['iConsecutivoCompania'];
      $iConsecutivoPolizas  = $_POST['iConsecutivoPolizas'];
      $error = "0";
      $mensaje = "";
      $htmlTabla = ""; 
      $fileName = $_FILES['userfile']['name'];
      $tmpName  = $_FILES['userfile']['tmp_name'];
      
      if($iConsecutivoCompania != '' && $iConsecutivoPolizas != ''){
          #Revisamos contenido del archivo:
          $fp      = fopen($tmpName, 'r'); 
          $content = fread($fp, filesize($tmpName));
          $content = addslashes($content);
          fclose($fp);
          $array_contenido = explode("\r\n",$content);
          foreach($array_contenido as $key => $valor){
             
              $valores = array();
              $campos  = array();
               
            if($transaccion_exitosa){    
              if($array_contenido[$key] != ""){ 
                  $array_unit = explode("|",$array_contenido[$key]);
                  $sVIN = strtoupper(trim($array_unit[0]));
                  $iYear = $array_unit[1];
                  $iModelo = $array_unit[2];
                  $iConsecutivoRadio = $array_unit[3];
                  
                  //Header table:
                  $htmlTabla = '&lt;tr style="background: rgb(55, 101, 176);color:#ffffff!important;">'.
                               '&lt;td&gt; VIN</td>'.
                               '&lt;td&gt; RADIO</td>'.
                               '&lt;td&gt; YEAR</td>'.
                               '&lt;td&gt; MAKE</td>'.
                               '&lt;td&gt; TYPE</td>'.
                               '&lt;td&gt; WEIGHT</td>';
                  
                  #Verificamos si el driver no existe:
                  $sql = "SELECT COUNT(iConsecutivo) AS total FROM ct_unidades WHERE sVIN = '$sVIN' AND iConsecutivoCompania = '".trim($iConsecutivoCompania)."'";
                  $result = $conexion->query($sql);
                  $items = $result->fetch_assoc();
                  $existe = $items["total"];
                  if($existe == "0"){
                      #armando Array:
                      array_push($campos ,"iConsecutivoCompania"); array_push($valores,trim($iConsecutivoCompania));
                      if($sVIN != ''){array_push($campos ,"sVIN"); array_push($valores,trim($sVIN));}
                      if($iYear != ''){array_push($campos ,"iYear"); array_push($valores,trim($iYear));}
                      if($iModelo != ''){array_push($campos ,"iModelo"); array_push($valores,trim($iModelo));}
                      if($iConsecutivoRadio != ''){array_push($campos ,"iConsecutivoRadio"); array_push($valores,trim($iConsecutivoRadio));}
                      array_push($campos ,"dFechaIngreso"); array_push($valores,date("Y-m-d H:i:s"));
                      array_push($campos ,"sIP"); array_push($valores,$_SERVER['REMOTE_ADDR']);
                      array_push($campos ,"sUsuarioIngreso"); array_push($valores,$_SESSION['usuario_actual']);
                      array_push($campos ,"siConsecutivosPolizas"); array_push($valores,trim($iConsecutivoPolizas)); 
                      array_push($campos ,"inPoliza"); array_push($valores,'1'); 
                      
                      $insert = "INSERT INTO ct_unidades (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')";
                      $conexion->query($insert);
                      if($conexion->affected_rows < 1){ 
                         $transaccion_exitosa = false;
                         $msj = "The data of unit was not saved properly, please try again.";               
                      }else{
                        $htmlTabla .= '&lt;tr style="background: rgb(234, 255, 226);">'.
                                      '&lt;td&gt;'.$sVIN.'</td>'.
                                      '&lt;td&gt;'.$iYear.'</td>'.
                                      '&lt;td&gt;'.$iModelo.'</td>'.
                                      '&lt;td&gt;'.$iConsecutivoRadio.'</td>';
                      }
                      
                  }else{
                     #YA EXISTE: 
                     
                     //if($sVIN != ''){array_push($valores,"sVIN='".trim($sVIN)."'");}
                     if($iYear != ''){array_push($valores,"iYear='".trim($iYear)."'");}
                     if($iModelo != ''){array_push($valores,"iModelo='".trim($iModelo)."'"); }
                     if($iConsecutivoRadio != ''){array_push($valores,"iConsecutivoRadio='".trim($iConsecutivoRadio)."'"); }
                     array_push($valores,"sIPActualizacion='".$_SERVER['REMOTE_ADDR']."'");
                     array_push($valores,"dFechaActualizacion='".date_to_server(date("Y-m-d H:i:s"))."'");
                     array_push($valores,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'");
                     array_push($valores,"iConsecutivosPolizas='".trim($iConsecutivoPolizas)."'");
                     array_push($valores,"inPoliza='1'");
                     
                      $update = "UPDATE ct_unidades SET ".implode(",",$valores)." WHERE sVIN = '$sVIN' AND iConsecutivoCompania = '$iConsecutivoCompania'";
                      
                      if($conexion->query($update)){ 
                          $htmlTabla .= '&lt;tr style="background: rgb(254, 232, 154);">'.
                                        '&lt;td&gt;'.$sVIN.'</td>'.
                                        '&lt;td&gt;'.$iYear.'</td>'.
                                        '&lt;td&gt;'.$iModelo.'</td>'.
                                        '&lt;td&gt;'.$iConsecutivoRadio.'</td>';             
                      }else{
                          $transaccion_exitosa = false;
                          $msj = "The data of unit was not saved properly, please try again."; 
                      }
      
                  }
              } 
            }
          }
                
          
      }
      if($transaccion_exitosa){
            $conexion->commit();
            $conexion->close();
            $mensaje = "The drivers has been uploaded successfully, please verify the data in the company policies.";
      }else{
            $conexion->rollback();
            $conexion->close();
            $error = "1";
      }
      $htmlTabla = $htmlTabla.$tabla_error;
      //$htmlTabla = utf8_encode($htmlTabla.$tabla_error);
      $response = array("mensaje"=>"$mensaje","error"=>"$error", "name_file" => "$fileName","reporte"=>"$htmlTabla"); 
      echo json_encode($response); 
      
  }
  
  #DATA GRID DRIVERS/VEHICLES
  function get_drivers_active(){
      
      $iConsecutivoPoliza   = trim($_POST['iConsecutivoPoliza']);
      $iConsecutivoCompania = trim($_POST['iConsecutivoCompania']);
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
    
      $registros_por_pagina = $_POST["registros_por_pagina"];
      $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
      $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
        
      //Filtros de informacion //
      $filtroQuery = " WHERE  iConsecutivoCompania = '$iConsecutivoCompania' AND iDeleted='0' ";
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
    $query_rows = "SELECT COUNT(iConsecutivo) AS total FROM ct_operadores ".$filtroQuery;
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
        $sql    = "SELECT iConsecutivo, sNombre, DATE_FORMAT(dFechaNacimiento,'%m/%d/%Y') AS dFechaNacimiento, DATE_FORMAT(dFechaExpiracionLicencia,'%m/%d/%Y') AS dFechaExpiracionLicencia, iExperienciaYear, iNumLicencia, (CASE eTipoLicencia WHEN  'Federal/B1' THEN 'Federal / B1' WHEN  'Commercial/CDL-A' THEN 'Commercial / CDL - A' END) AS TipoLicencia,eModoIngreso ".
                  "FROM ct_operadores ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior; 
        $result = $conexion->query($sql);
        $rows   = $result->num_rows;   
        if ($rows > 0) {    
                while ($items = $result->fetch_assoc()){ 
                    
                    //Revisar polizas:
                    $query  = "SELECT iConsecutivoPoliza, B.sNumeroPoliza, C.sDescripcion AS sTipoPoliza, C.sAlias, DATE_FORMAT(A.dFechaIngreso,'%m/%d/%Y') AS dFechaIngreso, eModoIngreso ".
                               "FROM cb_poliza_operador AS A ".
                               "INNER JOIN ct_polizas   AS B ON A.iConsecutivoPoliza = B.iConsecutivo AND B.iDeleted = '0' AND B.dFechaCaducidad >= CURDATE() ".
                               "LEFT JOIN  ct_tipo_poliza AS C ON B.iTipoPoliza = C.iConsecutivo ".
                               "WHERE A.iConsecutivoOperador = '".$items['iConsecutivo']."' AND A.iDeleted='0' ";
                    $r      = $conexion->query($query);
                    $total  = $r->num_rows;
                    $polizas= "";
                    
                    if($total > 0){
                        $polizas  = '<table style="width: 100%;text-transform: uppercase;border-collapse: collapse;">';
                        //$classpan = "style=\"display:block;width:100%;padding:1px;\""; 
                        while ($poli = $r->fetch_assoc()){
                           
                            $polizas .= "<tr>";
                            $polizas .= "<td style=\"width:35%;\">".$poli['sNumeroPoliza']."</td>";
                            $polizas .= "<td style=\"width:15%;\">".$poli['sAlias']."</td>";
                            $polizas .= "<td style=\"width:50%;\">".$poli['eModoIngreso']." - ".$poli['dFechaIngreso']."</td>";
                            $polizas .= "</tr>";
                        }
                        $polizas .= "</table>"; 
                    }
                    
                     $htmlTabla .= "<tr>".
                                   "<td id=\"".$items['iConsecutivo']."\" >".$items['sNombre']."</td>".
                                   "<td class=\"txt-c\">".$items['dFechaNacimiento']."</td>".
                                   "<td>".$items['iNumLicencia']."</td>". 
                                   "<td>".$items['TipoLicencia']."</td>".
                                   "<td class=\"txt-c\">".$items['dFechaExpiracionLicencia']."</td>".  
                                   "<td class=\"txt-c\">".$items['iExperienciaYear']."</td>".
                                   "<td style=\"padding: 0px!important;\">$polizas</td>".                                                                                                                                                                                                                   
                                   "<td>".
                                   "<div class=\"btn_edit btn-icon edit btn-left\" title=\"Edit data\"><i class=\"fa fa-pencil-square-o\"></i></div>".
                                   "</td></tr>";   
                }
                $conexion->rollback();
                $conexion->close();                                                                                                                                                                       
        }else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>"   ;    }  
    }
     $htmlTabla = utf8_decode($htmlTabla);
     $response  = array("total"=>"$paginas_total","pagina"=>"$pagina_actual","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error");   
     echo json_encode($response);
      
  }
  /*
  function get_drivers_inactive(){
      
      $iConsecutivoPoliza = $_POST['iConsecutivoPoliza'];
      $iConsecutivoCompania = $_POST['iConsecutivoCompania'];
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
    
      $registros_por_pagina = $_POST["registros_por_pagina"];
      $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
      $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
        
      //Filtros de informacion //
      $filtroQuery = " WHERE iConsecutivoCompania = '$iConsecutivoCompania' AND siConsecutivosPolizas NOT LIKE '%$iConsecutivoPoliza%' OR siConsecutivosPolizas = '' ";
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
    $query_rows = "SELECT COUNT(iConsecutivo) AS total FROM ct_operadores ".$filtroQuery;
    $Result = $conexion->query($query_rows);
    $itemssNombre = $Result->fetch_assoc();
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
        $sql = "SELECT iConsecutivo, sNombre, DATE_FORMAT(dFechaNacimiento,'%m/%d/%Y') AS dFechaNacimiento, DATE_FORMAT(dFechaExpiracionLicencia,'%m/%d/%Y') AS dFechaExpiracionLicencia, iExperienciaYear, iNumLicencia, (CASE eTipoLicencia WHEN  1 THEN 'Federal / B1' WHEN  2 THEN 'Commercial / CDL - A' END) AS TipoLicencia ".
               "FROM ct_operadores ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior; 
        $result = $conexion->query($sql);
        $rows = $result->num_rows;   
        if ($rows > 0) {    
                while ($items = $result->fetch_assoc()){ 
                   if($items["sNombre"] != ""){
                                   
                         $htmlTabla .= "<tr id=\"id-".$items['iConsecutivo']."\" >".
                                           "<td>".$items['sNombre']."</td>".
                                           "<td>".$items['dFechaNacimiento']."</td>".
                                           "<td>".$items['iNumLicencia']."</td>". 
                                           "<td>".$items['TipoLicencia']."</td>".
                                           "<td>".$items['dFechaExpiracionLicencia']."</td>".  
                                           "<td>".$items['iExperienciaYear']."</td>".                                                                                                                                                                                                                    
                                           "<td>
                                                <div class=\"btn_edit btn-icon edit btn-left\" title=\"Edit data\"><i class=\"fa fa-pencil-square-o\"></i> <span></span></div>
                                                <div class=\"btn_delete btn-icon trash btn-left\" title=\"Delete Driver from list\"><i class=\"fa fa-trash\"></i> <span></span></div>
                                           </td></tr>";  
                     }else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";}    
                }
                $conexion->rollback();
                $conexion->close();                                                                                                                                                                       
        }else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>"   ;    }  
    }

     $response = array("total"=>"$paginas_total","pagina"=>"$pagina_actual","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
     echo json_encode($response);
      
  }
  function get_drivers_deleted(){
      
      $iConsecutivoPoliza = $_POST['iConsecutivoPoliza'];
      $iConsecutivoCompania = $_POST['iConsecutivoCompania'];
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
    
      $registros_por_pagina = $_POST["registros_por_pagina"];
      $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
      $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
        
      //Filtros de informacion //
      $filtroQuery = " WHERE iConsecutivoCompania = '$iConsecutivoCompania' AND siConsecutivosPolizas NOT LIKE '%$iConsecutivoPoliza%' OR siConsecutivosPolizas = '' ";
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
    $query_rows = "SELECT COUNT(iConsecutivo) AS total FROM ct_operadores ".$filtroQuery;
    $Result = $conexion->query($query_rows);
    $itemssNombre = $Result->fetch_assoc();
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
        $sql = "SELECT iConsecutivo, sNombre, DATE_FORMAT(dFechaNacimiento,'%m/%d/%Y') AS dFechaNacimiento, DATE_FORMAT(dFechaExpiracionLicencia,'%m/%d/%Y') AS dFechaExpiracionLicencia, iExperienciaYear, iNumLicencia, (CASE eTipoLicencia WHEN  1 THEN 'Federal / B1' WHEN  2 THEN 'Commercial / CDL - A' END) AS TipoLicencia ".
               "FROM ct_operadores ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior; 
        $result = $conexion->query($sql);
        $rows = $result->num_rows;   
        if ($rows > 0) {    
                while ($items = $result->fetch_assoc()){ 
                   if($items["sNombre"] != ""){
                                   
                         $htmlTabla .= "<tr id=\"id-".$items['iConsecutivo']."\" >".
                                           "<td>".$items['sNombre']."</td>".
                                           "<td>".$items['dFechaNacimiento']."</td>".
                                           "<td>".$items['iNumLicencia']."</td>". 
                                           "<td>".$items['TipoLicencia']."</td>".
                                           "<td>".$items['dFechaExpiracionLicencia']."</td>".  
                                           "<td>".$items['iExperienciaYear']."</td>".                                                                                                                                                                                                                    
                                           "<td>
                                                <div class=\"btn_edit btn-icon edit btn-left\" title=\"Edit data\"><i class=\"fa fa-pencil-square-o\"></i> <span></span></div>
                                                <div class=\"btn_delete btn-icon trash btn-left\" title=\"Delete Driver from list\"><i class=\"fa fa-trash\"></i> <span></span></div>
                                           </td></tr>";  
                     }else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";}    
                }
                $conexion->rollback();
                $conexion->close();                                                                                                                                                                       
        }else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>"   ;    }  
    }

     $response = array("total"=>"$paginas_total","pagina"=>"$pagina_actual","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
     echo json_encode($response);
      
  } */
  function save_driver(){
      include("funciones_genericas.php"); 
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      #VARIABLES:
      $error   = '0'; 
      $valores = array();
      $campos  = array(); 
      $msj     = ""; 
      $_POST['sNombre']          = strtoupper(trim($_POST['sNombre']));
      $_POST['dFechaNacimiento'] = format_date(trim($_POST['dFechaNacimiento'])); 
      $_POST['dFechaExpiracionLicencia'] != '' ? $_POST['dFechaExpiracionLicencia'] = format_date(trim($_POST['dFechaExpiracionLicencia'])) : $_POST['dFechaExpiracionLicencia'] = '';
      
      $query = "SELECT COUNT(iConsecutivo) AS total ".
               "FROM   ct_operadores ".
               "WHERE  iNumLicencia ='".$_POST['iNumLicencia']."' AND iConsecutivoCompania = '".$_POST['iConsecutivoCompania']."'";
      $result = $conexion->query($query);
      $valida = $result->fetch_assoc();
      
      if($valida['total'] != '0'){
          if($_POST["edit_mode"] != 'true'){
              $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>
                      Error: The Driver that you trying to add already exists in this list. Please you verify the data.</p>';
              $error = '1';
          }else{   
             foreach($_POST as $campo => $valor){
                if($campo != "accion" && $campo != "edit_mode" && $campo != "iConsecutivo" && $campo != "iNumLicencia" && $campo != "siConsecutivosPolizas"){ //Estos campos no se insertan a la tabla
                    if($valor != ""){array_push($valores,"$campo='".trim($valor)."'");}
                }
             }   
          }
      }else if($_POST["edit_mode"] != 'true'){
         foreach($_POST as $campo => $valor){
            if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivo" && $campo != "siConsecutivosPolizas"){ //Estos campos no se insertan a la tabla
                if($valor != ""){ 
                    array_push($campos ,$campo); 
                    array_push($valores, trim($valor)); 
                }
            }
         }  
      }
      
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

          $success = $conexion->query($sql);
          if(!($success)){$transaccion_exitosa = false;$msj = "A general system error ocurred : internal error";}
          
          if($transaccion_exitosa){
                $conexion->commit();
                $conexion->close();
          }else{
                $conexion->rollback();
                $conexion->close();
                $error = "1";
          } 
      }
      $response = array("error"=>"$error","msj"=>"$msj");
      echo json_encode($response);
  }
  function get_driver(){
      $error   = '0';
      $msj     = "";
      $fields  = "";
      $clave   = trim($_POST['clave']);
      $company = trim($_POST['company']);  
      $domroot = $_POST['domroot'];
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                 

      $sql    = "SELECT iConsecutivo,iConsecutivoCompania, sNombre, DATE_FORMAT(dFechaNacimiento,'%m/%d/%Y') AS dFechaNacimiento, DATE_FORMAT(dFechaExpiracionLicencia,'%m/%d/%Y') AS dFechaExpiracionLicencia, ".
                "iExperienciaYear, iNumLicencia, eTipoLicencia  FROM ct_operadores WHERE iConsecutivo = '$clave' AND iConsecutivoCompania = '$company'";  
      $result = $conexion->query($sql);
      $items  = $result->num_rows;   
      if ($items > 0) {     
        $data = $result->fetch_assoc();
        $llaves  = array_keys($data);
        $datos   = $data;
        foreach($datos as $i => $b){
            if($i != 'siConsecutivosPolizas'){
               $fields .= "\$('#$domroot :input[id=".$i."]').val('".$datos[$i]."');"; 
            }
             
        }  
        
        $query  = "SELECT iConsecutivoPoliza, B.sNumeroPoliza, C.sDescripcion AS sTipoPoliza, C.sAlias, DATE_FORMAT(A.dFechaIngreso,'%m/%d/%Y') AS dFechaIngreso, eModoIngreso ".
                   "FROM cb_poliza_operador AS A ".
                   "INNER JOIN ct_polizas   AS B ON A.iConsecutivoPoliza = B.iConsecutivo AND B.iDeleted = '0' AND B.dFechaCaducidad >= CURDATE() ".
                   "LEFT JOIN  ct_tipo_poliza AS C ON B.iTipoPoliza = C.iConsecutivo ".
                   "WHERE A.iConsecutivoOperador = '".$data['iConsecutivo']."' AND A.iDeleted='0' ";
        $r      = $conexion->query($query);
        $total  = $r->num_rows;
        
        if($total > 0){
            while ($poli = $r->fetch_assoc()){
               $fields .= "\$('#drivers_edit_form  :input[value=\"".$poli['iConsecutivoPoliza']."\"]').prop(\"checked\",\"true\");"; 
            }
        }
         
      }
      $conexion->rollback();
      $conexion->close(); 
      $response = array("msj"=>"$msj","error"=>"$error","fields"=>"$fields");   
      echo json_encode($response);
  }
  
  #units
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
                    
                    //Revisar polizas:
                    $query  = "SELECT iConsecutivoPoliza, B.sNumeroPoliza, C.sDescripcion AS sTipoPoliza, C.sAlias, DATE_FORMAT(A.dFechaIngreso,'%m/%d/%Y') AS dFechaIngreso,eModoIngreso ".
                               "FROM cb_poliza_unidad AS A ".
                               "INNER JOIN ct_polizas   AS B ON A.iConsecutivoPoliza = B.iConsecutivo AND B.iDeleted = '0' AND B.dFechaCaducidad >= CURDATE() ".
                               "LEFT JOIN  ct_tipo_poliza AS C ON B.iTipoPoliza = C.iConsecutivo ".
                               "WHERE A.iConsecutivoUnidad = '".$items['iConsecutivo']."' AND A.iDeleted = '0' ";
                    $r      = $conexion->query($query);
                    $total  = $r->num_rows;
                    $polizas= "";
                    $PDApply= false;  
                    if($total > 0){
                        $polizas  = '<table style="width: 100%;text-transform: uppercase;border-collapse: collapse;">';
                        //$classpan = "style=\"display:block;width:100%;padding:1px;\""; 
                        while ($poli = $r->fetch_assoc()){
                           
                            $polizas .= "<tr>";
                            $polizas .= "<td style=\"width:35%;\">".$poli['sNumeroPoliza']."</td>";
                            $polizas .= "<td style=\"width:15%;\">".$poli['sAlias']."</td>";
                            $polizas .= "<td style=\"width:50%;\">".$poli['eModoIngreso']." - ".$poli['dFechaIngreso']."</td>";
                            $polizas .= "</tr>";
                           if($poli['sAlias'] == "PD"){$PDApply = true;}
                        }
                        $polizas .= "</table>"; 
                    }
                    
                    $PDApply && $items['iTotalPremiumPD'] > 0 ? $value = "\$ ".number_format($items['iTotalPremiumPD'],2,'.',',') : $value = "";           
                    $htmlTabla .= "<tr>".
                                  "<td id=\"".$items['iConsecutivo']."\">".$items['sVIN']."</td>".
                                  "<td>".$items['Radio']."</td>".
                                  "<td class=\"txt-c\">".$items['iYear']."</td>". 
                                  "<td>".$items['Make']."</td>".
                                  "<td>".$items['sTipo']."</td>".  
                                  "<td class=\"txt-c\">".$items['sPeso']."</td>".
                                  "<td class=\"txt-r\">".$value."</td>".
                                  "<td style=\"padding: 0px!important;\">".$polizas."</td>".                                                                                                                                                                                                                    
                                  "<td>".
                                  "<div class=\"btn_edit btn-icon edit btn-left\" title=\"Edit data\"><i class=\"fa fa-pencil-square-o\"></i></div>".
                                  "</td></tr>";
                      
                }
                $conexion->rollback();
                $conexion->close();                                                                                                                                                                       
        }else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>"   ;    }  
    }

     $response = array("total"=>"$paginas_total","pagina"=>"$pagina_actual","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
     echo json_encode($response);
  }
  function save_unit(){
      include("funciones_genericas.php"); 
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      #VARIABLES:
      $error   = '0'; 
      $valores = array();
      $campos  = array(); 
      $msj     = ""; 
      $_POST['sVIN'] = strtoupper(trim($_POST['sVIN'])); 
      
      $query  = "SELECT COUNT(iConsecutivo) AS total FROM   ct_unidades WHERE  sVIN ='".$_POST['sVIN']."' AND iConsecutivoCompania = '".$_POST['iConsecutivoCompania']."'";
      $result = $conexion->query($query);
      $valida = $result->fetch_assoc();
      
      if($valida['total'] != '0'){
          if($_POST["edit_mode"] != 'true'){
              $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>
                      Error: The Unit/Trailer that you trying to add already exists in this list. Please you verify the data.</p>';
              $error = '1';
          }else{
             foreach($_POST as $campo => $valor){
                if($campo != "accion" and $campo != "edit_mode"  and $campo != "iConsecutivo" and $campo != "sVIN"){ //Estos campos no se insertan a la tabla
                    
                    if($campo == "siConsecutivosPolizas" || $valor != "" || $campo == "iTotalPremiumPD"){
                        array_push($valores,"$campo='".trim($valor)."'");
                    }
                }
             }   
          }
      }else if($_POST["edit_mode"] != 'true'){
         foreach($_POST as $campo => $valor){
            if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivo"){ //Estos campos no se insertan a la tabla
                if($campo == "siConsecutivosPolizas" || $valor != "" || $campo == "iTotalPremiumPD"){ 
                    array_push($campos ,$campo); 
                    array_push($valores, trim($valor));
                }
            }
         }  
      }
      ////////
      if($error == '0'){
          
          $_POST['siConsecutivosPolizas'] != "" ? $inPoliza = "1" : $inPoliza = "0"; 
          
          if($_POST["edit_mode"] == 'true'){
            array_push($valores ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
            array_push($valores ,"sIP='".$_SERVER['REMOTE_ADDR']."'");
            array_push($valores ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'"); 
            array_push($valores,"inPoliza = '$inPoliza'");
            $sql = "UPDATE ct_unidades SET ".implode(",",$valores)." WHERE iConsecutivo ='".$_POST['iConsecutivo']."' AND iConsecutivoCompania ='".$_POST['iConsecutivoCompania']."'";
            $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Data have been updated successfully.</p>'; 
          }else{
            array_push($campos ,"dFechaIngreso");
            array_push($valores ,date("Y-m-d H:i:s"));
            array_push($campos ,"sIP");
            array_push($valores ,$_SERVER['REMOTE_ADDR']);
            array_push($campos ,"sUsuarioIngreso");
            array_push($valores ,$_SESSION['usuario_actual']);
            array_push($campos ,"inPoliza");
            array_push($valores ,"'$inPoliza'");
            $sql = "INSERT INTO ct_unidades (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')";
            $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Data have been added successfully.</p>';
          }
          
          $success = $conexion->query($sql);
          if(!($success)){$transaccion_exitosa = false;$msj = "A general system error ocurred : internal error";}
          if($transaccion_exitosa){
                    $conexion->commit();
                    $conexion->close();
          }else{
                    $conexion->rollback();
                    $conexion->close();
                    $error = "1";
          } 
      }
      $response = array("error"=>"$error","msj"=>"$msj");
      echo json_encode($response); 
  }
  function get_unit(){
      //Variables:
      $error   = '0';
      $msj     = "";
      $fields  = "";
      $clave   = trim($_POST['clave']);
      $company = trim($_POST['company']);  
      $domroot = $_POST['domroot'];
      
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                 
      $sql    = "SELECT * FROM ct_unidades WHERE iConsecutivo = '$clave' AND iConsecutivoCompania = '$company'";  
      $result = $conexion->query($sql);
      $items  = $result->num_rows;   
      if($items > 0){     
        $data = $result->fetch_assoc();
        $llaves  = array_keys($data);
        $datos   = $data;
        foreach($datos as $i => $b){
            if($i != 'siConsecutivosPolizas'){
               $fields .= "\$('#$domroot :input[id=".$i."]').val('".$datos[$i]."');"; 
            }
             
        } 
        
        //CONSULTAR POLIZAS:
        $query  = "SELECT iConsecutivoPoliza, B.sNumeroPoliza, C.sDescripcion AS sTipoPoliza, C.sAlias, DATE_FORMAT(A.dFechaIngreso,'%m/%d/%Y') AS dFechaIngreso,eModoIngreso ".
                   "FROM cb_poliza_unidad AS A ".
                   "INNER JOIN ct_polizas   AS B ON A.iConsecutivoPoliza = B.iConsecutivo AND B.iDeleted = '0' AND B.dFechaCaducidad >= CURDATE() ".
                   "LEFT JOIN  ct_tipo_poliza AS C ON B.iTipoPoliza = C.iConsecutivo ".
                   "WHERE A.iConsecutivoUnidad = '".$data['iConsecutivo']."' AND A.iDeleted = '0' ";
        $r      = $conexion->query($query);
        $total  = $r->num_rows;
        $PDApply= 'false';  
        if($total > 0){
           while ($poli = $r->fetch_assoc()){ 
                if($poli['sAlias'] == "PD"){$PDApply = 'true';} 
                $fields .= "\$('#unit_edit_form  :input[value=\"".$poli['iConsecutivoPoliza']."\"]').prop(\"checked\",\"true\");";
           }
        }
      }
      $conexion->rollback();
      $conexion->close(); 
      $response = array("msj"=>"$msj","error"=>"$error","fields"=>"$fields","PDApply"=>"$PDApply");   
      echo json_encode($response);
  }   
  
  /*----- BACKUP ----*/
  function upload_driver_txt_backup(){
      
      include("cn_usuarios.php");  
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      #variables:
      $iConsecutivoCompania = $_POST['iConsecutivoCompania'];
      $iConsecutivoPolizas  = $_POST['iConsecutivoPolizas'];
      $error = "0";
      $mensaje = "";
      $htmlTabla = ""; 
      $fileName = $_FILES['userfile']['name'];
      $tmpName  = $_FILES['userfile']['tmp_name'];
      if($iConsecutivoCompania != '' && $iConsecutivoPolizas != ''){
          #Revisamos contenido del archivo:
          $fp      = fopen($tmpName, 'r'); 
          $content = fread($fp, filesize($tmpName));
          $content = addslashes($content);
          fclose($fp);
          $array_contenido = explode("\r\n",$content);
          print_r($array_contenido);
          exit;
          foreach($array_contenido as $key => $valor){
            $valores = array();
            $campos  = array();  
            if($transaccion_exitosa){    
              if($array_contenido[$key] != ""){ 
                  $array_driver = explode("|",$array_contenido[$key]);
                  $sNombre = strtoupper($array_driver[0]);
                  $dFechaNacimiento = format_date($array_driver[1]);
                  $dFechaExpiracionLicencia = $array_driver[2];
                  $iExperienciaYear = $array_driver[3];
                  $iNumLicencia = strtoupper($array_driver[4]);
                  $eTipoLicencia = $array_driver[5];
                  //print_r($array_driver);
                  
                  //Header table:
                  $htmlTabla = '&lt;tr style="background: rgb(55, 101, 176);color:#ffffff!important;">'.
                               '&lt;td&gt; NAME</td>'.
                               '&lt;td&gt; DOB</td>'.
                               '&lt;td&gt; EXPIRE DATE</td>'.
                               '&lt;td&gt; EXPERIENCE YEARS</td>'.
                               '&lt;td&gt; LICENSE NUMBER</td>';
                  
                  #Verificamos si el driver no existe:
                  $sql = "SELECT COUNT(iConsecutivo) AS total FROM ct_operadores WHERE iNumLicencia = '$iNumLicencia' AND iConsecutivoCompania = '$iConsecutivoCompania' ";
                  $result = $conexion->query($sql);
                  $items = $result->fetch_assoc();
                  $existe = $items["total"];
                  if($existe == "0"){
                      
                      #armando Array:
                      array_push($campos ,"iConsecutivoCompania"); array_push($valores,trim($iConsecutivoCompania));
                      if($sNombre != ''){array_push($campos ,"sNombre"); array_push($valores,trim($sNombre));}
                      if($dFechaNacimiento != ''){array_push($campos ,"dFechaNacimiento"); array_push($valores,trim($dFechaNacimiento));}
                      if($dFechaExpiracionLicencia != ''){array_push($campos ,"dFechaExpiracionLicencia"); array_push($valores,trim($dFechaExpiracionLicencia));}
                      if($iExperienciaYear != ''){array_push($campos ,"iExperienciaYear"); array_push($valores,trim($iExperienciaYear));}
                      if($iNumLicencia != ''){array_push($campos ,"iNumLicencia"); array_push($valores,trim($iNumLicencia));}
                      if($eTipoLicencia != ''){array_push($campos ,"eTipoLicencia"); array_push($valores,trim($eTipoLicencia));}
                      array_push($campos ,"dFechaIngreso"); array_push($valores,date("Y-m-d H:i:s"));
                      array_push($campos ,"sIP"); array_push($valores,$_SERVER['REMOTE_ADDR']);
                      array_push($campos ,"sUsuarioIngreso"); array_push($valores,$_SESSION['usuario_actual']);
                      array_push($campos ,"siConsecutivosPolizas"); array_push($valores,$iConsecutivoPolizas); 
                      array_push($campos ,"inPoliza"); array_push($valores,'1'); 
                      
                      $insert = "INSERT INTO ct_operadores (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')";
                      $conexion->query($insert);
                      if($conexion->affected_rows < 1){ 
                         $transaccion_exitosa = false;
                         $msj = "The data of driver was not saved properly, please try again.";               
                      }else{
                        $htmlTabla .= '&lt;tr style="background: rgb(234, 255, 226);">'.
                                      '&lt;td&gt;'.$driver_name.'</td>'.
                                      '&lt;td&gt;'.$driver_dob.'</td>'.
                                      '&lt;td&gt;'.$driver_exp_lic.'</td>'.
                                      '&lt;td&gt;'.$driver_exp_years.'</td>'.
                                      '&lt;td&gt;'.$driver_license.'</td>'; 
                      } 
                      
                  }else{
                     #YA EXISTE actualizamos solo la parte de las polizas para incluirlos:
                     //if($iNumLicencia != ''){array_push($valores,"iNumLicencia='".trim($iNumLicencia)."'");}
                     if($sNombre != ''){array_push($valores,"sNombre='".trim($sNombre)."'");}
                     if($dFechaNacimiento != ''){array_push($valores,"dFechaNacimiento='".trim($dFechaNacimiento)."'"); }
                     if($dFechaExpiracionLicencia != ''){array_push($valores,"dFechaExpiracionLicencia='".trim($dFechaExpiracionLicencia)."'"); }
                     if($iExperienciaYear != ''){array_push($valores,"iExperienciaYear='".trim($iExperienciaYear)."'"); }
                     if($eTipoLicencia != ''){array_push($valores,"eTipoLicencia='".trim($eTipoLicencia)."'"); } 
                     array_push($valores,"sIPActualizacion='".$_SERVER['REMOTE_ADDR']."'");
                     array_push($valores,"dFechaActualizacion='".date_to_server(date("Y-m-d H:i:s"))."'");
                     array_push($valores,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'");
                     array_push($valores,"iConsecutivosPolizas='".trim(siConsecutivosPolizas)."'");
                     array_push($valores,"inPoliza='1'");
                     
                     $update = "UPDATE ct_operadores SET ".implode(",",$valores)." WHERE iNumLicencia = '$iNumLicencia' AND iConsecutivoCompania = '$iConsecutivoCompania'";
                     $conexion->query($update);
                     
                     if($conexion->affected_rows < 1){ 
                         $transaccion_exitosa = false;
                         $msj = "The data of driver was not saved properly, please try again.";               
                      }else{
                        $tabla_error .= '&lt;tr style="background: rgb(254, 232, 154);">'.
                                     '&lt;td&gt;'.$driver_name.'</td>'.
                                     '&lt;td&gt;'.$driver_dob.'</td>'.
                                     '&lt;td&gt;'.$driver_exp_lic.'</td>'.
                                     '&lt;td&gt;'.$driver_exp_years.'</td>'.
                                     '&lt;td&gt;'.$driver_license.'</td>';
                      }  
                     
                  }
              }
            }
          }          
      }
      if($transaccion_exitosa){
            $conexion->commit();
            $conexion->close();
            $mensaje = "The drivers has been uploaded successfully, please verify the data in the company policies.";
      }else{
            $conexion->rollback();
            $conexion->close();
            $error = "1";
      }
      $htmlTabla = $htmlTabla.$tabla_error;
      //$htmlTabla = utf8_encode($htmlTabla);
      $response = array("mensaje"=>"$mensaje","error"=>"$error", "name_file" => "$fileName","reporte"=>"$htmlTabla"); 
      echo json_encode($response); 
      
  }
?>
