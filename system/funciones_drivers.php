<?php 
session_start();
  function array2json($arr) { 
    if(function_exists('json_encode')) return json_encode($arr); //Lastest versions of PHP already has this functionality.
    $parts = array(); 
    $is_list = false; 

    //Find out if the given array is a numerical array 
    $keys = array_keys($arr); 
    $max_length = count($arr)-1;                                                                                       
    if(($keys[0] == 0) and ($keys[$max_length] == $max_length)) {//See if the first key is 0 and last key is length - 1 
        $is_list = true; 
        for($i=0; $i<count($keys); $i++) { //See if each key correspondes to its position 
            if($i != $keys[$i]) { //A key fails at position check. 
                $is_list = false; //It is an associative array. 
                break; 
            } 
        } 
    } 

    foreach($arr as $key=>$value) { 
        if(is_array($value)) { //Custom handling for arrays 
            if($is_list) $parts[] = array2json($value); /* :RECURSION: */ 
            else $parts[] = '"' . $key . '":' . array2json($value); /* :RECURSION: */ 
        } else { 
            $str = ''; 
            if(!$is_list) $str = '"' . $key . '":'; 

            //Custom handling for multiple data types 
            if(is_numeric($value)) $str .= $value; //Numbers 
            elseif($value === false) $str .= 'false'; //The booleans 
            elseif($value === true) $str .= 'true'; 
            else $str .= '"' . addslashes($value) . '"'; //All other things 
            // :TODO: Is there any more datatype we should be in the lookout for? (Object?) 

            $parts[] = $str; 
        } 
    } 
    $json = implode(',',$parts); 
     
    if($is_list) return '[' . $json . ']';//Return numerical JSON 
    return '{' . $json . '}';//Return associative JSON 
} 
  if($_POST["accion"] == ""){
    //$_POST["accion"] = "enviar_certificado";
}
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  //define('USER',$_SESSION['usuario_actual']); // Constante UserId 
  
  //----------------Funciones para las Companias--------------//
  //Catalogo de Drivers:
  function get_drivers(){
     
    include("cn_usuarios.php");
    //$conexion->begin_transaction();
    $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
    $transaccion_exitosa = true;

    //Filtros de informacion //
    $filtroQuery = " ";
    $array_filtros = explode(",",$_POST["filters"]);
    foreach($array_filtros as $key => $valor){
        if($array_filtros[$key] != ""){
            $campo_valor = explode("|",$array_filtros[$key]);
            if($campo_valor[0] == 'iConsecutivo'){ 
                $filtroQuery.= " AND  ".$campo_valor[0]."='".$campo_valor[1]."' ";
            }else{
                    $filtroQuery == "" ? $filtroQuery.= " AND  ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'": $filtroQuery.= " AND ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'";
            }

        }
    }
    
    $sql = "SELECT ct_operadores.iConsecutivo as id, ct_operadores.sNombre AS nombre, DATE_FORMAT(ct_operadores.dFechaNacimiento,'%d %b %y') AS FechaNacimiento, ct_operadores.iNumLicencia AS NumLicencia, ct_operadores.iExperienciaYear AS Experiencia, DATE_FORMAT(ct_operadores.dFechaExpiracionLicencia,'%d %b %y') AS FechaExpiracion, ct_operadores.iNumLicencia, DATE_FORMAT(ct_operadores.dFechaContratacion,'%d %b %y') AS FechaContratacion, ct_entidad.sDescEntidad AS Entidad, ct_entidad.sCvePais AS Country  FROM  ct_operadores LEFT JOIN   ct_entidad ON  ct_entidad.sCveEntidad = ct_operadores.iEntidad LEFT JOIN cu_control_acceso ON cu_control_acceso.iConsecutivo = ct_operadores.iCompania WHERE cu_control_acceso.sUsuario = '".$_SESSION['usuario_actual']."'".$filtroQuery;
    $result = $conexion->query($sql);
    $items = $result->num_rows;    
    if ($items > 0) {
              
        while ($drivers = $result->fetch_assoc()) { 
           if($drivers["id"] != ""){
                 
                 $htmlTabla .= "<tr>
                                    <td>".$drivers['NumLicencia']."</td>".
                                   "<td>".$drivers['nombre']."</td>".
                                   "<td>".$drivers['FechaNacimiento']."</td>".
                                   "<td>".$drivers['FechaExpiracion']."</td>".
                                   "<td>".$drivers['Entidad']."</td>".
                                   "<td>".$drivers['Country']."</td>".
                                   "<td>".$drivers['Experiencia']."</td>".
                                   "<td>".$drivers['FechaContratacion']."</td>". 
                                   "<td> - - -</td>".                                                                                                                                                                                                                         
                                   "<td>
                                        <div class=\"btn_edit btn-icon edit btn-left\" title=\"Edit Driver\" onclick=\"driver_edit(".$drivers['id'].");\"><i class=\"fa fa-pencil-square-o\"></i> <span></span></div>
                                        <div class=\"btn_delete btn-icon trash btn-left\" title=\"Delete Driver\" onclick=\"confirmarBorrar('".$drivers['nombre']."','".$drivers['id']."');\"><i class=\"fa fa-trash\"></i> <span></span></div>
                                   </td></tr>";
             }else{                                                                                                                                                                                                        
                
                 $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>"   ;
             }    
        }
    
        
        $conexion->rollback();
        $conexion->close();                                                                                                                                                                       
    } else { 
        
        $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>"   ;    
        
    }
     $response = array("mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
     echo array2json($response); 
  }
  //Save Driver:
  function save_driver(){
     
    $id = $_POST["id_driver"]; 
    //$id != "" ? $edit_mode = true : $edit_mode = false;
    if($id != ""){$edit_mode = TRUE;}else{$edit_mode = FALSE; }
    $Nombre = trim($_POST["sNombre"]); 
    //$Apellido = trim($_POST['sApellido']);
    $FDN = trim($_POST['dFechaNacimiento']);
    $NumLicencia = trim($_POST['iNumLicencia']);
    $FDE = trim($_POST['dFechaExp']);
    $Entidad = trim($_POST['iEntidad']);
    $ExpYear = trim($_POST['iExpYear']);
    $FDC = trim($_POST['dFechaContratacion']);
    $error = "0";
    $msj = "";
    $iCompania =  get_company_id();
    
    //Conexion:
    include("cn_usuarios.php");
    //$conexion->begin_transaction();
    $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
    $transaccion_exitosa = true;
    if($edit_mode) {
       $sql = "UPDATE ct_operadores SET  sNombre = '".$Nombre."', dFechaNacimiento = '".$FDN."', dFechaExpiracionLicencia = '".$FDE."', iExperienciaYear = ".$ExpYear.", iNumLicencia = ".$NumLicencia.", dFechaContratacion = '".$FDC."', iEntidad = '".$Entidad."', sIP = '".$_SERVER['REMOTE_ADDR']."', dFechaActualizacion = NOW() WHERE iConsecutivo = ".$id; 
    }else if($edit_mode == FALSE){   
       $sql = "INSERT INTO ct_operadores SET  sNombre = '".$Nombre."', dFechaNacimiento = '".$FDN."', dFechaExpiracionLicencia = '".$FDE."', iExperienciaYear = ".$ExpYear.", iNumLicencia = ".$NumLicencia.", dFechaContratacion = '".$FDC."', iEntidad = '".$Entidad."', iCompania = ".$iCompania.", dFechaIngreso = NOW(), sIP = '".$_SERVER['REMOTE_ADDR']."', dFechaActualizacion = NOW()  "; 
    }
    
    $conexion->query($sql);
        if ($conexion->affected_rows < 1 ) {$transaccion_exitosa =false;}
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
    //$_POST["dFechaLiberacion"] = date_create_from_format('d/m/Y', $_POST["dFechaLiberacion"])->format('Y-m-d');
    $response = array("error"=>"$error","msj"=>"$msj");
    echo json_encode($response);
  }
  //Detectar ID de la Compania:
  function get_company_id(){
    //Conexion:
    include("cn_usuarios.php");
    $sql = "SELECT iConsecutivo as id  FROM  cu_control_acceso WHERE sUsuario = '".$_SESSION['usuario_actual']."'";
    $result = $conexion->query($sql);
    $items = $result->num_rows;    
        if ($items > 0) {     
            while ($drivers = $result->fetch_assoc()) { 
               if($drivers["id"] != "") return $drivers["id"];
            }
        }
  }
  //load edit driver:
  function load_driver(){
      
        $error = '0';
        $msj = "";
        $fields = "";
        $id_driver = trim($_POST['id_driver']);
        include("cn_usuarios.php");
        //$conexion->begin_transaction();
        $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
        $transaccion_exitosa = true;
        $sql = "SELECT iConsecutivo, sNombre, dFechaNacimiento, dFechaExpiracionLicencia, iExperienciaYear, iNumLicencia, dFechaContratacion, iEntidad, iCompania FROM ct_operadores WHERE iConsecutivo = ".$id_driver;
        $result = $conexion->query($sql);
        $items = $result->num_rows;    
        if ($items > 0) {     
            $drivers = $result->fetch_assoc(); 
            if($drivers["iConsecutivo"] != ""){
                $fields .= "\$('#id_driver').val('".$drivers["iConsecutivo"]."');";
                $fields .= "\$('#sNombre').val('".$drivers["sNombre"]."');";
                $fields .= "\$('#dFechaNacimiento').val('".$drivers["dFechaNacimiento"]."');";
                $fields .= "\$('#dFechaExpiracionLicencia').val('".$drivers["dFechaExpiracionLicencia"]."');";
                $fields .= "\$('#iExperienciaYear').val('".$drivers["iExperienciaYear"]."');"; 
                $fields .= "\$('#iNumLicencia').val('".$drivers["iNumLicencia"]."');"; 
                $fields .= "\$('#dFechaContratacion').val('".$drivers["dFechaContratacion"]."');";                  
                //llenando campo para entidad:
                if($drivers["iEntidad"] != "")$country = get_pais($drivers["iEntidad"]);
                $fields .= "\$('#sCountry').val('".$country."');";
                //$fields .= "get_states('".$pais."');";  
                $fields .= "\$('#iEntidad').val('".$drivers["iEntidad"]."');";
                //$fields .= "\$('#iEntidad > option[value=\"".$drivers["iEntidad"]."\"]').attr('selected', 'selected');";
                $state = $drivers["iEntidad"];
            }
            $conexion->rollback();
            $conexion->close(); 
        }
        $response = array("msj"=>"$msj","error"=>"$error","fields"=>"$fields", "state" => "$state", "country" => "$country");   
        echo array2json($response);  
  }
  //delete driver:
  function delete_driver(){
      $id_driver = trim($_POST['id_driver']);
      $msj = "";
      $error = "0";
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);
      $transaccion_exitosa = true;
      $sql = "DELETE FROM ct_operadores WHERE iConsecutivo = '".$id_driver."'";
      $conexion->query($sql);   
         if ($conexion->affected_rows < 1 ) {
            $transaccion_exitosa =false;
         }
         if($transaccion_exitosa){
            $conexion->commit();
            $conexion->close();
         }else{
            $conexion->rollback();
            $conexion->close();
            $msj = "A general system error ocurred : internal error";
            $error = "1";
         }
      $response = array("msj"=>"$msj","error"=>"$error");   
      echo array2json($response); 
  }
  //traer pais:
  function get_pais($iEntidad){
      
     include("cn_usuarios.php");
     //$conexion->begin_transaction();
     $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
     $transaccion_exitosa = true;
     $sql = "SELECT sCvePais FROM ct_entidad WHERE sCveEntidad = '".$iEntidad."'";
     $result = $conexion->query($sql);
     $items = $result->num_rows;    
     if ($items > 0) {     
        $pais = $result->fetch_assoc(); 
     }
     $conexion->rollback();
     $conexion->close();
     return $pais["sCvePais"];
  }
?>
