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
  
  //----------------Funciones para las Companias--------------//
  //Catalogo de Drivers:
  function get_drivers(){
     
    include("cn_usuarios.php");
    //$conexion->begin_transaction();
    $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
    $transaccion_exitosa = true;
    $sql = "SELECT ct_operadores.iConsecutivo as id, ct_operadores.sNombre AS nombre, DATE_FORMAT(ct_operadores.dFechaNacimiento,'%d %b %y') AS FechaNacimiento, ct_operadores.iNumLicencia AS NumLicencia, ct_operadores.iExperienciaYear AS Experiencia, DATE_FORMAT(ct_operadores.dFechaExpiracionLicencia,'%d %b %y') AS FechaExpiracion, ct_operadores.iNumLicencia, DATE_FORMAT(ct_operadores.dFechaContratacion,'%d %b %y') AS FechaContratacion, ct_entidad.sDescEntidad AS Entidad  FROM  ct_operadores LEFT JOIN   ct_entidad ON  ct_entidad.sCveEntidad = ct_operadores.iEntidad LEFT JOIN cu_control_acceso ON cu_control_acceso.iConsecutivo = ct_operadores.iCompania WHERE cu_control_acceso.sUsuario = '".$_SESSION['usuario_actual']."'";
    $result = $conexion->query($sql);
    $items = $result->num_rows;    
    if ($items > 0) {
              
        while ($drivers = $result->fetch_assoc()) { 
           if($drivers["id"] != ""){
                 
                 $htmlTabla .= "<tr>
                                    <td>".$drivers['id']."</td>".
                                   "<td>".$drivers['nombre']."</td>".
                                   "<td>".$drivers['FechaNacimiento']."</td>".
                                   "<td>".$drivers['NumLicencia']."</td>".
                                   "<td>".$drivers['FechaExpiracion']."</td>".
                                   "<td>".$drivers['Entidad']."</td>".
                                   "<td>".$drivers['Experiencia']."</td>".
                                   "<td>".$drivers['FechaContratacion']."</td>". 
                                   "<td> - - -</td>".                                                                                                                                                                                                                         
                                   "<td><div class=\"btn-icon edit btn-left\" title=\"Edit Driver\" onclick=\"\"><i class=\"fa fa-pencil-square-o\"></i> <span></span></div><div class=\"btn-icon trash btn-left\" title=\"Delete Driver\" onclick=\"\"><i class=\"fa fa-trash\"></i> <span></span></div></td></tr>";
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
?>
