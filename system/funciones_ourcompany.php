<?php
  session_start();
  // Generic functions lib 
  include("functiones_genericas.php"); 
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId  
  
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
      
      $limpiar = array('rgb(', ')');
      $_POST['sColorPdf']       = str_replace($limpiar, "", $_POST['sColorPdf']);
      $_POST['sCorreoEmpresa']  = strtolower(trim($_POST['sCorreoEmpresa']));
      

      if($_POST["edit_mode"] == 'true'){
        
        foreach($_POST as $campo => $valor){
            if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivo" ){ //Estos campos no se insertan a la tabla
                if($campo == "sNombreCompleto" || $campo == "sAlias"){$valor = strtoupper(utf8_decode($valor));}
                else if($campo == "sCorreoEmpresa"){$valor = strtolower($valor);}
                array_push($valores,"$campo='".trim($valor)."'");
            }
        }      
          
        array_push($valores ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
        array_push($valores ,"sIPActualizacion='".$_SERVER['REMOTE_ADDR']."'");
        array_push($valores ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'");
        $sql = "UPDATE ct_empresa SET ".implode(",",$valores)." WHERE iConsecutivo = '".$_POST['iConsecutivo']."'";
        $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The data has been updated successfully.</p>'; 
        
      }else{
          
        foreach($_POST as $campo => $valor){
           if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivo"){ //Estos campos no se insertan a la tabla
                if($campo == "sNombreCompleto" || $campo == "sAlias"){$valor = strtoupper(utf8_decode($valor));}
                else if($campo == "sCorreoEmpresa"){$valor = strtolower($valor);}
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
        $sql = "INSERT INTO ct_empresa (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')";
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
      
      $response = array("error"=>"$error","msj"=>"$msj");
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
        $sql = "SELECT * FROM ct_empresa WHERE iConsecutivo IS NOT NULL LIMIT 1 ";
        $result = $conexion->query($sql);
        $items = $result->num_rows;   
        if ($items > 0) {     
            $items = $result->fetch_assoc();
            $llaves  = array_keys($items);
            $datos   = $items;
            foreach($datos as $i => $b){
                if($i == "sColorPdf"){
                     $fields .= "\$('#$domroot :input[id=".$i."]').minicolors('value', 'rgb(".$datos[$i].")');";     
                }
                else{$fields .= "\$('#$domroot :input[id=".$i."]').val('".$datos[$i]."');"; }
            }  
        }
        $conexion->rollback();
        $conexion->close(); 
        $response = array("msj"=>"$msj","error"=>"$error","fields"=>"$fields");   
        echo json_encode($response);
  }   
?>
