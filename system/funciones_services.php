<?php
  session_start();
  // Generic functions lib 
  include("functiones_genericas.php"); 
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId 
    
  //Catalogo de compañias:
  function get_list(){
     
    include("cn_usuarios.php");
    $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
    $transaccion_exitosa = true;
    
    $registros_por_pagina = $_POST["registros_por_pagina"];
    $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
    $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
        
    //Filtros de informacion //
    $filtroQuery = " WHERE sClave IS NOT NULL AND bEliminado = '0' ";
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
    $query_rows = "SELECT COUNT(iConsecutivo) AS total FROM ct_productos_servicios ".$filtroQuery;
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
        $sql = "SELECT iConsecutivo,sClave, sDescripcion, sCveUnidadMedida, iPrecioUnitario, iPctImpuesto, iCategoriaPS 
                FROM  ct_productos_servicios ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
        $result = $conexion->query($sql);
        $rows = $result->num_rows;    
        if ($rows > 0) {    
            while ($items = $result->fetch_assoc()) { 
                     $htmlTabla .= "<tr>
                                    <td id=\"id_".$items['iConsecutivo']."\">".$items['sClave']."</td>".
                                    "<td>".$items['sDescripcion']."</td>".
                                    "<td>".$items['iCategoriaPS']."</td>". 
                                    "<td>".$items['sCveUnidadMedida']."</td>".
                                    "<td class=\"text-right\">\$ ".number_format($items['iPrecioUnitario'],2,'.',',')."</td>".
                                    "<td class=\"text-center\">".$items['iPctImpuesto']." %</td>".                                                                                                                                                                                                                       
                                    "<td>
                                    <div class=\"btn_edit btn-icon edit btn-left\" title=\"Edit data\"><i class=\"fa fa-pencil-square-o\"></i> <span></span></div>
                                    <div class=\"btn_delete btn-icon trash btn-left\" title=\"Delete data\"><i class=\"fa fa-trash\"></i> <span></span></div>
                                    </td></tr>";    
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
        $error   = '0';
        $msj     = "";
        $fields  = "";
        $clave   = trim($_POST['clave']);
        $domroot = trim($_POST['domroot']);
        
        include("cn_usuarios.php");
        $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
        $transaccion_exitosa = true;
        $sql = "SELECT * FROM ct_productos_servicios WHERE iConsecutivo = ".$clave;
        $result = $conexion->query($sql);
        $items = $result->num_rows;   
        if ($items > 0) {     
            $items = $result->fetch_assoc();
            $llaves  = array_keys($items);
            $datos   = $items;
            foreach($datos as $i => $b){$fields .= "\$('#$domroot :input[id=".$i."]').val('".$datos[$i]."');"; }  
        }
        $conexion->rollback();
        $conexion->close(); 
        $response = array("msj"=>"$msj","error"=>"$error","fields"=>"$fields");   
        echo json_encode($response);
  }   
  function save_data(){
      
      include("funciones_genericas.php");
      $error   = '0'; 
      $valores = array();
      $campos  = array(); 
      $msj     = "";
  
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      //Revisamos si no existe otro servicio con la misma clave que no este eliminado:
      $query  = "SELECT iConsecutivo,bEliminado FROM ct_productos_servicios WHERE sClave ='".trim($_POST['sClave'])."' ";
      $result = $conexion->query($query);
      $valida = $result->fetch_assoc();
  
      if($valida['iConsecutivo'] != ''){
          if($_POST["edit_mode"] != 'true'){   
              if($valida['bEliminado'] == "0"){
                 $msj   = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>Error: The item that you trying to add already exists, please verify the data.</p>';
                 $error = '1'; 
              }else{
                  $_POST["edit_mode"]    = "true";
                  $_POST['iConsecutivo'] = $valida['iConsecutivo'];
                  array_push($valores,"bEliminado='0'");
              }
          }
      }
      
      if($error == '0'){
          if($_POST["edit_mode"] == 'true'){
            
            foreach($_POST as $campo => $valor){
                if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivo" ){ //Estos campos no se insertan a la tabla
                    if($campo == "sDescripcion" || $campo == "sClave"){$valor = strtoupper(utf8_decode($valor));}
                    else if($campo == "sComentarios"){$valor = utf8_decode($valor);}
                    array_push($valores,"$campo='".trim($valor)."'");
                }
            }      
              
            array_push($valores ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
            array_push($valores ,"sIPActualizacion='".$_SERVER['REMOTE_ADDR']."'");
            array_push($valores ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'");
            $sql = "UPDATE ct_productos_servicios SET ".implode(",",$valores)." WHERE iConsecutivo = '".$_POST['iConsecutivo']."'";
            $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The data has been updated successfully.</p>'; 
            
          }else{
              
            foreach($_POST as $campo => $valor){
               if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivo"){ //Estos campos no se insertan a la tabla
                    if($campo == "sDescripcion" || $campo == "sClave"){$valor = strtoupper(utf8_decode($valor));}
                    else if($campo == "sComentarios"){$valor = utf8_decode($valor);}
                    array_push($campos ,$campo); 
                    array_push($valores, trim($valor));
               }
            }  
           
            array_push($campos ,"dFechaIngreso");
            array_push($valores ,date("Y-m-d H:i:s"));
            array_push($campos ,"sIPIngreso");
            array_push($valores ,$_SERVER['REMOTE_ADDR']);
            array_push($campos ,"sUsuarioIngreso");
            array_push($valores ,$_SESSION['usuario_actual']);
            $sql = "INSERT INTO ct_productos_servicios (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')";
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
  function delete_record(){
        $error   = '0';
        $msj     = "";
        $fields  = "";
        $clave   = trim($_POST['iConsecutivo']);
        
        include("cn_usuarios.php");
        $conexion->autocommit(FALSE);  
                                                                                                                                                                                                                                            
        $transaccion_exitosa = true;
        $sql                 = "UPDATE ct_productos_servicios SET bEliminado ='1' WHERE iConsecutivo = '$clave'";
        
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
        if($transaccion_exitosa)$msj = "The data has been deleted successfully.";  

        $response = array("msj"=>"$msj","error"=>"$error");   
        echo json_encode($response);
  }
?>
