<?php
  session_start();
  // Generic functions lib 
  include("functiones_genericas.php"); 
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId 
   
  function get_companies(){
     include("cn_usuarios.php");
     $conexion->autocommit(FALSE);
     $error = "0";   
     $sql   = "SELECT iConsecutivo AS clave, sNombreCompania AS descripcion 
              FROM ct_companias WHERE iDeleted='0' ORDER BY sNombreCompania ASC";
     $result= $conexion->query($sql);
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
  function get_services(){
     include("cn_usuarios.php");
     $conexion->autocommit(FALSE);
     $error = "0"; 
     $sql = "SELECT iConsecutivo AS clave, sClave ,sDescripcion AS descripcion 
             FROM ct_productos_servicios WHERE bEliminado = '0' ORDER BY iConsecutivo ASC";
     $result = $conexion->query($sql);
     $tipos = $result->num_rows;  
        if($tipos > 0){
            $htmlTabla .= "<option value=\"\">Select an option...</option>";      
            while ($item = $result->fetch_assoc()) {
               if($item["clave"] != ""){
                     $htmlTabla .= "<option value=\"".$item['clave']."\"> ".$item['sClave']." | ".$item['descripcion']."</option>";
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
  function get_financieras(){
     include("cn_usuarios.php");
     $conexion->autocommit(FALSE);
     $error = "0";   
     $sql = "SELECT iConsecutivo AS clave, sName AS descripcion 
             FROM ct_financieras ORDER BY iConsecutivo ASC";
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
  function get_financing_insurances(){
     include("cn_usuarios.php");
     $conexion->autocommit(FALSE);
      
     $sql = "SELECT iConsecutivo AS clave, sName AS descripcion 
             FROM ct_insurance_premium_financing ORDER BY iConsecutivo ASC";
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
      
     $actual_year = date("Y")+1; 
     $htmlTabla  .= "<option value=\"\">Select an option...</option>";
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
             FROM ct_unidad_radio ORDER BY sDescripcion ASC";
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
  function get_insurances(){
     include("cn_usuarios.php");
     $conexion->autocommit(FALSE);
     $htmlTabla .= "<option value=\"\">Select an option...</option>";  
     $sql = "SELECT iConsecutivo AS clave, sName AS descripcion 
             FROM ct_aseguranzas ORDER BY sName ASC";
     $result = $conexion->query($sql);
     $tipos = $result->num_rows;  
        if($tipos > 0){     
            while ($items = $result->fetch_assoc()) {
               if($items["clave"] != ""){
                     $htmlTabla .= "<option value=\"".$items['clave']."\">".utf8_decode($items['descripcion'])."</option>";
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
  function get_claims_types(){
     include("cn_usuarios.php");
     $conexion->autocommit(FALSE);
      
     $sql = "SELECT iConsecutivo AS clave, sNombre AS descripcion, sDescripcion 
             FROM ct_tipo_claim ORDER BY iConsecutivo ASC";
     $result = $conexion->query($sql);
     $tipos = $result->num_rows;  
        if($tipos > 0){
            $htmlTabla .= "<option value=\"\">Select an option...</option>";      
            while ($options = $result->fetch_assoc()) {
               if($options["clave"] != ""){
                     $htmlTabla .= "<option value=\"".$options['clave']."\" title=\"".$options['sDescripcion']."\">".$options['descripcion']."</option>";
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
  function get_claims_incident(){
      include("cn_usuarios.php");
     $conexion->autocommit(FALSE);
      
     $sql = "SELECT iConsecutivo AS clave, sNombre AS descripcion 
             FROM ct_tipo_incidente_claim ORDER BY iConsecutivo ASC";
     $result = $conexion->query($sql);
     $tipos = $result->num_rows;  
        if($tipos > 0){
            $htmlTabla .= "<option value=\"\">Select an option...</option>";      
            while ($options = $result->fetch_assoc()) {
               if($options["clave"] != ""){
                     $htmlTabla .= "<option value=\"".$options['clave']."\">".$options['descripcion']."</option>";
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
  function get_commodities(){
     include("cn_usuarios.php");
     $conexion->autocommit(FALSE);
     $error = "0"; 
     $htmlList = "";  
     $sql = "SELECT iConsecutivo AS clave, sCommodities AS descripcion FROM ct_quotes_default_commodities ORDER BY sCommodities ASC";
     $result = $conexion->query($sql);
     $tipos = $result->num_rows;  
     if($tipos > 0){
        $htmlTabla .= "<option value=\"\">Select an option...</option>";      
        while ($items = $result->fetch_assoc()){
           if($items["clave"] != ""){
                 $htmlTabla .= "<option value=\"".$items['clave']."\">".utf8_decode($items['descripcion'])."</option>";
                 if($_POST['table_list'] == 'true'){
                    $htmlList .= "<tr>".
                                   "<td><input class=\"list_id\" name=\"list_id\" type=\"checkbox\" value=\"".$items['clave']."\" title=\"".strtoupper(utf8_decode($items['descripcion']))."\"></td>".
                                   "<td>".strtoupper(utf8_decode($items['descripcion']))."</td>".                                                                                                                                                                                                                  
                                   "</tr>";
                 }
             }else{                             
                 $htmlTabla .="";
             }    
        }                                                                                                                                                                       
     }else {$htmlTabla .="";}
     $conexion->rollback();
     $conexion->close();
     $htmlTabla = utf8_encode($htmlTabla);  
     $response = array("mensaje"=>"$mensaje","error"=>"$error","select"=>"$htmlTabla","table"=>"$htmlList");   
     echo json_encode($response);  
  }
  function get_policies(){
     
     include("cn_usuarios.php");
     $conexion->autocommit(FALSE);
     
     $iConsecutivoCompania = trim($_POST['iConsecutivoCompania']);
     $htmlTabla            = "<option value=\"\">Select an option...</option>"; 
     $error                = 0;
     $mensaje              = "";
      
     $sql    = "SELECT A.iConsecutivo AS clave, A.sNumeroPoliza AS descripcion, B.sName AS sBroker, C.sDescripcion AS sTipoPoliza, C.sAlias ".
               "FROM      ct_polizas     AS A ".
               "LEFT JOIN ct_brokers     AS B ON A.iConsecutivoBrokers = B.iConsecutivo ".
               "LEFT JOIN ct_tipo_poliza AS C ON A.iTipoPoliza = C.iConsecutivo ".
               "WHERE A.iConsecutivoCompania='$iConsecutivoCompania' AND A.iDeleted = '0' AND A.dFechaCaducidad >= CURDATE() ORDER BY A.iConsecutivo ASC";
     $result = $conexion->query($sql);
     $rows   = $result->num_rows;  
     if($rows > 0){
        while ($items = $result->fetch_assoc()) {
            $items['sAlias'] = strlen($items['sAlias']) == 2 ? "(".$items['sAlias'].") " : "(".$items['sAlias'].")";
            $htmlTabla .= "<option value=\"".$items['clave']."\">".$items['sAlias']." | ".$items['descripcion']." | ".$items['sBroker']."</option>";
        }                                                                                                                                                                       
     }else{$error = 1;$mensaje = "The company does not have configured any policy, please verify it.";}
     $conexion->rollback();
     $conexion->close();
     $htmlTabla = utf8_encode($htmlTabla);  
     $response = array("mensaje"=>"$mensaje","error"=>"$error","select"=>"$htmlTabla");   
     echo json_encode($response);  
  }
?>
