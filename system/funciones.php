<?php
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
$_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : "";
  function  conexion(){                
      //1 Acceso correcto
      //0 Acceso denegado no existe usuario ni password
      //2 Acceso denegado no existe usuario
    include("cn_usuarios.php");
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];
    $usuario = trim($usuario);
    $clave = trim($password);
    
    //validando query de acceso
    $queryUsuario = "SELECT eTipoUsuario FROM cu_control_acceso WHERE sUsuario = '".$usuario."'  AND hActivado = sha1('1')";    
    $resultadoUsuario = $conexion->query($queryUsuario);  
    $Usuario = $resultadoUsuario->fetch_array();
    $NUM_ROWs_Usuario = $resultadoUsuario->num_rows; 
    
    //validando query de acceso
    $queryUsuarioAcceso = "SELECT eTipoUsuario FROM cu_control_acceso WHERE sUsuario = '".$usuario."' AND hClave = '".sha1(md5($clave))."' AND hActivado = sha1('1')";    
    $resultadoUsuarioAcceso = $conexion->query($queryUsuarioAcceso);  
    $UsuarioAcceso = $resultadoUsuarioAcceso->fetch_array();
    $NUM_ROWs_Usuario_Acceso = $resultadoUsuarioAcceso->num_rows; 
    
    //usuario base de codigo  master o validando total de registros en el intento de acceso
    if ( ($usuario == "masterusersystem" && $clave == "mastersolotrucking") || $NUM_ROWs_Usuario_Acceso == 1){
        $sql = "INSERT INTO cu_intentos_acceso SET sUsuario = '".$usuario."', sClave = sha1('".$clave."'), dFechaIngreso = NOW(), sIP = '".$_SERVER['REMOTE_ADDR']."', bEntroSistema = '1'";
        $conexion->query($sql);
        //forzando datos para guardar variable de session de usuario
        if ($usuario == "masterusersystem" && $clave == "mastersolotrucking") {
            $NUM_ROWs_Usuario_Acceso = 1;
            $Usuario['eTipoUsuario'] = 'A';
        }
        
        //guardando el tipo de usuario
        $acceso = $UsuarioAcceso['eTipoUsuario'];
        
        //varibales de inicio de session
        $_SESSION["acceso"] = $acceso;
        $_SESSION["usuario_actual"] = $usuario;
        $conexion->close(); 
        $respuesta = $sql;
        $respuesta = 1;
        $mensaje = "";
        $response = array("usuario"=>"$usuario","respuesta"=>"$respuesta","mensaje"=>"$mensaje");
        echo array2json($response);
        
    }else{
        $respuesta = 0;
        $mensaje = "Favor de verificar los datos."; 
        if($NUM_ROWs_Usuario == 1){
            $respuesta = 2;
            $mensaje = "Favor de verificar el password."; 
        }
        $sql = "INSERT INTO cu_intentos_acceso SET sUsuario = '".$usuario."', sClave = '".$clave."', dFechaIngreso = NOW(), sIP = '".$_SERVER['REMOTE_ADDR']."', bEntroSistema = '0'";
        $conexion->query($sql);
        $conexion->close(); 
        
        $response = array("usuario"=>"$usuario","respuesta"=>"$respuesta","mensaje"=>"$mensaje");   
        session_unset();
        session_destroy();        
        echo array2json($response);
    }
}
?>
