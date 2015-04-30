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
    $queryUsuarioAcceso = "SELECT eTipoUsuario FROM cu_control_acceso WHERE sUsuario = '".$usuario."' AND hClave = sha1('".$clave."') AND hActivado = sha1('1')";    
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
  function alta_usuario(){
    $usuario = trim($_POST["email"]);
    $nombre = trim($_POST["name"]);
    $password = $_POST["password"];
    $tipo = strtoupper(trim($_POST["nivel"]));
    $correo = strtoupper(trim($_POST["email"]));
    include("cn_usuarios.php");
    //$conexion->begin_transaction();
    $conexion->autocommit(FALSE);
    $transaccion_exitosa = true;
    $sql = "SELECT sUsuario FROM cu_control_acceso WHERE sUsuario = '".$usuario."' LOCK IN SHARE MODE";
    $result = $conexion->query($sql);
    $NUM_ROWs_Usuario = $result->num_rows;
    if ($NUM_ROWs_Usuario > 0) {
        $mensaje = "El usuario: $usuario ya existe. Favor de verificar los datos.";
        $error = "1";
        $conexion->rollback();
        $conexion->close();                                                                                                                                                                       
    } else {     
        $sql = "INSERT INTO cu_control_acceso SET  sUsuario = '".$usuario."',   hClave =sha1('".$password."'), sCorreo ='".$correo."',eTipoUsuario ='".$tipo."', sDescripcion ='".$nombre."', hActivado  =sha1('1')  ";
        $conexion->query($sql);   
        if ($conexion->affected_rows < 1 ) {
            $error = "1";
        }                
        if ($transaccion_exitosa) {
            //Proceso para enviar correo                 
            require_once("./lib/mail.php");
            $cuerpo = "
                    <div style=\"font-size:12px;border:1px solid #6191df;border-radius:3px;padding:10px;width:95%; margin:5px auto;font-family: Arial, Helvetica, sans-serif;\">
                         <h2 style=\"color:#313131;text-transform: uppercase; text-align:center;\">Welcome tu Solo-Trucking Insurance!</h2> \n 
                         <p style=\"color:#5c5c5c;margin:5px auto; text-align:left;\"><strong>$correo</strong><br>Thank you for joining Solo-Trucking the best option to choose the most convenient for you insurance. Feel protected!</p>\n 
                         <br><br>
                         <p style=\"color:#5c5c5c;margin:5px auto; text-align:left;\">Then you remember your login to our system. Keep them in a safe place.</p>
                         <br><br>
                         <ul style=\"color:#010101;line-height:15px;\">
                            <li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">Login User: </strong>$correo</li>
                            <li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">Password: </strong>$password</li>
                         </ul>
                         <br><br>
                         <p style=\"color:#5c5c5c;margin:5px auto; text-align:left;\">If you agree the details are correct and you confirm by clicking the following button:</p><br>
                         <p style=\"margin:5px auto; text-align:center;\"><a href='AQUIELENLACE' style='color:#ffffff;background:#6191df;padding:5px 8px;border-radius:3px;-moz-border-radius:3px;-webkit-border-radius:3px;text-decoration:none;'>I agree and wish to confirm my account</a></p>
                         <br>
                         <p style=\"color:#5c5c5c;margin:5px auto; text-align:left;\">If you disagree just press on:</p>
                         <p style=\"margin:5px auto; text-align:center;\"><a href='AQUIELENLACE'' style='color:#ffffff;background:#8d0c0c;padding:5px 8px;border-radius:3px;-moz-border-radius:3px;-webkit-border-radius:3px;text-decoration:none;'>Cancel</a></p>
                    </div>";
             $mail = new Mail();
             $mail->From = "support@solotrucking.com";
             $mail->FromName = "solotrucking team";
             $mail->Host = "solotrucking.com";
             $mail->Mailer = "sendmail";
             $mail->Subject = "Your New Account "; 
             $mail->Body  = $cuerpo;
             $mail->ContentType ="Content-type: text/html; charset=iso-8859-1";
             $mail->IsHTML(true);
             $mail->WordWrap =150;
             $mail_error = false;
             $mail->AddAddress(trim($correo));
             if (!$mail->Send()) {
                $mail_error = true;
                $mail->ClearAddresses();
             }


            
            if(!$mail_error){
                $mensaje = "El usuario $usuario se registro con exito";
                $error = "0";
                $conexion->commit();
                $conexion->close();
            }else{
                $mensaje = "Error e-mail.";
                $error = "1";  
                $conexion->rollback();
                $conexion->close();           
            }
            
        } else {
            $mensaje = "Error al guardar los datos. Favor de verificarlos.";
            $error = "1";  
            $conexion->rollback();
            $conexion->close();           
        }
    }
     $response = array("mensaje"=>"$mensaje","error"=>"$error");   
     echo array2json($response);
}
  function get_clientusers(){   
  //error_reporting(E_ALL);
  //ini_set('display_errors', '1');
   
    include("cn_usuarios.php");
    //$conexion->begin_transaction();
    $conexion->autocommit(FALSE);
    $transaccion_exitosa = true;
    $sql = "SELECT iConsecutivo as id, sUsuario,hActivado,sDescripcion as nombre,sCorreo as correo FROM cu_control_acceso WHERE eTipoUsuario ='C' ";
    $result = $conexion->query($sql);
    $NUM_ROWs_Usuario = $result->num_rows;    
    if ($NUM_ROWs_Usuario > 0) {
        //$items = mysql_fetch_all($result);      
        while ($usuario = $result->fetch_assoc()) {
           if($usuario["sUsuario"] != ""){
                 $htmlTabla .= "<tr>
                                    <td>".$usuario['nombre']."</td>".
                                   "<td>".$usuario['correo']."</td>".
                                   "<td>".$usuario['nombre']."</td>".
                                   "<td>".$usuario['hActivado']."</td>".     
                                   "<td><div id='f_".$usuario['id']."' class=\"btn-icon ico-email-fwd\" title=\"Forward e-mail\"><span></span></div>
                                        <div ".'Onclick=" if (confirmarBorrar(\''.$usuario['nombre'].'\')) {    borrarUsuario(\''.$usuario['id'].'\')};" '. "   id='d_".$usuario['id']."' class=\"btn-icon ico-delete\" title=\"Delete Register\"><span></span></div></td>".  
                                "</tr>"   ;
             }else{                             
                 $htmlTabla .="<tr>
                                    <td>&nbsp;</td>".
                                   "<td>&nbsp;</td>".
                                   "<td>&nbsp;</td>".
                                   "<td>&nbsp;</td>".
                 "</tr>"   ;
             }    
        }
       // $htmlTabla .="<tr>
         //                           <td>&nbsp;</td>".
           //                        "<td>&nbsp;</td>".
             //                      "<td>&nbsp;</td>".
               //                    "<td>&nbsp;</td>".
                 //"</tr>"   ;
        
        $conexion->rollback();
        $conexion->close();                                                                                                                                                                       
    } else { 
    $htmlTabla .="<tr>
                                    <td>&nbsp;</td>".
                                   "<td>&nbsp;</td>".
                                   "<td>&nbsp;</td>".
                                   "<td>&nbsp;</td>".
                 "</tr>"   ;    
        
    }
     $response = array("mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
     echo array2json($response);
}   
  function get_certificate(){
       $insuredname = trim($_POST["insuredname"]);
       $email = trim($_POST["emailfax"]);
       $fax = trim($_POST["fax"]);
       $cholder = trim($_POST["cholder"]);
       $description = trim($_POST["description"]);  
       $error = "0";
       $mensaje = "";
       //Creando registro
                include("cn_usuarios.php");
                $conexion->autocommit(FALSE);
                $transaccion_exitosa = true;
                $sql = "INSERT INTO cb_certificate SET  sInnsuredName = '".$insuredname."',   email ='".$email."', sCholder ='".$cholder."', sDescription ='".$description."', dFechaIngreso = NOW(), sIP = '".$_SERVER['REMOTE_ADDR']."', dFechaActualizacion = NOW()  ";
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
                    $mensaje = "A general system error ocurred : internal error";
                    $error = "1";
                }
                if($transaccion_exitosa){
                    //Proceso para enviar correo                 
                    require_once("./lib/mail.php");
                        $cuerpo = "
                                <div style=\"font-size:13px;border:1px solid #6191df;border-radius:3px;padding:10px;width:95%; margin:5px auto;font-family: Arial, Helvetica, sans-serif;\">
                                     <h2 style=\"color:#313131;text-transform: uppercase; text-align:center;\">Certificate Request from the Website</h2> \n 
                                     <h3 style=\"color:#3c5284;text-transform: uppercase; text-align:center;\">www.solo-trucking.com</h3> \n
                                     <p style=\"color:#5c5c5c;margin:5px auto; text-align:left;\"><strong>$correo</strong><br>You've received a request to generate a new certificate:</p>\n 
                                     <br><br>
                                     <ul style=\"color:#010101;line-height:15px;\">
                                        <li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">Insured Name: </strong>$insuredname</li>
                                        <li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">E-mail or Fax: </strong>$email / $fax</li>
                                        <li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">Certificate Holder: </strong>$cholder</li>
                                        <li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">Description of Operations / Locations / Vehicles / Additional Remarks: </strong>$description</li>
                                     </ul>
                                     <br><br>
                                     <p style=\"color:#5c5c5c;margin:5px auto; text-align:center;\">Please follow up this customer request and send your file.</p><br>
                                </div>";
                         $mail = new Mail();
                         $mail->From = "support@solotrucking.com";
                         $mail->FromName = "solotrucking team";
                         $mail->Host = "solotrucking.com";
                         $mail->Mailer = "sendmail";
                         $mail->Subject = "Certificate Request from the Website "; 
                         $mail->Body  = $cuerpo;
                         $mail->ContentType ="Content-type: text/html; charset=iso-8859-1";
                         $mail->IsHTML(true);
                         $mail->WordWrap =150;
                         $mail_error = false;
                         $mail->AddAddress('alopez@globalpc.net');
                         if (!$mail->Send()) {
                            $mail_error = true;
                            $mail->ClearAddresses();
                         }


                        
                        if(!$mail_error){
                            $error = "0";
                        }else{              
                            $mensaje = "A general system error ocurred : e-mail error";
                            $error = "1";            
                        }
                }
                $response = array("mensaje"=>"$mensaje","error"=>"$error");   
                echo array2json($response);                       
    }    
  function borrar_cliente(){    
     $id = $_POST['id'];
     include("cn_usuarios.php");
     $conexion->autocommit(FALSE);
     $transaccion_exitosa = true;
     $sql = "DELETE FROM cu_control_acceso WHERE iConsecutivo = '".$id."'";
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
        $mensaje = "A general system error ocurred : internal error";
        $error = "1";
     }
}
  function get_request_certificate(){   
  //error_reporting(E_ALL);
  //ini_set('display_errors', '1');
   
    include("cn_usuarios.php");
    //$conexion->begin_transaction();
    $conexion->autocommit(FALSE);
    $transaccion_exitosa = true;
    $sql = "SELECT sInnsuredName as insuredname,email,sCholder as cholder, sDescription as description, eEstatus FROM cb_certificate ";
    $result = $conexion->query($sql);
    $NUM_ROWs_Certificates = $result->num_rows;    
    if ($NUM_ROWs_Certificates > 0) {
        //$items = mysql_fetch_all($result);      
        while ($certificates = $result->fetch_assoc()) {
           if($certificates["insuredname"] != ""){
                 $htmlTabla .= "<tr>
                                    <td>".$certificates['insuredname']."</td>".
                                   "<td>".$certificates['email']."</td>".
                                   "<td>".$certificates['cholder']."</td>".
                                   "<td>".$certificates['description']."</td>".  
                                   "<td>".$certificates['eEstatus']."</td>". 
                                   "<td><div class=\"btn-icon ico-email-fwd\" title=\"Send Certificate\"><span></span></div>".
                                   "<div class=\"btn-icon ico-delete\" title=\"Delete Request\"><span></span></div></td>".  
                                "</tr>"   ;
             }else{                             
                 $htmlTabla .="<tr>
                                    <td>&nbsp;</td>".
                                   "<td>&nbsp;</td>".
                                   "<td>&nbsp;</td>".
                                   "<td>&nbsp;</td>".
                 "</tr>"   ;
             }    
        }
       // $htmlTabla .="<tr>
         //                           <td>&nbsp;</td>".
           //                        "<td>&nbsp;</td>".
             //                      "<td>&nbsp;</td>".
               //                    "<td>&nbsp;</td>".
                 //"</tr>"   ;
        
        $conexion->rollback();
        $conexion->close();                                                                                                                                                                       
    } else { 
    $htmlTabla .="<tr>
                                    <td>&nbsp;</td>".
                                   "<td>&nbsp;</td>".
                                   "<td>&nbsp;</td>".
                                   "<td>&nbsp;</td>".
                 "</tr>"   ;    
        
    }
    $html_tabla = utf8_encode($html_tabla); 
     $response = array("mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
     echo array2json($response);
}     
?>
