<?php
  session_start();
  // Generic functions lib 
  include("functiones_genericas.php"); 
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId 
    
  //Catalogo de compañias:
  function get_invoices(){
     
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
            $campo_valor[0] == 'iConsecutivo' ? $filtroQuery.= " AND  ".$campo_valor[0]."='".$campo_valor[1]."' " : $filtroQuery == "" ? $filtroQuery.= " AND  ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'": $filtroQuery.= " AND ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'";
        }
    }
    // ordenamiento//
    $ordenQuery = " ORDER BY ".$_POST["ordenInformacion"]." ".$_POST["sortInformacion"];
    
    //contando registros // 
    $query_rows = "SELECT COUNT(A.iConsecutivo) AS total FROM cb_invoices A ".
                  "LEFT JOIN ct_companias B ON A.iConsecutivoCompania = B.iConsecutivo ".
                  "LEFT JOIN ct_services C ON A.iTipoInvoice = C.iConsecutivo ".$filtroQuery;
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
        $sql = "A.iConsecutivo, sNombreCompania, sNombreContacto, sDescripcion AS PaymentFor, eTipoInvoice AS Summary, iAmount, iFinanciamiento, sDiasFinanciamiento, dFechaCobro, eStatus, iOnRedList ".   
               "FROM cb_invoices A ".
               "LEFT JOIN ct_companias B ON A.iConsecutivoCompania = B.iConsecutivo ".
               "LEFT JOIN ct_services C ON A.iTipoInvoice = C.iConsecutivo ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
        $result = $conexion->query($sql);
        $rows = $result->num_rows;    
        if ($rows > 0) {    
            while ($items = $result->fetch_assoc()) { 
               if($items["iConsecutivo"] != ""){
                     
                     //Redlist:
                     if($items['iOnRedList'] == '1'){
                        $redlist_class = "class=\"row_red\"";
                        $redlist_icon = "<i class=\"fa fa-star\" style=\"color:#e8051b;margin-right:4px;\"></i>"; 
                     }else{
                        $redlist_icon = ""; 
                        $redlist_class = "";
                     }
                     
                     $htmlTabla .= "<tr ".$redlist_class.">
                                        <td>".$items['iConsecutivo']."</td>".
                                       "<td>".$redlist_icon.$items['sNombreContacto']."</td>".
                                       "<td>".$items['sNombreCompania']."</td>". 
                                       "<td>".$items['eTipoInvoice']."</td>".
                                       "<td>".$items['dFechaIngreso']."</td>".
                                       "<td>".$items['iAmount']."</td>".
                                       "<td>".$items['sDescripcion']."</td>".
                                       "<td>".$items['iFinanciamiento']."</td>".  
                                       "<td>".$items['eStatus']."</td>".                                                                                                                                                                                                                    
                                       "<td>
                                            <div class=\"btn_edit btn-icon edit btn-left\" title=\"Edit Invoice\"><i class=\"fa fa-pencil-square-o\"></i> <span></span></div>
                                            <div style=\"display:none;\" class=\"btn_delete btn-icon trash btn-left\" title=\"Cancel Invoice\"><i class=\"fa fa-trash\"></i> <span></span></div>
                                       </td></tr>";
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
  function get_company(){
    $error = '0';
    $msj = "";
    $fields = "";
    $clave = trim($_POST['clave']);
    $domroot = $_POST['domroot'];
    include("cn_usuarios.php");
    //$conexion->begin_transaction();
    $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
    $transaccion_exitosa = true;
    $sql = "SELECT * FROM ct_companias WHERE iConsecutivo = ".$clave;
    $result = $conexion->query($sql);
    $items = $result->num_rows;   
    if ($items > 0) {     
        $drivers = $result->fetch_assoc();
        $llaves  = array_keys($drivers);
        $datos   = $drivers;
        
        foreach($datos as $i => $b){
             $fields .= "\$('#$domroot :input[id=".$i."]').val('".$datos[$i]."');"; 
             //if($i == 'iEntidad'){$country = $datos[$i];} 
        }  
    }
    $conexion->rollback();
    $conexion->close(); 
    $response = array("msj"=>"$msj","error"=>"$error","fields"=>"$fields");   
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
      
      $query = "SELECT COUNT(iConsecutivo) AS total FROM ct_companias WHERE sNombreCompania ='".$_POST['sNombreCompania']."'";
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
