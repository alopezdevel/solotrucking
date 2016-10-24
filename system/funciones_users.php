<?php
  session_start();
  // Generic functions lib 
  include("functiones_genericas.php");
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId 
  
  function get_users(){
     
    include("cn_usuarios.php");
    $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
    $transaccion_exitosa = true;
     $registros_por_pagina = $_POST["registros_por_pagina"];
    $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
    $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
        
    //Filtros de informacion //
    $filtroQuery = " WHERE iConsecutivoTipoUsuario != '1' AND iDeleted = '0' ";
    if($_SESSION["acceso"] != 1){
         $filtroQuery .= " AND sCveTipo = 'CO' "; 
    }
    $array_filtros = explode(",",$_POST["filtroInformacion"]);
    foreach($array_filtros as $key => $valor){
        if($array_filtros[$key] != ""){
            $campo_valor = explode("|",$array_filtros[$key]);
            $campo_valor[0] == 'iConsecutivo' ? $filtroQuery.= " AND  ".$campo_valor[0]."='".$campo_valor[1]."' " : $filtroQuery == "" ? $filtroQuery.= " AND  ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'": $filtroQuery.= " AND ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'";
        }
    }
    // ordenamiento//
    $ordenQuery = " ORDER BY ".$_POST["ordenInformacion"]." ".$_POST["sortInformacion"];
    
    //contando registros // 
    $query_rows = "SELECT COUNT(A.iConsecutivo) AS total FROM cu_control_acceso A 
                   LEFT JOIN ct_tipo_usuario B ON A.iConsecutivoTipoUsuario = B.iConsecutivo
                   LEFT JOIN ct_companias C ON A.iConsecutivoCompania = C.iConsecutivo ".$filtroQuery;
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
        $sql = "SELECT A.iConsecutivo as id, sUsuario, sCorreo, CASE WHEN hActivado = '0' then 'LOCKED' Else 'ACTIVE' END AS  hActivado, 
                iConsecutivoCompania, iConsecutivoTipoUsuario, sNombreCompania , sDescripcionTipo
                FROM cu_control_acceso A
                LEFT JOIN ct_tipo_usuario B ON A.iConsecutivoTipoUsuario = B.iConsecutivo
                LEFT JOIN ct_companias C ON A.iConsecutivoCompania = C.iConsecutivo".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior; 
        $result = $conexion->query($sql);
        $rows = $result->num_rows;    
        if ($rows > 0) {    
            while ($usuario = $result->fetch_assoc()) {
               $btn_lock = "";
               if($usuario["id"] != ""){
                     $btn_confirm ="<div class=\"btn_confirm_email btn-icon send-email btn-left\" title=\"Send e-mail confirmation\"><i class=\"fa fa-envelope\"></i> <span></span></div>";
                     
                     //Boton para bloquear o desbloquear acceso:
                     if($usuario['hActivado'] == 'ACTIVE'){
                        $btn_lock = "<div class=\"btn_status_user btn-icon btn-red btn-left\" onclick=\"fn_users.change_user_status(".$usuario["id"].",0);\" title=\"Lock user access\"><i class=\"fa fa-ban\"></i><span></span></div>"; 
                     }else{
                        $btn_lock = "<div class=\"btn_status_user btn-icon btn-green btn-left\" onclick=\"fn_users.change_user_status(".$usuario["id"].",1);\" title=\"Unlock user access\"><i class=\"fa fa-unlock\"></i><span></span></div>";  
                     }
                     
                     $htmlTabla .= "<tr>
                                        <td>".$usuario['id']."</td>".
                                       "<td>".$usuario['sUsuario']."</td>".
                                       "<td>".$usuario['sCorreo']."</td>". 
                                       //"<td>".$usuario['sDescripcionTipo']."</td>".
                                       "<td>".$usuario['sNombreCompania']."</td>".
                                       "<td>".$usuario['hActivado']."</td>".                                                                                                                                                                                                                       
                                       "<td>
                                            $btn_lock
                                            <div class=\"btn_edit btn-icon edit btn-left\" title=\"Edit Company\"><i class=\"fa fa-pencil-square-o\"></i> <span></span></div>
                                            $btn_confirm
                                            <div class=\"btn_delete btn-icon trash btn-left\" title=\"Delete Company\"><i class=\"fa fa-trash\"></i> <span></span></div>
                                       </td></tr>";
                 }else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";}    
            }
            $conexion->rollback();
            $conexion->close();                                                                                                                                                                       
        }else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";} 
    }
     $response = array("total"=>"$paginas_total","pagina"=>"$pagina_actual","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
     echo json_encode($response);
  }
  function get_usertypes_companies(){
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);
      $filtro_tipo_usuario = "";
      if($_SESSION["acceso"] != 1){
         $filtro_tipo_usuario = " AND sCveTipo = 'CO' "; 
      }
      $sql = "SELECT iConsecutivo AS clave, sDescripcionTipo AS descripcion FROM ct_tipo_usuario WHERE sCveTipo != 'MA' $filtro_tipo_usuario ORDER BY iConsecutivo ASC";
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
        
      $query = "SELECT iConsecutivo AS clave, sNombreCompania AS descripcion FROM ct_companias ORDER BY iConsecutivo ASC";
      $result2 = $conexion->query($query);
      $companias = $result2->num_rows;
        if($companias > 0){ 
            $companies_select .= "<option value=\"\">Select an option...</option>";      
            while ($companies = $result2->fetch_assoc()) {
               if($companies["clave"] != ""){
                     $companies_select .= "<option value=\"".$companies['clave']."\">".$companies['descripcion']."</option>";
                 }else{                             
                     $companies_select .="";
                 }    
            }                                                                                                                                                                       
        }else {$companies_select .="";}  
      
      $conexion->rollback();
      $conexion->close();
      $htmlTabla = utf8_encode($htmlTabla); 
      $companies_select = utf8_encode($companies_select); 
      $response = array("mensaje"=>"$mensaje","error"=>"$error","types"=>"$htmlTabla","company"=>"$companies_select");   
      echo json_encode($response);
  }
  function get_user(){
      $error = '0';
      $msj = "";
      $fields = "";
      $clave = trim($_POST['clave']);
      $domroot = $_POST['domroot'];
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                 
      $sql = "SELECT * FROM cu_control_acceso WHERE iConsecutivo = ".$clave;
      $result = $conexion->query($sql);
      $items = $result->num_rows;   
      if ($items > 0) {     
        $data = $result->fetch_assoc();
        $llaves  = array_keys($data);
        $datos   = $data;
        foreach($datos as $i => $b){
            $fields .= "\$('#$domroot :input[id=".$i."]').val('".$datos[$i]."');"; 
        }  
      }
      $conexion->rollback();
      $conexion->close(); 
      $response = array("msj"=>"$msj","error"=>"$error","fields"=>"$fields");   
      echo json_encode($response);
  }
  function save_user(){
      $error = '0'; 
      $valores = array();
      $campos  = array(); 
      $msj = "";  
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
  
      #REVISAR TIPO DE USUARIO:
      if($_POST['iConsecutivoTipoUsuario'] == '2'){  //<--- COMPANIES
         $filtro_company = "AND iConsecutivoCompania = '".$_POST['iConsecutivoCompania']."'";   
      }else{
         $filtro_company = ""; 
      }
      
      //Convertir a minusculas el correo:
      $_POST['sCorreo'] != '' ? $_POST['sCorreo'] = strtolower($_POST['sCorreo']) : $_POST['sCorreo'] = '';
      
      $query = "SELECT iConsecutivo, iDeleted ".
               "FROM   cu_control_acceso ".
               "WHERE  sCorreo ='".$_POST['sCorreo']."'".$filtro_company;
      $result = $conexion->query($query);
      $valida = $result->fetch_assoc();
      
      if($valida['iConsecutivo'] != ''){
          if($_POST["edit_mode"] != 'true'){
              if($valida['iDeleted'] == '0'){
                 $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>
                      Error: The User that you trying to add already exists. Please verify your data.</p>';
                 $error = '1'; 
                 $filtro_usuario = '';
              }else{
                  //Cambiamos a Editar ya que el usuario ya existe pero esta como eliminado del sistema.
                  $_POST["edit_mode"] = 'true';
                  $_POST['iConsecutivo'] = $valida['iConsecutivo'];
                  $filtro_usuario = ", iDeleted = '0', hActivado = '1' ";
              }
          }
      }
      
      if($error == '0'){
          if($_POST["edit_mode"] == 'true'){
              //Array de campos a insertar
              foreach($_POST as $campo => $valor){
                 if($_POST['iConsecutivoTipoUsuario'] != '2' && $_POST['iConsecutivoCompania'] == ''){ // <--- Usuarios de Solo trucking o Admins
                    if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivo" and $campo != 'hClave2' and $campo != 'iConsecutivoCompania'){ //Estos campos no se insertan a la tabla
                        array_push($valores,"$campo='".trim($valor)."'");
                    }    
                 }else{
                    if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivo" and $campo != 'hClave2'){ //Estos campos no se insertan a la tabla
                        array_push($valores,"$campo='".trim($valor)."'");
                    }
                 } 
                
              }  
               
            array_push($valores ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
            array_push($valores ,"sIP='".$_SERVER['REMOTE_ADDR']."'");
            array_push($valores ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'");
            $sql = "UPDATE cu_control_acceso SET ".implode(",",$valores).$filtro_usuario." WHERE iConsecutivo = '".$_POST['iConsecutivo']."'";
            $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The data has been updated successfully!</p>'; 
          }else{
            //Array de campos a insertar:
            foreach($_POST as $campo => $valor){
                if($_POST['iConsecutivoTipoUsuario'] != '2' && $_POST['iConsecutivoCompania'] == ''){
                    if($campo != "accion" and $campo != "edit_mode" and $campo != 'hClave2' and $campo != 'iConsecutivoCompania'){ //Estos campos no se insertan a la tabla
                        array_push($campos ,$campo); 
                        array_push($valores, trim($valor));
                    } 
                }else{
                    if($campo != "accion" and $campo != "edit_mode" and $campo != 'hClave2'){ //Estos campos no se insertan a la tabla
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
            //Activar Usuario:
            array_push($campos ,"hActivado");
            array_push($valores ,'1');
            
            $sql = "INSERT INTO cu_control_acceso (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')";
            $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>The data has been added successfully!</p>';
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
      }
      $response = array("error"=>"$error","msj"=>"$msj");
      echo json_encode($response);
  }
  function delete_user(){
  
      $error = '0';  
      $msj = "";  
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      $query = "UPDATE cu_control_acceso SET iDeleted = '1', hActivado = '0' WHERE iConsecutivo = '".$_POST["clave"]."'";
      $conexion->query($query);
      $conexion->affected_rows < 1 ? $transaccion_exitosa = false : $transaccion_exitosa = true;
      if($transaccion_exitosa){
        $conexion->commit();
        $conexion->close();
        $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>
                The user has been deleted succesfully!</p>';
      }else{
        $conexion->rollback();
        $conexion->close();
        $msj = "A general system error ocurred : internal error";
        $error = "1";
      }
        
      $response = array("msj"=>"$msj","error"=>"$error");   
      echo json_encode($response);
  }
  function send_email_confirmation(){
      include("cn_usuarios.php"); //<-- conexion.
      $codigo1 = randomPassword();
      $codigo2 = randomPassword();
      //$codigo2 = substr( md5(microtime()), 1, 8).$codigo1.substr( md5(microtime()), 1, 5);
      $codigoconfirm = $codigo1.$codigo2;
      $ruta = "http://www.solotrucking.laredo2.net/system/confirm_mail_user.php?cuser=$codigoconfirm";
      $ruta = trim($ruta);
      $error = '0';
      $conexion->autocommit(FALSE);
      $transaccion_exitosa = true;
      
      $sql = "SELECT sUsuario, sCorreo, hClave, hActivado FROM cu_control_acceso WHERE iConsecutivo = '".trim($_POST['clave'])."' LOCK IN SHARE MODE";
      $result = $conexion->query($sql);
      $items = $result->num_rows;
      if($items > 0){
          //actualizamos el codigo de confirmacion:
          $sql = "UPDATE cu_control_acceso SET sCodigoVal = '".$codigoconfirm."' WHERE iConsecutivo = '".trim($_POST['clave'])."'";
          $conexion->query($sql);   
          if ($conexion->affected_rows < 1 ) {$error = "1";}     
      }
      if($error == '0'){
          $usuario = $result->fetch_assoc();
          //Proceso para enviar correo                 
          require_once("./lib/phpmailer_master/class.phpmailer.php");
          
          $mail = new PHPMailer(); 
          $mail->IsSendmail(); // telling the class to use SendMail transport
          //$mail->IsSMTP(); // telling the class to use SMTP
          //$mail->SMTPDebug  = 2;
          //$mail->SMTPAuth   = true;                  // enable SMTP authentication
          $mail->Host       = "mail.solo-trucking.com"; // SMTP server
          //$mail->Port       = 26;
         // $mail->Username   = "customerservice@solo-trucking.com";
          $mail->AddReplyTo("customerservice@solo-trucking.com","Solo-Trucking Insurance");
          
          $body .= "<!DOCTYPE html>".
                    "<html>".
                        "<head>".
                            "<meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\">".
                            "<title>solo-trucking insurance e-mail</title>".
                        "</head>";   
          $body .= "<body style=\"font-family: Arial, Helvetica, sans-serif;font-size:12px;\">".
                        "<table style=\"border:1px solid #6191df;border-radius:3px;padding:10px;width:95%; margin:5px auto;\">".
                            "<tr>".
                            "<td>"."<h2 style=\"color:#313131;text-transform: uppercase; text-align:center;\">Welcome to Solo-Trucking Insurance System!</h2>"."</td>".
                            "</tr><tr>".
                            "<td>"."<b>".trim($usuario['sUsuario'])."</b>, Thank you for joining Solo-Trucking the best option to choose the most convenient for you insurance. Feel protected!"."</td>".
                            "</tr><tr>".
                            "<td>"."Then you remember your login to our system, keep them in a safe place."."</td>".
                            "</tr><tr>".
                            "<td><ul style=\"color:#010101;line-height:15px;\">".
                            "<li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">Login: </strong>".$usuario['sCorreo']."</li>".
                            "<li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">Password: </strong>".$usuario['hClave']."</li>".
                            "</ul></td>". 
                            "</tr><tr><td><a href=\"http://www.solo-trucking.com/\">www.solo-trucking.com</a></td></tr>".
                            "<tr>".
                            "<td><p style=\"color:#858585;margin:5px auto; text-align:left;font-size:10px;\">e-mail sent from Solo-trucking Insurance System.</p></td>".
                            "</tr>".
                        "</table>".
                        "</body>";
          //footer              
          $body .= "</html>";
          
          $body =  utf8_encode($body);
          
          
          $mail->SetFrom('customerservice@solo-trucking.com', 'Solo-Trucking Insurance');
          $address = trim($usuario['sCorreo']);
          $mail->AddAddress($address);          
          $mail->Subject = "solo-trucking insurance system, please confirm your new account";
          $mail->AltBody = "to view the message, please use an HTML compatible email viewer!";
          $mail->MsgHTML($body);
          $mail->IsHTML(true); 
          
          if(!$mail->Send()) {
              $mensaje = "Error e-mail.";
              $error = "1";  
              $conexion->rollback();
              $conexion->close;
          }else{
              $error = "0";
              $conexion->commit();
              $conexion->close();
          }
     
      }else{
         $mensaje = "Error: e-mail confirmation to the user was not sent successfully.."; 
      }
      $response = array("msj"=>"$mensaje","error"=>"$error");   
      echo json_encode($response);
      
  }
  function confirm_user(){
      $code = trim($_POST["code"]);
      include("cn_usuarios.php");
      //$conexion->begin_transaction();
      $conexion->autocommit(FALSE);
      $transaccion_exitosa = true;
      $error = "0";
      $sql = "SELECT iConsecutivo, sUsuario, sCorreo , hActivado FROM cu_control_acceso WHERE sCodigoVal = '".$code."'  LOCK IN SHARE MODE";
      $result = $conexion->query($sql);
      $rows = $result->num_rows;
      if ($rows > 0) {
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
      echo json_encode($response);
      
  }
  function change_user_status(){
      
      $status = $_POST['status'];
      $clave = $_POST["clave"];
      $error = '0';  
      $msj = "";  
      //Conexion:
      include("cn_usuarios.php"); 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      $status == '0' ? $statustxt = 'locked' : $statustxt = 'unlocked'; 
      
      $query = "UPDATE cu_control_acceso SET hActivado = '$status' WHERE iConsecutivo = '$clave'";
      if($conexion->query($query)){
        $conexion->commit();
        $conexion->close();
        $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>
                The user has been '.$statustxt.' succesfully!</p>';
      }else{
        $conexion->rollback();
        $conexion->close();
        $msj = "A general system error ocurred : internal error";
        $error = "1";
      }
        
      $response = array("msj"=>"$msj","error"=>"$error");   
      echo json_encode($response); 
  }
  
?>
