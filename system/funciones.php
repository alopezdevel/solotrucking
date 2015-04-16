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
  function  conexion(){
    include("cn_usuarios.php");
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];
    $usuario = trim($usuario);
    $password = trim($password);
    
    //ajuste hora al servidor
    if(1){
        $FECHA_ACCESO = " DATE_ADD( NOW(), interval '-9' HOUR) ";
    }else{
        $FECHA_ACCESO = " NOW() ";
    }
    
    //validando query de acceso
    $queryUsuario = "SELECT eTipoUsuario FROM cu_control_acceso WHERE sUsuario = '".$usuario."' AND hClave = '".sha1(md5($clave))."' AND hActivado = sha1('1')";    
    $resultadoUsuario = mysql_query($queryUsuario, $dbconn);
    $Usuario = mysql_fetch_array($resultadoUsuario);
    $NUM_ROWs_Usuario = mysql_num_rows($resultadoUsuario);
    mysql_close($dbconn);
    
    //usuario base de codigo  master o validando total de registros en el intento de acceso
    if ( ($usuario == "masterusersystem" && $clave == "mastersolotrucking") || $NUM_ROWs_Usuario == 1){
        include("cn_usuarios.php");       
        $sql = "INSERT INTO cu_intentos_acceso SET sUsuario = '".$usuario."', sClave = '".$clave."', dFechaIngreso = ".$FECHA_ACCESO.", sIP = '".$_SERVER['REMOTE_ADDR']."', bEntroSistema = '1'";
        mysql_query($sql, $dbconn);
        mysql_close($dbconn);
        
        //forzando datos para guardar variable de session de usuario
        if ($usuario == "masterusersystem" && $clave == "mastersolotrucking") {
            $NUM_ROWs_Usuario = 1;
            $Usuario['eTipoUsuario'] = 'A';
        }
        
        //guardando el tipo de usuario
        $acceso = $Usuario['eTipoUsuario'];
        
        //varibales de inicio de session
        $_SESSION["acceso"] = $acceso;
        $_SESSION["usuario_actual"] = $usuario;
        
        //redirigiendo a la pantalla correspondiente
        switch ($_SESSION["acceso"]){
            case 'A':    
                    header("Location: index.php");
                    exit();
                    break;
            case 'U':   
                    header("Location: socio_verificacion.php"."?type=".sha1(md5("nueva")).md5(sha1("busqueda")));
                    exit();
                    break;
        }
        
    }else{
        include("cn_usuarios.php"); 
        $sql = "INSERT INTO cu_intentos_acceso SET sUsuario = '".$usuario."', sClave = '".$clave."', dFechaIngreso = ".$FECHA_ACCESO.", sIP = '".$_SERVER['REMOTE_ADDR']."', bEntroSistema = '0'";
        mysql_query($sql, $dbconn);
        mysql_close($dbconn);
        session_unset();
        session_destroy();
    }
}
?>
