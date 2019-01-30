<?php
   session_start();
   // Generic functions lib 
   include("functiones_genericas.php"); 
   $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : "";
   
   function  conexion(){  
      //1 Acceso correcto - 0 Acceso denegado no existe usuario ni password - 2 Acceso denegado no existe usuario
      include("cn_usuarios.php");
      $usuario = trim($_POST['usuario']);
      $clave   = trim($_POST['password']);
        
      //Consultando tipo de usuario siempre y cuando este Activo.
      $queryUsuario     = "SELECT iConsecutivoTipoUsuario, sUsuario, hActivado FROM cu_control_acceso WHERE sCorreo = '".$usuario."' AND hClave = '".$clave."'"; 
      $resultadoUsuario = $conexion->query($queryUsuario);  
      $rows             = $resultadoUsuario->num_rows; 
      #VERIFICAR ACCESO:
      if($rows > 0 ){
            $tipo_usuario = $resultadoUsuario->fetch_assoc();
            if($tipo_usuario['hActivado'] == '1'){
            //validando query de acceso a compañias // tipo de usuario --> 2
              if($tipo_usuario['iConsecutivoTipoUsuario'] == '2'){
                  
                 //Verificando si el usuario ingreso bien su password y obtener datos de la compania: 
                 $sql    = "SELECT A.iConsecutivo,iConsecutivoCompania, sNombreCompania ". 
                           "FROM cu_control_acceso A ". 
                           "LEFT JOIN ct_companias B ON A.iConsecutivoCompania = B.iConsecutivo ".
                           "WHERE sCorreo = '".$usuario."' AND hClave = '".$clave."' AND hActivado IN ('1','2')";    
                 $result = $conexion->query($sql);  
                 $rows2  = $result->num_rows;
                 if($rows2 > 0){
                    $company = $result->fetch_assoc(); //Array data.
                    #creando variables de sesion para usuario company:
                    $respuesta = '1';
                    $_SESSION["company"]      = $company['iConsecutivoCompania'];
                    $_SESSION["company_name"] = $company['sNombreCompania'];
                     
                 }else{
                     $respuesta = '2';
                     $mensaje   = "Error: The Company are not exist, please report to administrator ('systemsupport@solo-trucking.com').";
                 } 
                 
              }else{
                 
                 //ESTO ES PARA USUARIOS INTERNOS DE SOLO Y ADMINS: Verificando password y trayendo datos. 
                 $sql    = "SELECT iConsecutivo ". 
                           "FROM cu_control_acceso ". 
                           "WHERE sCorreo = '".$usuario."' AND hClave = '".$clave."' AND hActivado IN ('1','2') ";
                 $result = $conexion->query($sql);  
                 $rows2  = $result->num_rows; 
                 if($rows2 > 0){$respuesta = '1';}
                 else{
                     $respuesta = '2';
                     $mensaje   = "Error: The user or password are wrong, please try again.";
                 } 
                  
              }
            }else{
              $respuesta = '2';
              $mensaje   = "Error: This user has been locked, please contact us for more information: \n customerservice@solo-trucking.com";
              session_unset();
              session_destroy();  
            }  
          
      }else{
          $respuesta = '2';
          $mensaje   = "Error: The user or password are wrong, please try again.";
          session_unset();
          session_destroy(); 
      }
      
      #REVISAMOS VARIABLE DE RESPUESTA: 
      if($respuesta == '1'){
          $acceso                     = '1';
          $_SESSION["acceso"]         = trim($tipo_usuario['iConsecutivoTipoUsuario']);
          $_SESSION["usuario_actual"] = $usuario;
          setcookie("USER", $usuario);
          session_cache_expire(600); // <-- Delimitar cache a 24 hrs.  
      }else{$acceso = '0';}
      
      $sql = "INSERT INTO cu_intentos_acceso (sUsuario,sClave,dFechaIngreso,sIP,bEntroSistema) ".
             "VALUES ('$usuario','$clave','".date("Y-m-d H:i:s")."','".$_SERVER['REMOTE_ADDR']."','$acceso')";
      $conexion->query($sql);
      $conexion->close();  
      $response = array("usuario"=>"$usuario","respuesta"=>"$respuesta","mensaje"=>"$mensaje");          
      echo json_encode($response);  
   }
?>
