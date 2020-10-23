<?php
  session_start();
  // Generic functions lib 
  include("functiones_genericas.php"); 
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId 
 
  function get_data(){
    include("cn_usuarios.php");
    $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
    $transaccion_exitosa = true;
    
    $registros_por_pagina = $_POST["registros_por_pagina"];
    $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
    $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
        
    //Filtros de informacion //
    $filtroQuery = " WHERE A.iConsecutivo != ''";
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
    $query_rows = "SELECT COUNT(A.iConsecutivo) AS total FROM ct_financieras AS A ".
                  "LEFT JOIN ct_brokers AS B ON A.iConsecutivoBroker = B.iConsecutivo ".$filtroQuery;
    $Result     = $conexion->query($query_rows);
    $items      = $Result->fetch_assoc();
    $registros  = $items["total"];
    
    if($registros == "0"){$pagina_actual = 0;}
    $paginas_total = ceil($registros / $registros_por_pagina);
    if($registros == "0"){
        $limite_superior = 0;
        $limite_inferior = 0;
        $htmlTabla       ="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";
    }else{
        $pagina_actual == "0" ? $pagina_actual = 1 : false;
        $limite_superior = $registros_por_pagina;
        $limite_inferior = ($pagina_actual*$registros_por_pagina)-$registros_por_pagina;
        $sql    = "SELECT A.iConsecutivo, A.sName, A.sTelefono,A.sNombreContacto, A.iConsecutivoBroker, A.sEmail, B.sName AS sBrokerName, A.sComentarios ".
                  "FROM      ct_financieras AS A ".
                  "LEFT JOIN ct_brokers     AS B ON A.iConsecutivoBroker = B.iConsecutivo ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior; 
        $result = $conexion->query($sql);
        $rows   = $result->num_rows;   
        if ($rows > 0) { 
                $htmlTabla = "";   
                while ($items = $result->fetch_assoc()){ 
                   
                     $htmlTabla .= "<tr>
                                        <td>".$items['iConsecutivo']."</td>".
                                       "<td>".$items['sName']."</td>".
                                       "<td>".$items['sNombreContacto']."</td>".
                                       "<td>".$items['sEmail']."</td>".
                                       "<td>".$items['sTelefono']."</td>". 
                                       "<td>".$items['sBrokerName']."</td>". 
                                       "<td>".utf8_encode($items['sComentarios'])."</td>".                                                                                                                                                                                                                      
                                       "<td>
                                            <div class=\"btn_edit btn-icon edit btn-left\" title=\"Edit\"><i class=\"fa fa-pencil-square-o\"></i> <span></span></div>
                                            <div class=\"btn_delete btn-icon trash btn-left\" title=\"Delete\"><i class=\"fa fa-trash\"></i> <span></span></div>
                                       </td></tr>";  
                       
                }
                                                                                                                                                                                      
        }else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>"   ;    }  
    }
    $conexion->rollback();
    $conexion->close(); 
    $response = array("total"=>"$paginas_total","pagina"=>"$pagina_actual","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error");   
    echo json_encode($response);
  }
  function get_finance_data(){
      $error = '0';
      $msj = "";
      $fields = "";
      $clave = trim($_POST['clave']);
      $domroot = $_POST['domroot'];
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                 
      $sql    = "SELECT iConsecutivo, sName, sTelefono, sNombreContacto, sEmail, iConsecutivoBroker, sComentarios ".
                "FROM ct_financieras WHERE iConsecutivo = '".$clave."'";
      $result = $conexion->query($sql);
      $items  = $result->num_rows;   
      if ($items > 0) {     
        $data = $result->fetch_assoc();
        $llaves  = array_keys($data);
        $datos   = $data;
        foreach($datos as $i => $b){
            $fields .= "\$('#$domroot [name=".$i."]').val(\"".$datos[$i]."\");"; 
        }  
      }
      $conexion->rollback();
      $conexion->close(); 
      $response = array("msj"=>"$msj","error"=>"$error","fields"=>"$fields");   
      echo json_encode($response);
  }
  function save_insurance(){
      include("funciones_genericas.php");
      $error   = '0'; 
      $valores = array();
      $campos  = array(); 
      $msj     = "";  
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      $_POST['sComentarios'] != "" ? $sComentarios = trim(fix_string($_POST['sComentarios'])) : $sComentarios = "''";

      
      //Revisando si ya existe un broker con el mismo nombre:
      $query  = "SELECT COUNT(iConsecutivo) AS total FROM ct_financieras WHERE sName =\"".(strtoupper($_POST['sName']))."\"";
      $result = $conexion->query($query);
      $valida = $result->fetch_assoc();
      
      if($valida['total'] != '0' || $_POST["edit_mode"] == 'true'){
          if($_POST["edit_mode"] != 'true'){
              $msj   = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Error: The Financier that you trying to add already exists. Please you verify the data.</p>';
              $error = '1';
          }else{
             foreach($_POST as $campo => $valor){
                if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivo"){ //Estos campos no se insertan a la tabla
                    if($campo == "sName" || $campo == "sNombreContacto" && $valor != ""){$valor = (strtoupper($valor));}
                    if($campo == "sEmail" && $valor != ""){$valor = strtolower($valor);}
                   
                    array_push($valores,"$campo=\"".($valor)."\"");
                }
             }   
          }
      }else if($_POST["edit_mode"] != 'true'){
         foreach($_POST as $campo => $valor){
            if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivo"){ //Estos campos no se insertan a la tabla
                if($campo == "sName" || $campo == "sNombreContacto" && $valor != ""){$valor = strtoupper($valor);}
                if($campo == "sEmail" && $valor != ""){$valor = strtolower($valor);}
                array_push($campos ,$campo); 
                array_push($valores,($valor));
            }
         }  
      }
    
      if($error == '0'){
          if($_POST["edit_mode"] == 'true'){
            array_push($valores ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
            array_push($valores ,"sIP='".$_SERVER['REMOTE_ADDR']."'");
            array_push($valores ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'"); 
            $sql = "UPDATE ct_financieras SET ".implode(",",$valores)." WHERE iConsecutivo =\"".$_POST['iConsecutivo']."\"";
            $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Data have been updated successfully.</p>'; 
          }else{
            array_push($campos ,"dFechaIngreso");
            array_push($valores ,date("Y-m-d H:i:s"));
            array_push($campos ,"sIP");
            array_push($valores ,$_SERVER['REMOTE_ADDR']);
            array_push($campos ,"sUsuarioIngreso");
            array_push($valores ,$_SESSION['usuario_actual']);
            $sql = "INSERT INTO ct_financieras (".implode(",",$campos).") VALUES (\"".implode("\",\"",$valores)."\")";
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
  function delete_insurance(){
      $error = '0';  
      $msj = "";  
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      $query = "DELETE FROM ct_financieras WHERE iConsecutivo = '".$_POST["clave"]."'"; 
      $conexion->query($query);
      $conexion->affected_rows < 1 ? $transaccion_exitosa = false : $transaccion_exitosa = true;
      if($transaccion_exitosa){
        $conexion->commit();
        $conexion->close();
        $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>
                The data has been deleted succesfully!</p>';
      }else{
        $conexion->rollback();
        $conexion->close();
        $msj = "A general system error ocurred : internal error";
        $error = "1";
      }
        
      $response = array("msj"=>"$msj","error"=>"$error");   
      echo json_encode($response);
  }
?>
