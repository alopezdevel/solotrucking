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
    $queryUsuario = "SELECT eTipoUsuario FROM cu_control_acceso WHERE sUsuario = '".$usuario."'  AND hActivado in ( '1','2') ";    
    $resultadoUsuario = $conexion->query($queryUsuario);  
    $Usuario = $resultadoUsuario->fetch_array();
    $NUM_ROWs_Usuario = $resultadoUsuario->num_rows; 
    
    //validando query de acceso
    $queryUsuarioAcceso = "SELECT eTipoUsuario FROM cu_control_acceso WHERE sUsuario = '".$usuario."' AND hClave = sha1('".$clave."') AND hActivado in ( '1','2') ";    
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
    function generaPass(){
        $cadena = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890";
        $longitudCadena=strlen($cadena);     
        $pass = "";
        $longitudPass=10;
        for($i=1 ; $i<=$longitudPass ; $i++){
            $pos=rand(0,$longitudCadena-1);             
            $pass .= substr($cadena,$pos,1);
        }
        return $pass;
    }  
    $codigo1 = generaPass();
    $codigo2 = substr( md5(microtime()), 1, 8).$codigo1.substr( md5(microtime()), 1, 5);
    $codigoconfirm = $codigo1.$codigo2;
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
        $mensaje = "Error: $usuario already exist.";
        $error = "1";
        $conexion->rollback();
        $conexion->close();                                                                                                                                                                       
    } else {                        
        $sql = "INSERT INTO cu_control_acceso SET  sUsuario = '".$usuario."',   hClave =sha1('".$password."'), sCorreo ='".$correo."',eTipoUsuario ='".$tipo."', sDescripcion ='".$nombre."', hActivado  ='0', sCodigoVal = '".$codigoconfirm."'  ";
        $conexion->query($sql);   
        if ($conexion->affected_rows < 1 ) {
            $error = "1";
        }  
             
         $ruta = "solotrucking.laredo2.net/system/confirm_mail_user.php?cuser=$codigoconfirm";
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
                         <p style=\"margin:5px auto; text-align:center;\"><a href='$ruta' style='color:#ffffff;background:#6191df;padding:5px 8px;border-radius:3px;-moz-border-radius:3px;-webkit-border-radius:3px;text-decoration:none;'>I agree and wish to confirm my account</a></p>
                         <br>
                         <p style=\"color:#5c5c5c;margin:5px auto; text-align:left;\">If you disagree just press on:</p>
                         <p style=\"margin:5px auto; text-align:center;\"><a href='solotrucking.laredo2.net' style='color:#ffffff;background:#8d0c0c;padding:5px 8px;border-radius:3px;-moz-border-radius:3px;-webkit-border-radius:3px;text-decoration:none;'>Cancel</a></p>
                    </div>";
             $mail = new Mail();                                    
             $mail->From = "supportteamo@solo-trucking.com";
             $mail->FromName = "solo-trucking team";
             $mail->Host = "solo-trucking.com";
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
    $sql = "SELECT iConsecutivo as id, sUsuario,CASE WHEN hActivado = '0' then 'Pending' Else 'Confirmed' END AS  hActivado,sDescripcion as nombre,sCorreo as correo FROM cu_control_acceso WHERE eTipoUsuario ='C' ";
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
                                   "<td><div ".'Onclick=" if (confirmarBorrar(\''.$usuario['nombre'].'\',\''.$usuario['id'].'\')) {    borrarClient(\''.$usuario['id'].'\')};" '. "   id='f_".$usuario['id']."' class=\"btn-icon ico-delete\" title=\"Forward e-mail\"><span></span></div>
                                   </td>".  
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
                         $mail->From = "support@solo-trucking.com";
                         $mail->FromName = "solo-trucking team";
                         $mail->Host = "solo-trucking.com";
                         $mail->Mailer = "sendmail";
                         $mail->Subject = "Certificate Request from the Website "; 
                         $mail->Body  = $cuerpo;
                         $mail->ContentType ="Content-type: text/html; charset=iso-8859-1";
                         $mail->IsHTML(true);
                         $mail->WordWrap =150;
                         $mail_error = false;
                         $mail->AddAddress('sanchezmdesign@gmail.com, 19564674440@messages.efax.com');
                         //$mail->AddAddress('alopez@globalpc.net');
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
    include("cn_usuarios.php");
    //$conexion->begin_transaction();
    $conexion->autocommit(FALSE);
    $transaccion_exitosa = true;
    $array_filtros = explode(",*",$_POST["filtroInformacion"]); 
    $filtroQuery .= " WHERE cb_certificate.iConsecutivo != '' ";  
    foreach($array_filtros as $key => $valor){
        if($array_filtros[$key] != ""){
            $campo_valor = explode("|",$array_filtros[$key]);
            if ($campo_valor[0] =='iConsecutivo'){
                    $filtroQuery.= " AND  ".$campo_valor[0]."='".$campo_valor[1]."' ";
            }else{
                    $filtroQuery == "" ? $filtroQuery.= " AND  ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'": $filtroQuery.= " AND ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'";
            }
        }
    }
    
    $sql = "SELECT iConsecutivo as folio,
            sInnsuredName as insuredname,
            email,
            sCholder as cholder, 
            sDescription as description,
            CASE WHEN  eEstatus = '0' THEN 'IN PROCESS'  ELSE 'COMPLETED' END  as eEstatus,
            DATE_FORMAT(dFechaIngreso,  '%m/%d/%Y')    as dFechaIngreso, 
            DATE_FORMAT(dFechaArchivo,  '%m/%d/%Y')    as dFechaArchivo 
            FROM cb_certificate ".$filtroQuery;
    $result = $conexion->query($sql);
    $NUM_ROWs_Certificates = $result->num_rows;    
    if ($NUM_ROWs_Certificates > 0) {
        //$items = mysql_fetch_all($result);      
        while ($certificates = $result->fetch_assoc()) {
           if($certificates["insuredname"] != ""){
                 $htmlTabla .= "<tr>                            
                                    <td>".$certificates['dFechaIngreso']."</td>".
                                   "<td>".$certificates['insuredname']."</td>".
                                   "<td>".$certificates['email']."</td>".
                                   "<td>".$certificates['cholder']."</td>".
                                   "<td >".$certificates['description']."</td>".  
                                   "<td nowrap='nowrap'>".$certificates['eEstatus']."</td>". 
                                   "<td nowrap='nowrap'>".$certificates['dFechaArchivo']."</td>". 
                                   "<td nowrap='nowrap' ><div id= 'boton_uploadFile' onclick='onAbrirDialog(\"".$certificates['folio']."\",\"".$certificates['email']."\" );' class=\"btn-icon ico-email-fwd\" title=\"Send Certificate\"><span></span></div>".
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
     $response = array("mensaje"=>"$sql","error"=>"$error","tabla"=>"$htmlTabla");   
     echo array2json($response);
}  
  function get_country(){   
  //error_reporting(E_ALL);
  //ini_set('display_errors', '1');
   
    include("cn_usuarios.php");
    //$conexion->begin_transaction();
    $conexion->autocommit(FALSE);
    $transaccion_exitosa = true;
    $sql = "SELECT sCveEntidad as clave, sDescEntidad as descripcion FROM ct_entidad";
    $result = $conexion->query($sql);
    $NUM_ROWs_Country = $result->num_rows;    
    if ($NUM_ROWs_Country > 0) {
        //$items = mysql_fetch_all($result);      
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
     echo array2json($response);
}    
  function add_company(){
       $userid = trim($_POST["userid"]);
       $address = trim($_POST["address"]);
       $city = trim($_POST["city"]);
       $zipcode = trim($_POST["zipcode"]); 
       $country = trim($_POST["country"]);  
       $phone = trim($_POST["phone"]); 
       $usdot = trim($_POST["usdot"]);   
        
       $error = "0";
       $mensaje = "";
    //VERIFICANDO REGISTRO DE USUARIO
    include("cn_usuarios.php");
    //$conexion->begin_transaction();
    $conexion->autocommit(FALSE);
    $transaccion_exitosa = true;
    $sql = "SELECT iConsecutivo FROM cu_control_acceso WHERE iConsecutivo = '".$userid."' LOCK IN SHARE MODE";
    $result = $conexion->query($sql);
    $NUM_ROWs_Usuario = $result->num_rows;
    if ($NUM_ROWs_Usuario > 0) {
        
        $sql = "INSERT INTO ct_companias SET  sDireccion = '".$address."', sCiudad = '".$city."', sEstado = '".$country."', sCodigoPostal = '".$zipcode."', sTelefonoPrincipal = '".$phone."', sUsdot = '".$usdot."', iConsecutivoAcceso = '".$userid."'";
        $conexion->query($sql);   
        if ($conexion->affected_rows < 1 ) {
            $error = "1";
            $mensaje= "Internal Error, Failed to verify the account. Please report to Administrator";
        } 
        $sql = "UPDATE cu_control_acceso SET hActivado = '2' WHERE iConsecutivo = '".$userid."'  ";
        $conexion->query($sql);   
        if ($conexion->affected_rows < 1 ) {
            $error = "1";
            $mensaje= "Internal Error, Failed to verify the account. Please report to Administrator";
        }                
        if ($transaccion_exitosa) {
            
            $mensaje = "Your information has been successfully registered.";
            $error = "0";
            $conexion->commit();
            $conexion->close();
            
        } else {
            $mensaje = "Error: Failed to save the information. Please verify..";
            $error = "1";  
            $conexion->rollback();
            $conexion->close();           
        }
                                                                                                                                                                              
    } else {     
        
        $mensaje = "Error: The user does not exist.";
        $error = "1";
        $conexion->rollback();
        $conexion->close(); 
    }
     $response = array("mensaje"=>"$mensaje","error"=>"$error");   
     echo array2json($response);
                       
} 
  function confirm_user(){
      $code = trim($_POST["code"]);
      include("cn_usuarios.php");
      //$conexion->begin_transaction();
      $conexion->autocommit(FALSE);
      $transaccion_exitosa = true;
      $error = "0";
      $sql = "SELECT sUsuario, hActivado, iConsecutivo, sDescripcion FROM cu_control_acceso WHERE sCodigoVal = '".$code."'  LOCK IN SHARE MODE";
      $result = $conexion->query($sql);
      $NUM_ROWs_Usuario = $result->num_rows;
      if ($NUM_ROWs_Usuario > 0) {
           while ($usuario = $result->fetch_assoc()) {
               if($usuario['hActivado']  == "0"){
                   $sql = "UPDATE cu_control_acceso SET  hActivado = '1' WHERE sCodigoVal = '".$code."'";
                   $conexion->query($sql);   
                   if ($conexion->affected_rows < 1 ) {
                        $error = "1";
                        $mensaje = "A general system error ocurred : internal error";                        
                   }else{
                       $conexion->commit();
                       $mensaje = "1"; //correct
                       $usuario = $usuario['sDescripcion'];
                       $correo =  $usuario['sUsuario'];
                   }
                   
               }else{                     
                   $mensaje = "Error: your code confirmation has expired ";
                   $error = "2";
               }
               
           }
          
      }else{
          $mensaje = "Error: user does not exist";
          $error = "1";
      }
      $conexion->close();
      
      $response = array("mensaje"=>"$mensaje","error"=>"$error");   
      echo array2json($response);
      
  }
  function validar_cliente_acceso(){
      $correo = trim($_POST["usuario"]);
      include("cn_usuarios.php");
      //$conexion->begin_transaction();
      $estatus = "";
      $usuario = "";
      $consecutivo = "";
      $descripcion = "";
      $conexion->autocommit(FALSE);
      $transaccion_exitosa = true;
      $error = "0";
      $sql = "SELECT sUsuario, hActivado, iConsecutivo, sCodigoVal  FROM cu_control_acceso WHERE sUsuario = '".$correo."' AND eTipoUsuario = 'C'   LOCK IN SHARE MODE";
      $result = $conexion->query($sql);
      $NUM_ROWs_Usuario = $result->num_rows;
      if ($NUM_ROWs_Usuario > 0) {
           while ($usuario = $result->fetch_assoc()) {
               $estatus = $usuario['hActivado'];   
               $user = $usuario['sUsuario'];
               $consecutivo = $usuario['iConsecutivo'];  
               $descripcion = $usuario['sDescripcion'];             
               $codigo = $usuario['sCodigoVal'];             
              
           }
          
      }else{
          $mensaje = "Error: user does not exist";
          $error = "1";
      }
      $conexion->close();                                                                                                                           
      
      $response = array("mensaje"=>"$mensaje","error"=>"$error","consecutivo"=>"$consecutivo","estatus"=>"$estatus","codigo"=>"$codigo");   
      echo array2json($response);
      
  }
  function get_usuario(){
      $correo = trim($_POST["usuario"]);
      $id = trim($_POST["id"]);
      include("cn_usuarios.php");
      //$conexion->begin_transaction();
      $estatus = "";
      $usuario = "";
      $consecutivo = "";
      $descripcion = "";
      $conexion->autocommit(FALSE);
      $transaccion_exitosa = true;
      $error = "0";
      $sql = "SELECT sUsuario as correo, sDescripcion as 'user', hActivado as estatus  FROM cu_control_acceso  WHERE sUsuario = '".$correo."' AND eTipoUsuario = 'C' AND iConsecutivo = '".$id."'  LOCK IN SHARE MODE";
      $result = $conexion->query($sql);
      $NUM_ROWs_Usuario = $result->num_rows;
      if ($NUM_ROWs_Usuario > 0) {
          
           while ($usuario = $result->fetch_assoc()) {
               $correo = $usuario['correo'];   
               $user = $usuario['user'];              
               $estatus = $usuario['estatus'];
               $direccion = "";
               $ciudad = "";
               $estado = "";                  
               $codigo_postal = ""; 
               $telefono_principal = "";
               $usdot = "";              
              
           }
          
      }else{
          $mensaje = "Error: user does not exist";
          $error = "1";
      }
      if($estatus == "2" && $error == "0"){
          $sql = "";
          $sql = "SELECT sUsuario as correo, 
                         sDescripcion as 'user',
                         hActivado as estatus_actual,
                         ct_companias.sDireccion as 'direccion',    
                         ct_companias.sCiudad as 'ciudad',    
                         ct_companias.sEstado as 'estado',    
                         ct_companias.sCodigoPostal as 'codigo_postal',    
                         ct_companias.sTelefonoPrincipal as 'telefono_principal',    
                         ct_companias.sUsdot as 'usdot'    
                  FROM cu_control_acceso LEFT JOIN  ct_companias ON  cu_control_acceso.iConsecutivo = ct_companias.iConsecutivoAcceso WHERE sUsuario = '".$correo."' AND eTipoUsuario = 'C' AND cu_control_acceso.iConsecutivo = '".$id."'  LOCK IN SHARE MODE";
          $resultGeneral = $conexion->query($sql);
          $NUM_ROWs_Usuario_General = $resultGeneral->num_rows;
          if ($NUM_ROWs_Usuario_General > 0) {   
          $usuario = NULL;    
           while ($usuario = $resultGeneral->fetch_assoc()) {
               $correo = $usuario['correo'];   
               $user = $usuario['user'];              
               $estatus = $usuario['estatus_actual'];  
               //compania
               $direccion = $usuario['direccion'];
               $ciudad = $usuario['ciudad'];
               $estado = $usuario['estado'];                  
               $codigo_postal = $usuario['codigo_postal']; 
               $telefono_principal = $usuario['telefono_principal'];
               $usdot = $usuario['usdot'];                                      
           }          
          }else{
            $mensaje = "Error: user does not exist";
            $error = "1";
          }
      }
      
      $conexion->close();                                                                                                                           
      
      $response = array("mensaje"=>"$mensaje",
                        "error"=>"$error",
                        "correo"=>"$correo",
                        "user"=>"$user",
                        "estatus"=>"$estatus",
                        "direccion"=>"$direccion",
                        "ciudad"=>"$ciudad",
                        "estado"=>"$estado",
                        "codigo_postal"=>"$codigo_postal",
                        "telefono_principal"=>"$telefono_principal",
                        "usdot"=>"$usdot",
                        );   
      echo array2json($response);
      
  }
  function update_company(){
       $userid = trim($_POST["userid"]);
       $address = trim($_POST["address"]);
       $city = trim($_POST["city"]);
       $zipcode = trim($_POST["zipcode"]); 
       $country = trim($_POST["country"]);  
       $phone = trim($_POST["phone"]); 
       $usdot = trim($_POST["usdot"]);   
        
       $error = "0";
       $mensaje = "";
    //VERIFICANDO REGISTRO DE USUARIO
    include("cn_usuarios.php");
    //$conexion->begin_transaction();
    $conexion->autocommit(FALSE);
    $transaccion_exitosa = true;
    $sql = "SELECT iConsecutivo FROM cu_control_acceso WHERE iConsecutivo = '".$userid."' LOCK IN SHARE MODE";
    $result = $conexion->query($sql);
    $NUM_ROWs_Usuario = $result->num_rows;
    if ($NUM_ROWs_Usuario > 0) {
        
        $sql = "UPDATE ct_companias SET  sDireccion = '".$address."', sCiudad = '".$city."', sEstado = '".$country."', sCodigoPostal = '".$zipcode."', sTelefonoPrincipal = '".$phone."', sUsdot = '".$usdot."' WHERE iConsecutivoAcceso = '".$userid."'  ";
        $conexion->query($sql);   
        if ($conexion->affected_rows < 1 ) {
            $error = "1";
            $mensaje= "Internal Error, Failed to verify the account. Please report to Administrator";
        }                
        if ($transaccion_exitosa) {
            
            $mensaje = "Your information has been successfully registered.";
            $error = "0";
            $conexion->commit();
            $conexion->close();
            
        } else {
            $mensaje = "Error: Failed to save the information. Please verify..";
            $error = "1";  
            $conexion->rollback();
            $conexion->close();           
        }
                                                                                                                                                                              
    } else {     
        
        $mensaje = "Error: The user does not exist.";
        $error = "1";
        $conexion->rollback();
        $conexion->close(); 
    }
     $response = array("mensaje"=>"$mensaje","error"=>"$error");   
     echo array2json($response);
                       
} 
  function enviar_certificado(){        
      //VERIFICANDO REGISTRO DE USUARIO
    include("cn_usuarios.php");
    //$conexion->begin_transaction();
    $id= $_POST['idCertificate'];
    $conexion->autocommit(FALSE);
    $transaccion_exitosa = true;
    $sql = "UPDATE cb_certificate SET eEstatus = '1', dFechaArchivo = NOW() WHERE iConsecutivo = '".$id."'  ";
    $conexion->query($sql);   
    if ($conexion->affected_rows < 1 ) {
        $error = "1";
        $mensaje= "Internal Error, Failed to verify the account. Please report to Administrator";
        $transaccion_exitosa = false;
    }  
    if ($transaccion_exitosa) {    
            $mensaje = "Your information has been successfully registered.";
            $error = "0";
            $conexion->commit();
            $conexion->close();
            
        } else {
            $mensaje = "Error: Failed to save the information. Please verify..";
            $error = "1";  
            $conexion->rollback();
            $conexion->close();           
        }
    if($transaccion_exitosa){
          //Almacenando los valores recibidos
        $sAsunto = "solo-trucking team - CERTIFICATE";
        $sPara   = $_POST['para'];
        $mensaje = $_POST['mensaje'];
        $sDe     = "solo-trucking team";
                    

        $bHayFicheros = 0;
        $sCabeceraTexto = "";
        $sAdjuntos = "";

        if ($sDe)$sCabeceras = "From:".$sDe."\n";
        else $sCabeceras = "";
        $sCabeceras .= "MIME-version: 1.0\n";
        $sTexto =  "
                            <div style=\"font-size:12px;border:1px solid #6191df;border-radius:3px;padding:10px;width:95%; margin:5px auto;font-family: Arial, Helvetica, sans-serif;\">
                                 <h2 style=\"color:#313131;text-transform: uppercase; text-align:center;\">Solo-Trucking Insurance</h2> \n 
                                 <p style=\"color:#5c5c5c;margin:5px auto; text-align:left;\">His request has already been processed, attached to this email we send the certificate file.</p>\n 
                                 <p>".$mensaje."</p>
                                 <br><br> 
                                 <p style=\"color:#5c5c5c;margin:5px auto; text-align:left;\">Thank you</p>
                            </div>";


        if ($bHayFicheros == 0)
        {
        $bHayFicheros = 1;
        $sCabeceras .= "Content-type: multipart/mixed;";
        $sCabeceras .= "boundary=\"--_Separador-de-mensajes_--\"\n";

        $sCabeceraTexto = "----_Separador-de-mensajes_--\n";
        $sCabeceraTexto .= "Content-type: text/html; text/plain;charset=iso-8859-1\n";
        $sCabeceraTexto .= "Content-transfer-encoding: 7BIT\n";

        $sTexto = $sCabeceraTexto.$sTexto;
        }
        if ($_FILES['adjunto']['size'] > 0)
        {
        $sAdjuntos .= "\n\n----_Separador-de-mensajes_--\n";
        $sAdjuntos .= "Content-type: ".$_FILES['adjunto']['type'].";name=\"".$_FILES['adjunto']['name']."\"\n";;
        $sAdjuntos .= "Content-Transfer-Encoding: BASE64\n";
        $sAdjuntos .= "Content-disposition: attachment;filename=\"".$_FILES['adjunto']['name']."\"\n\n";

        $oFichero = fopen($_FILES['adjunto']["tmp_name"], 'r');
        $sContenido = fread($oFichero, filesize($_FILES['adjunto']["tmp_name"]));
        $sAdjuntos .= chunk_split(base64_encode($sContenido));
        fclose($oFichero);
        }

        if ($bHayFicheros)
        $sTexto .= $sAdjuntos."\n\n----_Separador-de-mensajes_----\n";

        @mail($sPara, $sAsunto,$sTexto, $sCabeceras);
    }
  }
  function get_company(){   
    include("cn_usuarios.php");
    //$conexion->begin_transaction();
    $conexion->autocommit(FALSE);                                                                                                                                                                                                                 
    $transaccion_exitosa = true;
    $sql = "SELECT ct_companias.iConsecutivo as id, sUsuario, eEstatusCertificadoUpload as estatus_upload,CASE WHEN eEstatusCertificadoUpload = '0' then 'Pending' Else 'Loaded' END AS  hActivado,sDescripcion as nombre,cu_control_acceso.sUsuario as correo FROM cu_control_acceso LEFT JOIN  ct_companias ON  cu_control_acceso.iConsecutivo = ct_companias.iConsecutivoAcceso WHERE eTipoUsuario ='C' ";
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
                                   "<td>".$usuario['hActivado']."</td>";  
                                   if($usuario['estatus_upload'] == "0"){   
                                   $htmlTabla = $htmlTabla."<td nowrap='nowrap' ><div id= 'boton_uploadFile' onclick='onAbrirDialog(\"".$usuario['id']."\",\"".$usuario['correo']."\" );' class=\"btn-icon ico-email-fwd\" title=\"Upload Certificate\"><span></span></div>
                                   </td>";
                                   }
                                   if($usuario['estatus_upload'] == "1"){   
                                   $htmlTabla = $htmlTabla.    "<td nowrap='nowrap'  > <div>&nbsp; </div></td>";
                                   }
                                                                                                                                                                                                                                            
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
  function subir_certificado(){        
      //VERIFICANDO REGISTRO DE USUARIO
    include("cn_usuarios.php");
    //$conexion->begin_transaction();
    $id= $_POST['idCertificate'];
    $conexion->autocommit(FALSE);
    $transaccion_exitosa = true;       
    $oFichero = fopen($_FILES['adjunto']["tmp_name"], 'r'); 
    $sContenido = fread($oFichero, filesize($_FILES['adjunto']["tmp_name"]));  
    $sContenido =  $conexion->real_escape_string($sContenido);
    //$contenido = "";
    //$_FILES['adjunto']["tmp_name"]    
    $sql_imagen = ", sNombreArchivo = '".$_FILES['adjunto']["name"]."', sTipoArchivo = '".$_FILES['adjunto']["type"]."', iTamanioArchivo = '".$_FILES['adjunto']["size"]."', hContenidoDocumentoDigitalizado = '".$sContenido."'";
    $sql = "INSERT INTO cb_certificate_file SET iConsecutivoCompania = '$id'   ".$sql_imagen;        
    $conexion->query($sql);   
    
    if ($conexion->affected_rows < 1 ) {
        $error = "1";
        $mensaje= "Internal Error, Failed to verify the account. Please report to Administrator";
        $transaccion_exitosa = false;
    }  
    
    $sql = "UPDATE ct_companias SET eEstatusCertificadoUpload = '1'  WHERE iConsecutivo = '".$id."'  ";
    $conexion->query($sql);   
    if ($conexion->affected_rows < 1 ) {
        $error = "1";
        $mensaje= "Internal Error, Failed to verify the account. Please report to Administrator";
        $transaccion_exitosa = false;
    } 
    if ($transaccion_exitosa) {    
            $mensaje = "Your information has been successfully registered.";
            $error = "0";
            $conexion->commit();
            $conexion->close();
            
        } else {
            $mensaje = "Error: Failed to save the information. Please verify..";
            $error = "1";  
            $conexion->rollback();
            $conexion->close();           
        }
    if(0){
          //Almacenando los valores recibidos
        $sAsunto = "solo-trucking team - CERTIFICATE";
        $sPara   = $_POST['para'];
        $mensaje = $_POST['mensaje'];
        $sDe     = "solo-trucking team";
                    

        $bHayFicheros = 0;
        $sCabeceraTexto = "";
        $sAdjuntos = "";

        if ($sDe)$sCabeceras = "From:".$sDe."\n";
        else $sCabeceras = "";
        $sCabeceras .= "MIME-version: 1.0\n";
        $sTexto =  "
                            <div style=\"font-size:12px;border:1px solid #6191df;border-radius:3px;padding:10px;width:95%; margin:5px auto;font-family: Arial, Helvetica, sans-serif;\">
                                 <h2 style=\"color:#313131;text-transform: uppercase; text-align:center;\">Solo-Trucking Insurance</h2> \n 
                                 <p style=\"color:#5c5c5c;margin:5px auto; text-align:left;\">His request has already been processed, attached to this email we send the certificate file.</p>\n 
                                 <p>".$mensaje."</p>
                                 <br><br> 
                                 <p style=\"color:#5c5c5c;margin:5px auto; text-align:left;\">Thank you</p>
                            </div>";


        if ($bHayFicheros == 0)
        {
        $bHayFicheros = 1;
        $sCabeceras .= "Content-type: multipart/mixed;";
        $sCabeceras .= "boundary=\"--_Separador-de-mensajes_--\"\n";

        $sCabeceraTexto = "----_Separador-de-mensajes_--\n";
        $sCabeceraTexto .= "Content-type: text/html; text/plain;charset=iso-8859-1\n";
        $sCabeceraTexto .= "Content-transfer-encoding: 7BIT\n";

        $sTexto = $sCabeceraTexto.$sTexto;
        }
        if ($_FILES['adjunto']['size'] > 0)
        {
        $sAdjuntos .= "\n\n----_Separador-de-mensajes_--\n";
        $sAdjuntos .= "Content-type: ".$_FILES['adjunto']['type'].";name=\"".$_FILES['adjunto']['name']."\"\n";;
        $sAdjuntos .= "Content-Transfer-Encoding: BASE64\n";
        $sAdjuntos .= "Content-disposition: attachment;filename=\"".$_FILES['adjunto']['name']."\"\n\n";

        $oFichero = fopen($_FILES['adjunto']["tmp_name"], 'r');
        $sContenido = fread($oFichero, filesize($_FILES['adjunto']["tmp_name"]));
        $sAdjuntos .= chunk_split(base64_encode($sContenido));
        fclose($oFichero);
        }

        if ($bHayFicheros)
        $sTexto .= $sAdjuntos."\n\n----_Separador-de-mensajes_----\n";

        @mail($sPara, $sAsunto,$sTexto, $sCabeceras);
    }
  }
  function get_company_certificate(){   
    include("cn_usuarios.php");
    //$conexion->begin_transaction();
    $conexion->autocommit(FALSE);
    $transaccion_exitosa = true;
    $where = "";
    if($_SESSION['acceso'] == "C"){
        $where = " AND  cu_control_acceso.sUsuario = '".$_SESSION["usuario_actual"]."' ";
    }
    $sql = "SELECT cb_certificate_file.iConsecutivo as folio_documento, ct_companias.iConsecutivo as id, sUsuario,CASE WHEN eEstatusCertificadoUpload = '0' then 'Pending' Else 'Loaded' END AS  hActivado,sDescripcion as nombre,sCorreo as correo FROM cu_control_acceso LEFT JOIN  ct_companias ON  cu_control_acceso.iConsecutivo = ct_companias.iConsecutivoAcceso LEFT JOIN cb_certificate_file ON cb_certificate_file.iConsecutivoCompania = ct_companias.iConsecutivo  WHERE eTipoUsuario ='C' ". $where;
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
                                   "<td>".$usuario['hActivado']."</td>";
                                   if($usuario['hActivado']=="Pending"){
                                       $htmlTabla= $htmlTabla . "<td> &nbsp;";
                                   }else{
                                    $htmlTabla= $htmlTabla.   "<td nowrap='nowrap' ><div id= 'boton_uploadFile' onclick='onAbrirDialog(\"".$usuario['folio_documento']."\");' class=\"btn-icon ico-email-fwd\" title=\"Upload Certificate\"><span></span></div>";
                                   }     
                                   
                                  $htmlTabla= $htmlTabla. "</td>".  
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
?>
