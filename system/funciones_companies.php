<?php
  session_start();
  // Generic functions lib 
  include("functiones_genericas.php"); 
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId 
    
  //Catalogo de compañias:
  function get_companies(){
     
    include("cn_usuarios.php");
    $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
    $transaccion_exitosa = true;
    
    $registros_por_pagina = $_POST["registros_por_pagina"];
    $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
    $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
        
    //Filtros de informacion //
    $filtroQuery = " WHERE iConsecutivo IS NOT NULL AND iDeleted='0'";
    $array_filtros = explode(",",$_POST["filtroInformacion"]);
    foreach($array_filtros as $key => $valor){
        if($array_filtros[$key] != ""){
            $campo_valor = explode("|",$array_filtros[$key]);
            if($campo_valor[0] == 'sNombreCompania'){$campo_valor[1] = htmlspecialchars($campo_valor[1],ENT_QUOTES);}
            $campo_valor[0] == 'iConsecutivo' ? $filtroQuery.= " AND  ".$campo_valor[0]."='".$campo_valor[1]."' " : $filtroQuery == "" ? $filtroQuery.= " AND  ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'": $filtroQuery.= " AND ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'";
        }
    }
    // ordenamiento//
    $ordenQuery = " ORDER BY ".$_POST["ordenInformacion"]." ".$_POST["sortInformacion"];
    
    //contando registros // 
    $query_rows = "SELECT COUNT(iConsecutivo) AS total FROM ct_companias ".$filtroQuery;
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
        $sql = "SELECT iConsecutivo as id, sNombreCompania, sNombreContacto, sDireccion AS direccion, CONCAT(sCiudad, ' ', sEstado ) AS estado, 
                sCodigoPostal AS zipcode, sTelefonoPrincipal AS tel,sTelefono2 AS tel_2,sTelefono3 AS tel_3, sUsdot AS usdot, iOnRedList
                FROM  ct_companias ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
        $result = $conexion->query($sql);
        $rows = $result->num_rows;    
        if ($rows > 0) {   
        
            $valid_user = valid_user($_SESSION['usuario_actual']);

            while ($usuario = $result->fetch_assoc()) { 
               if($usuario["id"] != ""){
                     //telefonos:
                     $telefonos = $usuario['tel'];
                     if($usuario['tel_2'] != ""){  $telefonos .= " / ".$usuario['tel_2'];}
                     if($usuario['tel_3'] != ""){$telefonos .= " / ".$usuario['tel_3'];}
                     //Redlist:
                     if($usuario['iOnRedList'] == '1'){
                        $redlist_class = "class=\"row_red\"";
                        $redlist_icon = "<i class=\"fa fa-star\" style=\"color:#e8051b;margin-right:4px;\"></i>"; 
                     }else{
                        $redlist_icon = ""; 
                        $redlist_class = "";
                     }
                     
                     $btns = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"Edit Company\"><i class=\"fa fa-pencil-square-o\"></i> <span></span></div>".
                             "<div class=\"btn_delete btn-icon trash btn-left\" title=\"Deshabilitar empresa\"><i class=\"fa fa-trash\"></i> <span></span></div>";
                     
                     $htmlTabla .= "<tr ".$redlist_class.">
                                        <td>".$usuario['id']."</td>".
                                       "<td>".$redlist_icon.$usuario['sNombreCompania']."</td>".
                                       "<td>".$usuario['usdot']."</td>". 
                                       "<td>".$usuario['direccion']."</td>".
                                       "<td>".$usuario['estado']."</td>".
                                       "<td>".$usuario['zipcode']."</td>";
                     if($valid_user){
                        $htmlTabla .= "<td>".$usuario['sNombreContacto']."</td>".
                                       "<td>".$telefonos."</td>"; 
                     }
                                                                                                                                                                                                                                                             
                     $htmlTabla .= "<td>$btns</td></tr>";
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
    
    $valid_user = valid_user($_SESSION['usuario_actual']);

    if(!($valid_user)){
      $error = '1';
      $msj   = "This user does not have the privileges to modify or add data to the system.";
    }
    else{
        $sql   = "SELECT * FROM ct_companias WHERE iConsecutivo = ".$clave;
        $result= $conexion->query($sql);
        $items = $result->num_rows;   
        if ($items > 0) {     
            $drivers = $result->fetch_assoc();
            $llaves  = array_keys($drivers);
            $datos   = $drivers;
            
            foreach($datos as $i => $b){
                 if($i == 'sNombreCompania'){$datos[$i] = addslashes(htmlspecialchars_decode($datos[$i],ENT_QUOTES));} 
                 $fields .= "\$('#$domroot :input[id=".$i."]').val('".$datos[$i]."');"; 
            }  
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
      $_POST['sNombreCompania'] = strtoupper(htmlspecialchars($_POST['sNombreCompania'],ENT_QUOTES));  
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      $query = "SELECT COUNT(iConsecutivo) AS total FROM ct_companias WHERE sUsdot ='".$_POST['sUsdot']."' AND sUsdot != 'NA'";
      $result = $conexion->query($query);
      $valida = $result->fetch_assoc();
      
      if($valida['total'] != '0'){
          if($_POST["edit_mode"] != 'true'){
              $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>
                      Error: The company trying to add already exists. Please verify the data.</p>';
              $error = '1';
          }
      } 
      
      if($error == '0'){
          if($_POST["edit_mode"] == 'true'){
            
             foreach($_POST as $campo => $valor){
                if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivo" ){ //Estos campos no se insertan a la tabla
                    //if($campo == 'sNombreCompania'){$valor = addslashes($valor);} 
                    if($valor != ""){array_push($valores,"$campo='".trim($valor)."'");} 
                }
             }     
              
              
            array_push($valores ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
            array_push($valores ,"sIP='".$_SERVER['REMOTE_ADDR']."'");
            array_push($valores ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'");
            $sql = "UPDATE ct_companias SET ".implode(",",$valores)." WHERE iConsecutivo = '".$_POST['iConsecutivo']."'";
            $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The data has been updated successfully.</p>'; 
          }
          else{
            foreach($_POST as $campo => $valor){
               if($campo != "accion" and $campo != "edit_mode"){ //Estos campos no se insertan a la tabla
                    if($valor != ""){
                        
                       /*if($campo == 'sNombreCompania'){
                        $valor = addslashes($valor);    
                       }*/ 
                       array_push($campos ,$campo); 
                       array_push($valores, trim($valor)); 
                    }
               }
            }  
           
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
          
          if($transaccion_exitosa){$conexion->commit();$conexion->close();}
          else{
            $msj   = "A general system error ocurred : internal error";
            $error = "1";
            $mysqlError = $conexion->error;
            $conexion->rollback();
            $conexion->close();
          }
          if($transaccion_exitosa)$msj = "The data has been saved successfully."; 
      }
      $response = array("error"=>"$error","msj"=>"$msj", 'err_db'=>$mysqlError);
      echo json_encode($response);
  }  
  function delete_company(){
      
      $error = '0';  
      $msj   = "";  
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      $valid_user = valid_user($_SESSION['usuario_actual']);

      if(!($valid_user)){
          $error = '1';
          $msj   = "This user does not have the privileges to modify or add data to the system.";
      }else{
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
      }
      
      
      
      if($error == "0"){$conexion->commit();$conexion->close();}
      else{$conexion->rollback();$conexion->close();}
        
      $response = array("msj"=>"$msj","error"=>"$error");   
      echo json_encode($response);
  }
  
?>
