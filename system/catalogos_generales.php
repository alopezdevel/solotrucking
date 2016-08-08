<?php
  session_start();
  // Generic functions lib 
  include("functiones_genericas.php"); 
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId 
   
  function get_companies(){
     include("cn_usuarios.php");
     $conexion->autocommit(FALSE);
      
     $sql = "SELECT iConsecutivo AS clave, sNombreCompania AS descripcion 
             FROM ct_companias ORDER BY iConsecutivo ASC";
     $result = $conexion->query($sql);
     $tipos = $result->num_rows;  
        if($tipos > 0){
            $htmlTabla .= "<option value=\"\">Select an option...</option>";      
            while ($country = $result->fetch_assoc()) {
               if($country["clave"] != ""){
                     $htmlTabla .= "<option value=\"".$country['clave']."\">".$country['descripcion']."</option>";
                 }else{                             
                     $htmlTabla .="";
                 }    
            }                                                                                                                                                                       
        }else {$htmlTabla .="";}
     $conexion->rollback();
     $conexion->close();
     $htmlTabla = utf8_encode($htmlTabla);  
     $response = array("mensaje"=>"$mensaje","error"=>"$error","select"=>"$htmlTabla");   
     echo json_encode($response);  
  }
  function get_user_types(){
     include("cn_usuarios.php");
     $conexion->autocommit(FALSE);
      
     $sql = "SELECT iConsecutivo AS clave, sDescripcionTipo AS descripcion 
             FROM ct_tipo_usuario WHERE sCveTipo != 'MA' ORDER BY iConsecutivo ASC";
     $result = $conexion->query($sql);
     $tipos = $result->num_rows;  
        if($tipos > 0){
            $htmlTabla .= "<option value=\"\">Select an option...</option>";      
            while ($country = $result->fetch_assoc()) {
               if($country["clave"] != ""){
                     $htmlTabla .= "<option value=\"".$country['clave']."\">".$country['descripcion']."</option>";
                 }else{                             
                     $htmlTabla .="";
                 }    
            }                                                                                                                                                                       
        }else {$htmlTabla .="";}
     $conexion->rollback();
     $conexion->close();
     $htmlTabla = utf8_encode($htmlTabla);  
     $response = array("mensaje"=>"$mensaje","error"=>"$error","select"=>"$htmlTabla");   
     echo json_encode($response);  
  }
  function get_brokers(){
     include("cn_usuarios.php");
     $conexion->autocommit(FALSE);
      
     $sql = "SELECT iConsecutivo AS clave, sName AS descripcion 
             FROM ct_brokers ORDER BY iConsecutivo ASC";
     $result = $conexion->query($sql);
     $tipos = $result->num_rows;  
        if($tipos > 0){
            $htmlTabla .= "<option value=\"\">Select an option...</option>";      
            while ($country = $result->fetch_assoc()) {
               if($country["clave"] != ""){
                     $htmlTabla .= "<option value=\"".$country['clave']."\">".$country['descripcion']."</option>";
                 }else{                             
                     $htmlTabla .="";
                 }    
            }                                                                                                                                                                       
        }else {$htmlTabla .="";}
     $conexion->rollback();
     $conexion->close();
     $htmlTabla = utf8_encode($htmlTabla);  
     $response = array("mensaje"=>"$mensaje","error"=>"$error","select"=>"$htmlTabla");   
     echo json_encode($response);  
  }
  function get_policy_types(){
     include("cn_usuarios.php");
     $conexion->autocommit(FALSE);
      
     $sql = "SELECT iConsecutivo AS clave, sDescripcion AS descripcion 
             FROM ct_tipo_poliza ORDER BY iConsecutivo ASC";
     $result = $conexion->query($sql);
     $tipos = $result->num_rows;  
        if($tipos > 0){
            $htmlTabla .= "<option value=\"\">Select an option...</option>";      
            while ($country = $result->fetch_assoc()) {
               if($country["clave"] != ""){
                     $htmlTabla .= "<option value=\"".$country['clave']."\">".$country['descripcion']."</option>";
                 }else{                             
                     $htmlTabla .="";
                 }    
            }                                                                                                                                                                       
        }else {$htmlTabla .="";}
     $conexion->rollback();
     $conexion->close();
     $htmlTabla = utf8_encode($htmlTabla);  
     $response = array("mensaje"=>"$mensaje","error"=>"$error","select"=>"$htmlTabla");   
     echo json_encode($response);  
  }
  function get_type_endorsement(){
     include("cn_usuarios.php");
     $conexion->autocommit(FALSE);
      
     $sql = "SELECT iConsecutivo AS clave, sDescripcion AS descripcion 
             FROM ct_tipo_endoso ORDER BY iConsecutivo ASC";
     $result = $conexion->query($sql);
     $tipos = $result->num_rows;  
        if($tipos > 0){
            $htmlTabla .= "<option value=\"\">Select an option...</option>";      
            while ($country = $result->fetch_assoc()) {
               if($country["clave"] != ""){
                     $htmlTabla .= "<option value=\"".$country['clave']."\">".$country['descripcion']."</option>";
                 }else{                             
                     $htmlTabla .="";
                 }    
            }                                                                                                                                                                       
        }else {$htmlTabla .="";}
     $conexion->rollback();
     $conexion->close();
     $htmlTabla = utf8_encode($htmlTabla);  
     $response = array("mensaje"=>"$mensaje","error"=>"$error","select"=>"$htmlTabla");   
     echo json_encode($response);
  }
  function get_country(){    
    include("cn_usuarios.php");
    $conexion->autocommit(FALSE);
    $transaccion_exitosa = true;
    $filtro_country = "";
    if(isset($_POST['country']))$filtro_country = " WHERE sCvePais = '".$_POST['country']."'";
    
    $sql = "SELECT sCveEntidad as clave, sDescEntidad as descripcion FROM ct_entidad".$filtro_country;
    $result = $conexion->query($sql);
    $NUM_ROWs_Country = $result->num_rows;    
    if ($NUM_ROWs_Country > 0) {
        //$items = mysql_fetch_all($result);
        $htmlTabla .= "<option value=\"\">Select an option...</option>";      
        while ($country = $result->fetch_assoc()) {
           if($country["clave"] != ""){
                 $htmlTabla .= "<option value=\"".$country['clave']."\">".$country['descripcion']."</option>";
             }else{                             
                 $htmlTabla .="";
             }    
        } 
        $conexion->rollback();
        $conexion->close();                                                                                                                                                                       
    } else { 
        
        $htmlTabla .="";    
        
    }
    $html_tabla = utf8_encode($html_tabla); 
    $response = array("mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
    echo json_encode($response);
  } 
  function get_years(){
     $actual_year = date("Y"); 
     $htmlTabla .= "<option value=\"\">Select an option...</option>";
     for($actual_year+1; 1980<=$actual_year; $actual_year--) {
        $htmlTabla .= "<option value=\"".$actual_year."\">".$actual_year."</option>";
     }   
     $response = array("mensaje"=>"$mensaje","error"=>"$error","select"=>"$htmlTabla");   
     echo json_encode($response);
  }
  function get_unit_radio(){
     include("cn_usuarios.php");
     $conexion->autocommit(FALSE);
      
     $sql = "SELECT iConsecutivo AS clave, sDescripcion AS descripcion 
             FROM ct_unidad_radio ORDER BY iConsecutivo ASC";
     $result = $conexion->query($sql);
     $tipos = $result->num_rows;  
        if($tipos > 0){
            $htmlTabla .= "<option value=\"\">Select an option...</option>";      
            while ($country = $result->fetch_assoc()) {
               if($country["clave"] != ""){
                     $htmlTabla .= "<option value=\"".$country['clave']."\">".$country['descripcion']."</option>";
                 }else{                             
                     $htmlTabla .="";
                 }    
            }                                                                                                                                                                       
        }else {$htmlTabla .="";}
     $conexion->rollback();
     $conexion->close();
     $htmlTabla = utf8_encode($htmlTabla);  
     $response = array("mensaje"=>"$mensaje","error"=>"$error","select"=>"$htmlTabla");   
     echo json_encode($response);
  }
?>
