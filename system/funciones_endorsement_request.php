<?php
  session_start();
  // Generic functions lib 
  include("functiones_genericas.php"); 
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId 
  
  function get_endorsements(){
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      $registros_por_pagina = $_POST["registros_por_pagina"];
      $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
      $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
        
     //Filtros de informacion //
     $filtroQuery = " WHERE B.eStatus != 'E' AND iConsecutivoTipoEndoso = '2' ";
     $array_filtros = explode(",",$_POST["filtroInformacion"]);
     foreach($array_filtros as $key => $valor){
        if($array_filtros[$key] != ""){
            $campo_valor = explode("|",$array_filtros[$key]);
            if($campo_valor[0] == 'iConsecutivo'){ 
                $filtroQuery.= " AND  ".$campo_valor[0]."='".$campo_valor[1]."' ";
            }else{
                if($campo_valor[0] == 'eStatus'){
                     $filtroQuery .= " AND  ".$campo_valor[0]." = '".$campo_valor[1]."'";
                }else{
                     $filtroQuery.= " AND  ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'";
                }   
            } 
        }
     }
     // ordenamiento//
     $ordenQuery = " ORDER BY ".$_POST["ordenInformacion"]." ".$_POST["sortInformacion"];

    //contando registros // 
    $query_rows = "SELECT COUNT(B.iConsecutivo) AS total ".
                  "FROM cb_endoso_estatus A ".
                  "LEFT JOIN cb_endoso B ON A.iConsecutivoEndoso = B.iConsecutivo ".
                  "LEFT JOIN ct_tipo_endoso C ON B.iConsecutivoTipoEndoso = C.iConsecutivo  ".
                  "LEFT JOIN ct_companias D ON B.iConsecutivoCompania = D.iConsecutivo  ".
                  "LEFT JOIN ct_operadores E ON B.iConsecutivoOperador = E.iConsecutivo  ".
                  "LEFT JOIN ct_polizas P ON A.iConsecutivoPoliza = P.iConsecutivo ". 
                  "LEFT JOIN ct_brokers BR ON P.iConsecutivoBrokers = BR.iConsecutivo".$filtroQuery;
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
      $sql = "SELECT B.iConsecutivo, D.sNombreCompania,  DATE_FORMAT(B.dFechaAplicacion,  '%m/%d/%Y %H:%i') AS dFechaIngreso, C.sDescripcion AS categoria, A.eStatus, eAccion, D.iOnRedList, sNombre,sNumeroPoliza, sName AS broker, A.iConsecutivoPoliza ".
             "FROM cb_endoso_estatus A ".
             "LEFT JOIN cb_endoso B ON A.iConsecutivoEndoso = B.iConsecutivo ".
             "LEFT JOIN ct_tipo_endoso C ON B.iConsecutivoTipoEndoso = C.iConsecutivo  ".
             "LEFT JOIN ct_companias D ON B.iConsecutivoCompania = D.iConsecutivo  ".
             "LEFT JOIN ct_operadores E ON B.iConsecutivoOperador = E.iConsecutivo  ".
             "LEFT JOIN ct_polizas P ON A.iConsecutivoPoliza = P.iConsecutivo ". 
             "LEFT JOIN ct_brokers BR ON P.iConsecutivoBrokers = BR.iConsecutivo".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior;
      $result = $conexion->query($sql);
      $rows = $result->num_rows; 
         
        if ($rows > 0) {    
            while ($usuario = $result->fetch_assoc()) { 
               if($usuario["iConsecutivo"] != ""){
                     $btn_confirm = "";
                     $estado = "";
                     $class = "";
                     $descripcion = ""; 
                     switch($usuario["eStatus"]){
                         case 'S': 
                            $estado = 'NEW APPLICATION';
                            $class = "class = \"blue\"";
                            $btn_confirm = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"View and Edit Endorsement Status\"><i class=\"fa fa-pencil-square-o\"></i> <span></span>".
                                           "</div><div class=\"btn_send_email btn-icon send-email btn-left\" title=\"Send e-mail to the brokers\"><i class=\"fa fa-envelope\"></i><span></span></div>"; 
                         break;
                         case 'A': 
                            $estado = 'APPROVED';
                            $class = "class = \"green\"";
                            $btn_confirm = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"View and Edit Endorsement Status\"><i class=\"fa fa-pencil-square-o\"></i> <span></span>"; 
                         break;
                         case 'D': 
                            $estado = 'DENIED';
                            $class = "class = \"red\"";
                            $btn_confirm = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"View and Edit Endorsement Status\"><i class=\"fa fa-pencil-square-o\"></i> <span></span>";
                                        
                         break;
                         case 'SB': 
                            $estado = 'SENT TO BROKERS';
                            $class = "class = \"yellow\"";
                            $btn_confirm = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"View and Edit Endorsement Status\"><i class=\"fa fa-pencil-square-o\"></i> <span></span>"; 
                         break;
                         case 'P': 
                            $estado = 'IN PROCESS';
                            $class = "class = \"orange\"";
                            $btn_confirm = "<div class=\"btn_edit btn-icon edit btn-left\" title=\"View and Edit Endorsement Status\"><i class=\"fa fa-pencil-square-o\"></i> <span></span>".
                                           "</div><div class=\"btn_send_email btn-icon send-email btn-left\" title=\"Send e-mail to the brokers\"><i class=\"fa fa-envelope\"></i><span></span></div>";  
                         break;
                     } 
                     $color_action = "";
                     $action = "";
                     switch($usuario["eAccion"]){
                         case 'A': 
                            $action = 'ADD';
                            $color_action = "color:#00970d"; 
                         break;
                         case 'D': 
                            $action = 'DELETE'; 
                            $color_action = "color:#ab0000"; 
                         break;
                     }
                     
                      //Redlist:
                     $usuario['iOnRedList'] == '1' ? $redlist_icon = "<i class=\"fa fa-star\" style=\"color:#e8051b;margin-right:4px;\"></i>" : $redlist_icon = ""; 
                     $htmlTabla .= "<tr $class>
                                        <td>".$usuario['iConsecutivo']."</td>".
                                       "<td>".$redlist_icon.$usuario['sNombreCompania']."</td>".
                                       "<td>".strtoupper($usuario['sNombre'])."</td>". 
                                       "<td style=\"$color_action\">".$action."</td>".
                                       "<td class=\"text-center\">".$usuario['dFechaIngreso']."</td>".
                                       "<td id=\"".$usuario['iConsecutivoPoliza']."\">".$usuario['sNumeroPoliza']."</td>".
                                       "<td>".$usuario['broker']."</td>". 
                                       "<td class=\"text-center\">".$estado."</td>".                                                                                                                                                                                                                       
                                       "<td> $btn_confirm</td></tr>";
                 }else{                                                                                                                                                                                                        
                    
                     $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>"   ;
                 }    
            }
            $conexion->rollback();
            $conexion->close();                                                                                                                                                                       
        } else { 
            
            $htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>"   ;    
            
        }
      }
      $response = array("total"=>"$paginas_total","pagina"=>"$pagina_actual","tabla"=>"$htmlTabla","mensaje"=>"$mensaje","error"=>"$error","tabla"=>"$htmlTabla");   
      echo json_encode($response); 
  }
  function get_endorsement(){
      #Err flags:
      $error = '0';
      $msj = "";
      #Variables
      $fields = "";
      $clave = trim($_POST['clave']);
      $idPoliza = trim($_POST['idPoliza']);
      $domroot = $_POST['domroot'];
      
      #Function Begin
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                 
      $sql = "SELECT A.iConsecutivo, iConsecutivoCompania, iConsecutivoTipoEndoso, sDescripcion, C.eStatus, iReeferYear,iTrailerExchange, iPDAmount, ". 
             "sComentarios, iPDApply, iConsecutivoOperador, iConsecutivoUnidad, eAccion, iConsecutivoPoliza   ".  
             "FROM cb_endoso A ".
             "LEFT JOIN ct_tipo_endoso B ON A.iConsecutivoTipoEndoso = B.iConsecutivo ". 
             "LEFT JOIN cb_endoso_estatus C ON A.iConsecutivo = C.iConsecutivoEndoso AND iConsecutivoPoliza = '$idPoliza'  ".
             "WHERE A.iConsecutivo = '$clave'";
      $result = $conexion->query($sql);
      $items = $result->num_rows; 
      if ($items > 0) {     
            
            $data = $result->fetch_assoc(); //<---Endorsement Data Array.
            
            #Cambiando texto de Action:
            if($data['eAccion'] == 'A'){$data['eAccion']= 'ADD'; }else if($data['eAccion'] == 'D'){$data['eAccion']  = 'DELETE';}
            
            $llaves  = array_keys($data);
            $datos   = $data; 
            foreach($datos as $i => $b){ 
                if($i == 'sDescripcion' || $i == 'eAccion'|| $i == 'iConsecutivo' || $i == 'eStatus' || $i == 'iConsecutivoPoliza'){
                  $fields .= "\$('#$domroot :input[id=".$i."]').val('".$datos[$i]."');";  
                }else if($i == 'sComentarios' && $data['eStatus'] == 'D'){
                  $comentarios = utf8_decode($datos[$i]);  
                }
                
                if($i == 'iConsecutivoPoliza'){
                       //$PolizasEndoso = explode('|',$datos[$i]); 
                       //$htmlTabla = "";
                       //for ($i = 0; $i < count($PolizasEndoso); $i++) {
                            //$poliza = explode('/',$PolizasEndoso[$i]);
                            //$filtro = "AND sNumeroPoliza = '".$poliza[0]."' AND iTipoPoliza = '".$poliza[1]."' "; 
                               
                               $policy_query = "SELECT sNumeroPoliza, D.sDescripcion, D.iConsecutivo AS TipoPoliza, sName ".
                                               "FROM ct_polizas A LEFT JOIN ct_tipo_poliza D ON A.iTipoPoliza = D.iConsecutivo ".
                                               "LEFT JOIN ct_brokers C ON A.iConsecutivoBrokers = C.iConsecutivo ".
                                               "WHERE iConsecutivoCompania = '".$data['iConsecutivoCompania']."' ".
                                               "AND A.iConsecutivo = '".$data['iConsecutivoPoliza']."' AND A.iDeleted ='0'"; 

                               $result_policy = $conexion->query($policy_query);
                               $rows_policy = $result_policy->num_rows; 
                               if($rows_policy > 0){
                                   while($policies = $result_policy->fetch_assoc()){
                                      $htmlTabla .= "<tr>".
                                                        "<td>".$policies['sNumeroPoliza']."</td>".
                                                        "<td>".$policies['sName']."</td>".
                                                        "<td>".$policies['sDescripcion']."</td>".
                                                    "</tr>";
                                       if($policies['TipoPoliza'] == "1" && $data['iPDApply'] == 1 && $data['eAccion'] == 'A' && $data['iConsecutivoTipoEndoso'] == '1'){
                                             $pd_information = "<label>Apply:</label>".
                                                               "<input id=\"iPDApply\" name=\"iPDApply\" value=\"YES\" style=\"width: 10%!important;\" readonly=\"readonly\" class=\"readonly\">".
                                                               "<label>Amount:</label>".
                                                               "<input id=\"iPDAmount\" name=\"iPDAmount\" value=\"".$data['iPDAmount']."\" type=\"text\" readonly=\"readonly\" class=\"readonly\" style=\"width: 75%!important;margin-left: 4px;\">";
                                       }          
                                   }
                                   
                               }    
                       //}
                    
                }  
            }
            
            if($data['iConsecutivoOperador']!= '' && $data['iConsecutivoTipoEndoso']== '2'){
                $sql_driver = "SELECT sNombre,DATE_FORMAT(dFechaNacimiento,'%m/%d/%Y') AS dFechaNacimiento, DATE_FORMAT(dFechaExpiracionLicencia,'%m/%d/%Y') AS dFechaExpiracionLicencia,iExperienciaYear,iNumLicencia,dFechaContratacion,
                               iEntidad,eTipoLicencia FROM ct_operadores 
                               WHERE iConsecutivo = '".$data['iConsecutivoOperador']."'";
                $result = $conexion->query($sql_driver);
                $items_driver = $result->num_rows;
                if($items_driver > 0){
                     $data_driver = $result->fetch_assoc();
                     $data_driver['eTipoLicencia'] == "1" ? $data_driver['eTipoLicencia'] = 'FEDERAL / B1' : $data_driver['eTipoLicencia'] = 'COMMERCIAL / CDL-1'; 
                     $llaves_driver  = array_keys($data_driver);
                     $datos_driver   = $data_driver;
                     foreach($datos_driver as $i => $b){ 
                        $fields .= "\$('#$domroot :input[id=".$i."]').val('".$datos_driver[$i]."');"; 
                     }
                     
                     
                     //Get files from driver if the kind endorsement is nan ADD:
                     if($data['eAccion'] == 'ADD'){
                        
                        $sql_driver_files = "SELECT iConsecutivo, sNombreArchivo, eArchivo, sTipoArchivo FROM cb_operador_files WHERE  iConsecutivoOperador = '".$data['iConsecutivoOperador']."'";
                        $result_files = $conexion->query($sql_driver_files);
                        $file_rows  = $result_files->num_rows;
                        
                        if($file_rows > 0){
                            $htmlFiles = "<thead><tr>"."<td><label>File Name</label></td>"."<td><label>Preview</label></td>"."</tr><thead>";
                            $edad = CalculaEdad($data_driver['dFechaNacimiento']);  
                            while ($items_files = $result_files->fetch_assoc()) {
                                //Define icono del boton:
                                $btnIcon = "";
                                if($items_files['sTipoArchivo'] == 'application/pdf'){$btnIcon = "fa-file-pdf-o";}else if($items_files['sTipoArchivo'] == 'image/jpeg'){$btnIcon = "fa-file-image-o";}
                                
                                if($data_driver['eTipoLicencia'] == 'FEDERAL / B1' && ($items_files['eArchivo'] == 'LICENSE' || $items_files['eArchivo'] == 'PSP')){
                                   $htmlFiles .= "<tr>".
                                                 "<td>".$items_files['sNombreArchivo']."</td>".
                                                 "<td><div onclick=\"window.open('open_pdf.php?idfile=".$items_files['iConsecutivo']."&type=driver');\" class=\"btn-icon pdf btn-left\" title=\"View PDF File\"><i class=\"fa $btnIcon\"></i><span></span></td>".
                                                 "</tr>"; 
                                }else if($data_driver['eTipoLicencia'] == 'COMMERCIAL / CDL-1' && ($items_files['eArchivo'] == 'LICENSE' || $items_files['eArchivo'] == 'MVR')){
                                   $htmlFiles .= "<tr>".
                                                 "<td>".$items_files['sNombreArchivo']."</td>".
                                                 "<td><div onclick=\"window.open('open_pdf.php?idfile=".$items_files['iConsecutivo']."&type=driver');\" class=\"btn-icon pdf btn-left\" title=\"View PDF File\"><i class=\"fa $btnIcon\"></i><span></span></td>".
                                                 "</tr>";  
                                }
                                if($edad > 65 && $items_files['eArchivo'] == 'LONGTERM'){
                                   $htmlFiles .= "<tr>".
                                                 "<td>".$items_files['sNombreArchivo']."</td>".
                                                 "<td><div onclick=\"window.open('open_pdf.php?idfile=".$items_files['iConsecutivo']."&type=driver');\" class=\"btn-icon pdf btn-left\" title=\"View PDF File\"><i class=\"fa $btnIcon\"></i><span></span></td>".
                                                 "</tr>";  
                                }
                            }  
                        }else{$htmlFiles = "";} 
                         
                     }

                }
            }
  
            $tipoEndoso = $data['iConsecutivoTipoEndoso'];
            $eStatus = $data['eStatus'];  
      }
      $conexion->rollback();
      $conexion->close(); 
      $response = array(
                    "msj"=>"$msj",
                    "error"=>"$error",
                    "fields"=>"$fields",
                    "pd_information" => "$pd_information",
                    "policies"=>"$htmlTabla",
                    "kind"=>"$tipoEndoso",
                    "status"=>"$eStatus",
                    "files" => "$htmlFiles",
                    "sComentarios" => "$comentarios"
                  );   
      echo json_encode($response);  
      
  }
  function send_email_brokers(){
      include("cn_usuarios.php");
      $error = '0';
      //variables:
      $id= $_POST['clave'];
      $idPoliza= $_POST['idPoliza']; 
      $htmlEmail = "";
      $conexion->autocommit(FALSE);
      $transaccion_exitosa = true;
      
      #1- First Step: Consult the general information from the Endorsement with the id.
      $endorsement_query = "SELECT A.iConsecutivo, A.iConsecutivoCompania, sNombreCompania,iConsecutivoTipoEndoso,iConsecutivoOperador, eAccion, sNumeroPoliza, T.sDescripcion AS Tipo, sEmail AS Broker, BR.sName AS NameBroker
                            FROM cb_endoso A
                            LEFT JOIN ct_companias B ON A.iConsecutivoCompania = B.iConsecutivo
                            LEFT JOIN cb_endoso_estatus D ON A.iConseCutivo = D.iConsecutivoEndoso
                            LEFT JOIN ct_polizas P ON D.iConsecutivoPoliza = P.iConsecutivo
                            LEFT JOIN ct_tipo_poliza T ON P.iTipoPoliza = T.iConsecutivo
                            LEFT JOIN ct_brokers BR ON P.iConsecutivoBrokers = BR.iConsecutivo
                            WHERE A.iConsecutivo = '$id' AND D.iConsecutivoPoliza = '$idPoliza'";                          
      $result = $conexion->query($endorsement_query);
      $rows = $result->num_rows; 
      $rows > 0 ? $endorsement = $result->fetch_assoc() : $endorsement = "";
      
      
      if($endorsement['iConsecutivo'] != "" && $endorsement['iConsecutivoTipoEndoso'] == '2' && $endorsement['iConsecutivoOperador'] != ''){    

             #Driver: Consult the driver information:
             $driver_query = "SELECT iConsecutivo, sNombre,DATE_FORMAT(dFechaNacimiento,'%m/%d/%Y') AS dFechaNacimiento, DATE_FORMAT(dFechaExpiracionLicencia,'%m/%d/%Y') AS dFechaExpiracionLicencia,iExperienciaYear,
                              iNumLicencia,dFechaContratacion,eTipoLicencia FROM ct_operadores 
                              WHERE iConsecutivo = '".$endorsement['iConsecutivoOperador']."'";
             $result_d = $conexion->query($driver_query);
             $rows_d = $result_d->num_rows; 
             $rows_d > 0 ? $driver = $result_d->fetch_assoc() : $driver = "";
      
             $subject_policy = $endorsement['sNumeroPoliza'].'-'.$endorsement['Tipo'];        
                       
             #action to define the subject:
             switch($endorsement["eAccion"]){ 
                    case 'A': 
                        $action = 'Please add to my policy the following driver.';
                        $subject = "Endorsement application - please add the following driver from policy number: ".$endorsement['sNombreCompania'].", ".$subject_policy; 
                        break; 
                    case 'D': 
                        $action = 'Please delete from my policy the following driver.';                                                                   
                        $subject = "Endorsement application - please delete the following driver from policy number: ".$endorsement['sNombreCompania'].", ".$subject_policy; 
                    break;
                } 
                         
                         
                        #Building Email Body:                                   
                        require_once("./lib/phpmailer_master/class.phpmailer.php");
                        require_once("./lib/phpmailer_master/class.smtp.php");
                        
                        //header
                        $htmlEmail .= "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"\"http://www.w3.org/TR/html4/strict.dtd\"><html>
                                      <head>
                                          <meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\">
                                          <title>endorsement from solo-trucking insurance</title>
                                      </head>";
                        //Body
                        $htmlEmail .= "<body>".
                                      "<table style=\"font-size:12px;border:1px solid #6191df;border-radius:3px;padding:10px;width:95%; margin:5px auto;font-family: Arial, Helvetica, sans-serif;\">".
                                      "<tr><td>".
                                      "<h2 style=\"color:#313131;text-transform: uppercase; text-align:center;\">Endorsement application from Solo-Trucking Insurance</h2> \n".
                                      "</td></tr>". 
                                      "<tr><td>".
                                      "<p style=\"color:#000;margin:5px auto; text-align:left;\">$action</p><br><br>".
                                      "</td></tr>".
                                      "<tr><td>".
                                      "<ul style=\"color:#010101;line-height:15px;list-style:none;\"> ".
                                      "<li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">NAME: </strong>".$driver['sNombre']."</li>".
                                      "<li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">DOB: </strong>".$driver['dFechaNacimiento']."</li>".
                                      "<li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">YOE: </strong>".$driver['iExperienciaYear']."</li>".
                                      "<li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">LICENSE EXP: </strong>".$driver['dFechaExpiracionLicencia']."</li>".
                                      "<li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">LICENSE NUMBER: </strong>".$driver['iNumLicencia']."</li>".
                                      "</ul>".
                                      "</td></tr>".
                                      "<tr><td><p style=\"color:#010101;margin:5px auto 10px; text-align:left;font-size:11px;\">Please reply this email to the account:<a href=\"mailto:customerservice@solo-trucking.com\"> customerservice@solo-trucking.com</a></p></td></tr>".
                                      "<tr><td><p style=\"color:#858585;margin:5px auto; text-align:left;font-size:10px;\">e-mail sent from Solo-trucking Insurance System.</p></td></tr>".
                                      "</table>".
                                      "</body>";
                        //footer              
                        $htmlEmail .= "</html>"; 
                            
                        #TERMINA CUERPO DEL MENSAJE
                        $mail = new PHPMailer();   
                        $mail->IsSMTP(); // telling the class to use SMTP
                        $mail->Host       = "mail.solo-trucking.com"; // SMTP server
                        //$mail->SMTPDebug  = 2; // enables SMTP debug information (for testing) 1 = errors and messages 2 = messages only
                        $mail->SMTPAuth   = true;                  // enable SMTP authentication
                        $mail->SMTPSecure = "TLS";                 // sets the prefix to the servier
                        $mail->Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
                        $mail->Port       = 587;                   // set the SMTP port for the GMAIL server
                        $mail->Username   = "systemsupport@solo-trucking.com";  // GMAIL username
                        $mail->Password   = "SL09100242"; 
                        $mail->SetFrom('systemsupport@solo-trucking.com', 'Solo-Trucking Insurance');
                        $mail->AddReplyTo('customerservice@solo-trucking.com','Customer service Solo-Trucking');
                        $mail->Subject    = $subject;
                        $mail->AltBody    = "To view the message, please use an HTML compatible email viewer!";  // optional, comment out and test
                        $mail->MsgHTML($htmlEmail);
                        $mail->IsHTML(true); 
                        $email_destinatario = trim($endorsement['Broker']); 
                        $nombre_destinatario = trim($endorsement['NameBroker']);
                        $mail->AddAddress($email_destinatario,$nombre_destinatario);
                        //$mail->AddAddress('celina@websolutionsac.com','Magaly'); 
                        
                        //Revisar si se necesitan enviar archivos adjuntos:
                        if($endorsement['eAccion'] == 'A'){
                            include("./lib/fpdf153/fpdf.php"); // libreria fpdf 
                            //Consult the driver files:
                            $driver_files_query = "SELECT iConsecutivo, sNombreArchivo, eArchivo, hContenidoDocumentoDigitalizado, sTipoArchivo, iTamanioArchivo  
                                                   FROM cb_operador_files 
                                                   WHERE  iConsecutivoOperador = '".$endorsement['iConsecutivoOperador']."'";
                            
                           $result_files = $conexion->query($driver_files_query);
                           $rows_files = $result_files->num_rows; 
                           if($rows_files > 0){
                                while ($files = $result_files->fetch_assoc()){
                                   #Here will constructed the temporary files: 
                                   if($files['sNombreArchivo'] != ""){ 
                                     $file_tmp = fopen('tmp/'.$files["sNombreArchivo"],"w") or die("Error when creating the file. Please check."); 
                                     fwrite($file_tmp,$files["hContenidoDocumentoDigitalizado"]); 
                                     fclose($file_tmp);     
                                     $archivo = "tmp/".$files["sNombreArchivo"];  
                                     $mail->AddAttachment($archivo);
                                     $delete_files .= "unlink(\"tmp/\".".$files["sNombreArchivo"].");"; 
                                   }
                                   
                                }
                           } 
                        }
                        $mail_error = false;
                        if(!$mail->Send()){$mail_error = true; $mail->ClearAddresses();}
                        if(!$mail_error){
                            //$mensaje = "Mail confirmation to the user $usuario was successfully sent.";
                            
                        }else{
                            $mensaje = "Error: The e-mail cannot be sent.";
                            $error = "1";            
                        }
                        $mail->ClearAttachments();
                        #deleting files attachment:
                        eval($delete_files);

                 #VERIFICAR SI SE ENVIARON LOS CORREOS CORRECTAMENTE:
                 if($error == '0'){
                    #UPDATE ENDORSEMENT DETAILS:
                    $sql_endoso = "UPDATE cb_endoso_estatus SET eStatus = 'SB', dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."', sUsuarioActualizacion='".$_SESSION['usuario_actual']."' ".
                                  "WHERE iConsecutivoEndoso = '$id' AND iConsecutivoPoliza = '$idPoliza'"; 
                    if($conexion->query($sql_endoso)){
                            $msj = "The Endorsement was sent successfully, please check your email (customerservice@solo-trucking.com) waiting for their response."; 
                    }else{
                          $transaccion_exitosa = false;
                            $msj = "The data of endorsement was not updated properly, please try again.";  
                    } 
  
                 }
  

      }else{
          $error = '1';
          $msj = "The endorsement data was not found, please try again.";
      }
      
      if($transaccion_exitosa){
            $conexion->commit();
            $conexion->close();
      }else{
            $conexion->rollback();
            $conexion->close();
            $error = "1";
      }
      $response = array("error"=>"$error","msj"=>"$msj");
      echo json_encode($response);
      
  }
  
  
  /*------FUNCIONES GENERALES DEL MODULO DE SOLICITUD DE ENDOSOS -----------------------*/
  function update_endorsement_status(){
      #paremeters
      $iConsecutivo = trim($_POST['iConsecutivo']);
      $idPoliza = trim($_POST['idPoliza']);
      $eStatus = trim($_POST['eStatus']);
      $_POST['sComentarios'] != '' ? $sComentarios = utf8_encode(trim($_POST['sComentarios'])) : $sComentarios = '';
      #variables
      $error = '0';  
      $msj = "";
      
      //Conexion:
      include("cn_usuarios.php");
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true; 
      
      if($iConsecutivo != ''){  
        $sql = "UPDATE cb_endoso_estatus SET eStatus='$eStatus', dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."',sUsuarioActualizacion='".$_SESSION['usuario_actual']."'".
               "WHERE iConsecutivoEndoso = '$iConsecutivo' AND iConsecutivoPoliza = '$idPoliza'";     
        if($conexion->query($sql)){
                 $eStatusGeneral = "";  
                 $query = "SELECT eStatus FROM cb_endoso_estatus WHERE iConsecutivoEndoso = '$iConsecutivo' GROUP BY eStatus ORDER BY eStatus DESC";
                 $result = $conexion->query($query);
                 $rows = $result->num_rows;
                  if($rows > 0){
                      $cadena = array();
                      while ($items = $result->fetch_assoc()) {array_push($cadena, trim($items['eStatus']));}
                      //Revisando cadena:
                      if(in_array('D',$cadena)){
                          $eStatusGeneral = 'D';  
                      }else if(in_array('P',$cadena)){
                          $eStatusGeneral = 'P';
                      }else if(in_array('SB',$cadena)){
                            $eStatusGeneral = 'SB';
                      }else if(in_array('A',$cadena)){
                            $eStatusGeneral = 'A';
                      }
                  }
                 
                 if($eStatusGeneral != ''){
                     $sql = "UPDATE cb_endoso SET eStatus ='$eStatusGeneral', sComentarios='$sComentarios', dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."',sUsuarioActualizacion='".$_SESSION['usuario_actual']."'".
                            "WHERE iConsecutivo = '$iConsecutivo'";
                     //$conexion->query($sql);
                     //$conexion->affected_rows < 1 ? $transaccion_exitosa = false : $transaccion_exitosa = true;
                     if(!($conexion->query($sql))){
                        $error = '1';                                         
                        $msj = "Error: The Endorsement data was not found, please try again.";
                     }else{
                      //Revisamos si el Estatus es "APPROVED"  para actualizar la unidad o driver y agregarlo a las polizas.
                      if($eStatusGeneral == 'A'){
                         #1. saber que tipo de endoso es: 
                         $query_endoso = "SELECT iConsecutivoTipoEndoso, sNumPolizas, iConsecutivoCompania, iConsecutivoOperador, iConsecutivoUnidad, eAccion FROM cb_endoso WHERE iConsecutivo = '$iConsecutivo'";
                         $result = $conexion->query($query_endoso);
                         $items = $result->num_rows; 
                         
                         if($items > 0){     
                            $data = $result->fetch_assoc();
                            #2. Revisamos que sea ADD y sea una unit o un driver:
                            if($data['eAccion'] == 'A' && ($data['iConsecutivoTipoEndoso'] == '1' || $data['iConsecutivoTipoEndoso'] == '2')){
                                $id_polizas = "";
                                $PolizasEndoso = explode('|',$data['sNumPolizas']);
                                for ($i = 0; $i < count($PolizasEndoso); $i++) {
                                     $poliza = explode('/',$PolizasEndoso[$i]);
                                     $policy_query = "SELECT iConsecutivo FROM ct_polizas WHERE sNumeroPoliza = '".$poliza[0]."' AND iTipoPoliza = '".$poliza[1]."' AND iConsecutivoCompania = '".$data['iConsecutivoCompania']."'";
                                     $result2 = $conexion->query($policy_query);
                                     $items2 = $result2->num_rows; 
                                     if($items2 > 0 ){
                                        $iConsecutivoPoliza = $result2->fetch_assoc();  
                                        $id_polizas == '' ?  $id_polizas = $iConsecutivoPoliza['iConsecutivo'] : $id_polizas .= ','.$iConsecutivoPoliza['iConsecutivo']; 
                                     }else{
                                         $error = '1';
                                         $mensaje = "Error: Policy data not found.";
                                     }
                                }
                                 
                                if($id_polizas != ''){
                                    $Tabla = "";
                                    $ConsecutivoContenido = "";
                                    if($data['iConsecutivoTipoEndoso'] == '1' && $data['iConsecutivoUnidad'] != ''){ // UNITS
                                      $Tabla = "ct_unidades";
                                      $ConsecutivoContenido = trim($data['iConsecutivoUnidad']);  
                                    }else if($data['iConsecutivoTipoEndoso'] == '2' && $data['iConsecutivoOperador'] != ''){ //DRIVERS
                                       $Tabla = "ct_operadores";
                                       $ConsecutivoContenido = trim($data['iConsecutivoOperador']); 
                                    }
                                    
                                    #3. Actualizamos la tabla correspondiente:
                                    if($Tabla != '' && $ConsecutivoContenido != ''){
                                       $update_list = "UPDATE $Tabla SET inPoliza = '1', siConsecutivosPolizas = '$id_polizas' WHERE iConsecutivo = '$ConsecutivoContenido' AND iConsecutivoCompania = '".$datos['iConsecutivoCompania']."'";
                                       if(!($conexion->query($update_list))){
                                            $error = '1';
                                            $transaccion_exitosa = false;                                         
                                            $msj = "Error: The Unit or driver data was not found, please try again.";
                                       }else{
                                          $msj = "The data has been update successfully."; 
                                       }
                                       
                                    }else{
                                      $error = '1';
                                      $mensaje = "Error: the policy description list was not updated successfully.";  
                                    }
                                    
                                }else{
                                    $error = '1';
                                    $mensaje = "Error: Policy ids not found.";
                                }
                            }else{
                                $msj = "The data has been update successfully.";
                            }
                         }else{
                             $error = '1';
                             $mensaje = "Error: Policy ids not found.";
                         }
                           
                      }else{
                         $msj = "The data has been update successfully."; 
                      }
                      
                     }
                         
                 }else{
                     $msj = "The data has been update successfully.";
                 }
                
          }else{
              $error = '1';
              $msj = "Error: The Endorsement data was not found, please try again.";
          }
      }
      if($error == '0'){
         $conexion->commit();
         $conexion->close();
         //$msj = "The data has been update successfully.";  
      }else{
         $conexion->rollback();
         $conexion->close(); 
      }
      
      $response = array("error"=>"$error","msj"=>"$msj");
      echo json_encode($response);
  } 
  function evalua_estatus_general($idEndoso){
      include("cn_usuarios.php");
      $query = "SELECT eStatus FROM cb_endoso_estatus WHERE iConsecutivoEndoso = '$idEndoso' GROUP BY eStatus ORDER BY eStatus DESC";
      $result = $conexion->query($query);
      $rows = $result->num_rows;
      
      if($rows > 0){
          $cadena = array();
          while ($items = $result->fetch_assoc()) {                                           
             
             array_push($cadena, trim($items['eStatus']));
          }
          
          //Revisando cadena:
          if(in_array('D',$cadena)){
              $Status = 'D';  
          }else if(in_array('P',$cadena)){
              $Status = 'P';
          }else if(in_array('SB',$cadena)){
                $Status = 'SB';
          }else if(in_array('A',$cadena)){
                $Status = 'A';
          }
      }
 
      return $Status;
      
  }
  #funciones genericas:
  function CalculaEdad($fecha){
    list($m,$d,$Y) = explode("/",$fecha);
    return( date("md") < $m.$d ? date("Y")-$Y-1 : date("Y")-$Y );
  }
  
  
  
  
  
  #FUNCIONES BROKERS:
  /*function send_email_brokers(){
      include("cn_usuarios.php");
      $error = '0';
      //variables:
      $id= $_POST['clave'];
      $htmlEmail = "";
      $conexion->autocommit(FALSE);
      $transaccion_exitosa = true;
      #1- First Step: Consult the general information from the Endorsement with the id.
      $endorsement_query = "SELECT A.iConsecutivo,iConsecutivoCompania, sNombreCompania,iConsecutivoTipoEndoso, sDescripcion AS TipoEndoso, eStatus, iReeferYear,
                            iTrailerExchange, iPDAmount, iPDApply, iConsecutivoOperador, iConsecutivoUnidad, eAccion, sComentarios, sNumPolizas
                            FROM cb_endoso A
                            LEFT JOIN ct_companias B ON A.iConsecutivoCompania = B.iConsecutivo
                            LEFT JOIN ct_tipo_endoso C ON A.iConsecutivoTipoEndoso = C.iConsecutivo
                            WHERE A.iConsecutivo = '$id'";
      $result = $conexion->query($endorsement_query);
      $rows = $result->num_rows; 
      $rows > 0 ? $endorsement = $result->fetch_assoc() : $endorsement = "";
      if($endorsement['iConsecutivo'] != ""){    
          #2- Second step: Check the endorsement type.
          
          //DRIVERS
          if($endorsement['iConsecutivoTipoEndoso'] == '2' && $endorsement['iConsecutivoOperador'] != ''){ 
             #Driver: Consult the driver information:
             $driver_query = "SELECT iConsecutivo, sNombre,DATE_FORMAT(dFechaNacimiento,'%m/%d/%Y') AS dFechaNacimiento, DATE_FORMAT(dFechaExpiracionLicencia,'%m/%d/%Y') AS dFechaExpiracionLicencia,iExperienciaYear,
                              iNumLicencia,dFechaContratacion,eTipoLicencia FROM ct_operadores 
                              WHERE iConsecutivo = '".$endorsement['iConsecutivoOperador']."'";
             $result_d = $conexion->query($driver_query);
             $rows_d = $result_d->num_rows; 
             $rows_d > 0 ? $driver = $result_d->fetch_assoc() : $driver = "";
             
             if($driver['sNombre'] != ''){

                 #TRAER DATOS DE LA POLIZA QUE SELECCIONO LA COMPANIA:
                 $PolizasEndoso = explode('|',$endorsement['sNumPolizas']);
                 $filtro = "";
                 $subject_policy = "";
                 for ($i = 0; $i < count($PolizasEndoso); $i++) { 
                    $poliza = explode('/',$PolizasEndoso[$i]);
                    $filtro == "" ? $filtro .= "(sNumeroPoliza = '".$poliza[0]."' AND iTipoPoliza = '".$poliza[1]."')" : $filtro .= " OR (sNumeroPoliza = '".$poliza[0]."' AND iTipoPoliza = '".$poliza[1]."')"; 
                 }
                 
                 $filtro = " AND (".$filtro.")";
                 //
                 $brokers_query = "SELECT A.iConsecutivoBrokers, sName, sEmail FROM ct_polizas A ".
                                  "LEFT JOIN ct_brokers C ON A.iConsecutivoBrokers = C.iConsecutivo ".
                                  "LEFT JOIN ct_tipo_poliza B ON A.iTipoPoliza = B.iConsecutivo ".
                                  "WHERE iConsecutivoCompania= '".$endorsement['iConsecutivoCompania']."' ".
                                  "$filtro GROUP BY iConsecutivoBrokers "; 
                 $result_brokers = $conexion->query($brokers_query);
                 $rows_brokers = $result_brokers->num_rows; //<---Number of emails to send.
                 if($rows_brokers > 0){
                   while ($brokers = $result_brokers->fetch_assoc()){
                       $htmlEmail = "";
                       #Get policy descriptions:
                       $policy_query = "SELECT A.iConsecutivo, CONCAT(D.sDescripcion,' - ',sNumeroPoliza) AS policy ".
                                       "FROM ct_polizas A LEFT JOIN ct_tipo_poliza D ON A.iTipoPoliza = D.iConsecutivo ".
                                       "WHERE iConsecutivoCompania = '".$endorsement['iConsecutivoCompania']."' AND iConsecutivoBrokers = '".$brokers['iConsecutivoBrokers']."' ".
                                       "AND A.iDeleted = '0' AND (D.iConsecutivo = '1' OR D.iConsecutivo = '2' OR D.iConsecutivo = '3' OR D.iConsecutivo = '5')"; 
                       $result_policy = $conexion->query($policy_query);
                       $rows_policy = $result_policy->num_rows; //<---Number policies of broker.
                       $subject_policy = "";
                       if($rows_policy > 0){
                           while($policies = $result_policy->fetch_assoc()){
                               $subject_policy == '' ? $subject_policy = $policies['policy'] : $subject_policy .= '//'.$policies['policy'];
                           }   
                       }
                       
                       
                        #action to define the subject:
                        switch($endorsement["eAccion"]){ 
                            case 'A': 
                                $action = 'Please add to my policy the following driver.';
                                $subject = "Endorsement application - please add the following driver from policy number: ".$endorsement['sNombreCompania'].", ".$subject_policy; 
                                break; 
                            case 'D': 
                                $action = 'Please delete from my policy the following driver.';                                                                   
                                $subject = "Endorsement application - please delete the following driver from policy number: ".$endorsement['sNombreCompania'].", ".$subject_policy; 
                            break;
                        } 
                         
                         
                        #Building Email Body:                                   
                        require_once("./lib/phpmailer_master/class.phpmailer.php");
                        require_once("./lib/phpmailer_master/class.smtp.php");
                        
                        //header
                        $htmlEmail .= "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"\"http://www.w3.org/TR/html4/strict.dtd\"><html>
                                      <head>
                                          <meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\">
                                          <title>endorsement from solo-trucking insurance</title>
                                      </head>";
                        //Body
                        $htmlEmail .= "<body>".
                                      "<table style=\"font-size:12px;border:1px solid #6191df;border-radius:3px;padding:10px;width:95%; margin:5px auto;font-family: Arial, Helvetica, sans-serif;\">".
                                      "<tr><td>".
                                      "<h2 style=\"color:#313131;text-transform: uppercase; text-align:center;\">Endorsement application from Solo-Trucking Insurance</h2> \n".
                                      "</td></tr>". 
                                      "<tr><td>".
                                      "<p style=\"color:#000;margin:5px auto; text-align:left;\">$action</p><br><br>".
                                      "</td></tr>".
                                      "<tr><td>".
                                      "<ul style=\"color:#010101;line-height:15px;list-style:none;\"> ".
                                      "<li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">NAME: </strong>".$driver['sNombre']."</li>".
                                      "<li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">DOB: </strong>".$driver['dFechaNacimiento']."</li>".
                                      "<li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">YOE: </strong>".$driver['iExperienciaYear']."</li>".
                                      "<li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">LICENSE EXP: </strong>".$driver['dFechaExpiracionLicencia']."</li>".
                                      "<li style=\"line-height:15px;\"><strong style=\"color:#044e8d;\">LICENSE NUMBER: </strong>".$driver['iNumLicencia']."</li>".
                                      "</ul>".
                                      "</td></tr>".
                                      "<tr><td><p style=\"color:#010101;margin:5px auto 10px; text-align:left;font-size:11px;\">Please reply this email to the account:<a href=\"mailto:customerservice@solo-trucking.com\"> customerservice@solo-trucking.com</a></p></td></tr>".
                                      "<tr><td><p style=\"color:#858585;margin:5px auto; text-align:left;font-size:10px;\">e-mail sent from Solo-trucking Insurance System.</p></td></tr>".
                                      "</table>".
                                      "</body>";
                        //footer              
                        $htmlEmail .= "</html>"; 
                            
                        #TERMINA CUERPO DEL MENSAJE
                        $mail = new PHPMailer();   
                        $mail->IsSMTP(); // telling the class to use SMTP
                        $mail->Host       = "mail.solo-trucking.com"; // SMTP server
                        //$mail->SMTPDebug  = 2; // enables SMTP debug information (for testing) 1 = errors and messages 2 = messages only
                        $mail->SMTPAuth   = true;                  // enable SMTP authentication
                        $mail->SMTPSecure = "TLS";                 // sets the prefix to the servier
                        $mail->Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
                        $mail->Port       = 587;                   // set the SMTP port for the GMAIL server
                        $mail->Username   = "systemsupport@solo-trucking.com";  // GMAIL username
                        $mail->Password   = "SL09100242"; 
                        $mail->SetFrom('systemsupport@solo-trucking.com', 'Solo-Trucking Insurance');
                        $mail->AddReplyTo('customerservice@solo-trucking.com','Customer service Solo-Trucking');
                        $mail->Subject    = $subject;
                        $mail->AltBody    = "To view the message, please use an HTML compatible email viewer!";  // optional, comment out and test
                        $mail->MsgHTML($htmlEmail);
                        $mail->IsHTML(true); 
                        $email_destinatario = trim($brokers['sEmail']); 
                        $nombre_destinatario = trim($brokers['sName']);
                        $mail->AddAddress($email_destinatario,$nombre_destinatario);
                        //$mail->AddAddress('celina@globalpc.net','Celina');
                       // $mail->AddAddress('celina@websolutionsac.com','Magaly'); 
                        //Revisar si se necesitan enviar archivos adjuntos:
                        if($endorsement['eAccion'] == 'A'){
                            include("./lib/fpdf153/fpdf.php"); // libreria fpdf 
                            //Consult the driver files:
                            $driver_files_query = "SELECT iConsecutivo, sNombreArchivo, eArchivo, hContenidoDocumentoDigitalizado, sTipoArchivo, iTamanioArchivo  
                                                   FROM cb_operador_files 
                                                   WHERE  iConsecutivoOperador = '".$endorsement['iConsecutivoOperador']."'";
                            
                           $result_files = $conexion->query($driver_files_query);
                           $rows_files = $result_files->num_rows; 
                           if($rows_files > 0){
                                while ($files = $result_files->fetch_assoc()){
                                   #Here will constructed the temporary files: 
                                   if($files['sNombreArchivo'] != ""){ 
                                     $file_tmp = fopen('tmp/'.$files["sNombreArchivo"],"w") or die("Error when creating the file. Please check."); 
                                     fwrite($file_tmp,$files["hContenidoDocumentoDigitalizado"]); 
                                     fclose($file_tmp);     
                                     $archivo = "tmp/".$files["sNombreArchivo"];  
                                     $mail->AddAttachment($archivo);
                                     $delete_files .= "unlink(\"tmp/\".".$files["sNombreArchivo"].");"; 
                                   }
                                   
                                }
                           } 
                        }
                        $mail_error = false;
                        if(!$mail->Send()){$mail_error = true; $mail->ClearAddresses();}
                        if(!$mail_error){
                            //$mensaje = "Mail confirmation to the user $usuario was successfully sent.";
                            
                        }else{
                            $mensaje = "Error: The e-mail cannot be sent.";
                            $error = "1";            
                        }
                        $mail->ClearAttachments();
                        #deleting files attachment:
                        eval($delete_files);
                        
                   }//END BROKERS WHILE...
                    
                 }else{
                     $error = '1';
                     $msj= "The data policy was not found, please try again.";
                 }
                 
                 
                 #VERIFICAR SI SE ENVIARON LOS CORREOS CORRECTAMENTE:
                 if($error == '0'){
                    #UPDATE ENDORSEMENT DETAILS:
                    array_push($valores_endoso ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
                    array_push($valores_endoso ,"sIP='".$_SERVER['REMOTE_ADDR']."'");
                    array_push($valores_endoso ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'"); 
                    $sql_endoso = "UPDATE cb_endoso SET eStatus = 'SB' WHERE iConsecutivo = '".$endorsement['iConsecutivo']."'"; 
                    $conexion->query($sql_endoso);
                       if($conexion->affected_rows < 1){
                            $transaccion_exitosa = false;
                            $msj = "The data of endorsement was not updated properly, please try again.";
                       }else{
                            $msj = "The Endorsement was sent successfully, please check your email (customerservice@solo-trucking.com) waiting for their response."; 
                       } 
  
                 }
  
             }else{
                 $error = '1';
                 $msj = "The data driver was not found, please try again.";
             }
                               
          }
          
          //UNITS
          if($endorsement['iConsecutivoTipoEndoso'] == '1' && $endorsement['iConsecutivoUnidad'] != ''){
             #Unit: Consult the unit information:
             $unit_query = "SELECT A.iConsecutivo, iConsecutivoCompania, iYear, iModelo, sVIN, sModelo, B.sAlias, B.sDescripcion AS Make, C.sDescripcion AS Radius ".  
                             "FROM ct_unidades A ".
                             "LEFT JOIN ct_unidad_modelo B ON A.iModelo = B.iConsecutivo ".
                             "LEFT JOIN ct_unidad_radio C ON A.iConsecutivoRadio = C.iConsecutivo ".
                             "WHERE A.iConsecutivo = '".$endorsement['iConsecutivoUnidad']."'";
             $result_d = $conexion->query($unit_query);
             $rows_d = $result_d->num_rows; 
             $rows_d > 0 ? $unit = $result_d->fetch_assoc() : $unit = "";
             
             if($unit['iConsecutivo'] != ''){

                 #TRAER DATOS DE LA POLIZA QUE SELECCIONO LA COMPANIA:
                 $PolizasEndoso = explode('|',$endorsement['sNumPolizas']);
                 $filtro = "";
                 $subject_policy = "";
                 for ($i = 0; $i < count($PolizasEndoso); $i++) { 
                    $poliza = explode('/',$PolizasEndoso[$i]);
                    $filtro == "" ? $filtro .= "(sNumeroPoliza = '".$poliza[0]."' AND iTipoPoliza = '".$poliza[1]."')" : $filtro .= " OR (sNumeroPoliza = '".$poliza[0]."' AND iTipoPoliza = '".$poliza[1]."')"; 
                 }
                 
                 $filtro = " AND (".$filtro.")";
                 //
                 $brokers_query = "SELECT A.iConsecutivoBrokers, sName, sEmail FROM ct_polizas A ".
                                  "LEFT JOIN ct_brokers C ON A.iConsecutivoBrokers = C.iConsecutivo ".
                                  "LEFT JOIN ct_tipo_poliza B ON A.iTipoPoliza = B.iConsecutivo ".
                                  "WHERE iConsecutivoCompania= '".$endorsement['iConsecutivoCompania']."' ".
                                  "$filtro GROUP BY iConsecutivoBrokers ";
                 $result_brokers = $conexion->query($brokers_query);
                 $rows_brokers = $result_brokers->num_rows; //<---Number of emails to send.
                 if($rows_brokers > 0){
                   while ($brokers = $result_brokers->fetch_assoc()){
                       $htmlEmail = "";
                       $pd = false; // Bandera para saber si la compania tiene poliza de PD.
                       #Get policy descriptions:
                       $policy_query = "SELECT A.iConsecutivo, CONCAT(D.sDescripcion,' - ',sNumeroPoliza) AS policy ".
                                       "FROM ct_polizas A LEFT JOIN ct_tipo_poliza D ON A.iTipoPoliza = D.iConsecutivo ".
                                       "WHERE iConsecutivoCompania = '".$endorsement['iConsecutivoCompania']."' AND iConsecutivoBrokers = '".$brokers['iConsecutivoBrokers']."' ".
                                       "AND A.iDeleted = '0' AND (D.iConsecutivo = '1' OR D.iConsecutivo = '2' OR D.iConsecutivo = '3' OR D.iConsecutivo = '5')";   
                       $result_policy = $conexion->query($policy_query);
                       $rows_policy = $result_policy->num_rows; //<---Number policies of broker.
                       $subject_policy = "";
                       if($rows_policy > 0){
                           while($policies = $result_policy->fetch_assoc()){
                               $subject_policy == '' ? $subject_policy = $policies['policy'] : $subject_policy .= '//'.$policies['policy'];
                               if($policies['iTipoPoliza'] == '1'){$pd = true;} // si es verdadero si tiene poliza de tipo PD
                           }   
                       }
                      
                      #MAKE
                      if($unit['sAlias'] != ''){$make = $unit['sAlias'];}else if($unit['Make'] != ''){$make = $unit['Make']; }
                              
                      #action to define the subject an body email: 
                      if($endorsement["eAccion"] == 'A'){
                            $action = 'Please add to my policy the following Unit.';
                            $subject = "Endorsement application - please add the following unit from policy number: ".$endorsement['sNombreCompania'].", ".$subject_policy;
                            
                            #PDAmount
                            $pd && $endorsement["iPDApply"] == '1' && $endorsement["iPDAmount"] != '' ? $PDAmount = number_format($endorsement["iPDAmount"]) : $PDAmount = '';
                            
                            #RADIUS:
                            $radius = explode('(',$unit['Radius']);
                            
                            $bodyData = "<p style=\"color:#000;margin:5px auto; text-align:left;\">".$unit['iYear']."&nbsp;".$make."&nbsp;".$unit['sVIN']."&nbsp;".$radius[0]."&nbsp;11-22&nbsp;TONS&nbsp;".$PDAmount."</p><br><br>";
                          
                      }else if($endorsement["eAccion"] == 'D'){
                           $action = 'Please delete of my policy the following Unit.';                                                                   
                           $subject = "Endorsement application - please delete the following unit from policy number: ".$endorsement['sNombreCompania'].", ".$subject_policy;
                           $bodyData = "<p style=\"color:#000;margin:5px auto; text-align:left;\">".$unit['iYear']."&nbsp;".$make."&nbsp;".$unit['sVIN']."</p><br><br>";
                      } 
                    #Building Email Body:                                   
                    require_once("./lib/phpmailer_master/class.phpmailer.php");
                    require_once("./lib/phpmailer_master/class.smtp.php");
                    
                    //header
                    $htmlEmail .= "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"\"http://www.w3.org/TR/html4/strict.dtd\"><html>".
                                    "<head><meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\">".
                                    "<title>Endorsement from Solo-Trucking Insurance</title></head>"; 
                     
                     //Body - Armar Body dependiendo el tipo de poliza:
                    $htmlEmail .= "<body>".
                                  "<table style=\"font-size:12px;border:1px solid #6191df;border-radius:3px;padding:10px;width:95%; margin:5px auto;font-family: Arial, Helvetica, sans-serif;\">".
                                  "<tr><td>".
                                  "<h2 style=\"color:#313131;text-transform: uppercase; text-align:center;\">Endorsement application from Solo-Trucking Insurance</h2>".
                                  "</td></tr>".
                                  "<tr><td>".
                                  "<p style=\"color:#000;margin:5px auto; text-align:left;\">$action</p><br><br>".
                                  "</td></tr>".
                                  "<tr><td>".$bodyData."</td></tr>".
                                  "<tr>".
                                  "<td><p style=\"color:#858585;margin:5px auto; text-align:left;font-size:10px;\">e-mail sent from Solo-trucking Insurance System.</p></td>".
                                  "</tr>".
                                  "</table>".
                                  "</body>";   
                        
                     //footer              
                     $htmlEmail .= "</html>"; 
                    
                     #TERMINA CUERPO DEL MENSAJE
                     $mail = new PHPMailer();   
                     $mail->IsSMTP(); // telling the class to use SMTP
                     $mail->Host       = "mail.solo-trucking.com"; // SMTP server
                     //$mail->SMTPDebug  = 2; // enables SMTP debug information (for testing) 1 = errors and messages 2 = messages only
                     $mail->SMTPAuth   = true;                  // enable SMTP authentication
                     $mail->SMTPSecure = "TLS";                 // sets the prefix to the servier
                     $mail->Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
                     $mail->Port       = 587;                   // set the SMTP port for the GMAIL server
                     $mail->Username   = "systemsupport@solo-trucking.com";  // GMAIL username
                     $mail->Password   = "SL09100242"; 
                     $mail->SetFrom('systemsupport@solo-trucking.com', 'Solo-Trucking Insurance');
                     $mail->AddReplyTo('customerservice@solo-trucking.com','Customer service Solo-Trucking');
                     $mail->Subject    = $subject;
                     $mail->AltBody    = "To view the message, please use an HTML compatible email viewer!";  // optional, comment out and test
                     $mail->MsgHTML($htmlEmail);
                     $mail->IsHTML(true); 
                     $email_destinatario = trim($brokers['sEmail']); 
                     $nombre_destinatario = trim($brokers['sName']);
                     $mail->AddAddress($email_destinatario,$nombre_destinatario);
                     //$mail->AddAddress('celina@websolutionsac.com, celina@globalpc.net','Celina,Magaly');
                     
                     //Revisar si se necesitan enviar archivos adjuntos:
                     include("./lib/fpdf153/fpdf.php"); // libreria fpdf 
                     
                     //Consult the driver files:
                     $driver_files_query = "SELECT iConsecutivo, sNombreArchivo, eArchivo, hContenidoDocumentoDigitalizado, sTipoArchivo, iTamanioArchivo ". 
                                           "FROM cb_unidad_files ". 
                                           "WHERE  iConsecutivoUnidad = '".$endorsement['iConsecutivoUnidad']."'";   
                     $result_files = $conexion->query($driver_files_query);
                     $rows_files = $result_files->num_rows; 
                     if($rows_files > 0){
                            while ($files = $result_files->fetch_assoc()){
                               #Here will constructed the temporary files: 
                               if($files['sNombreArchivo'] != ""){
                               
                                 if($endorsement["eAccion"] == 'A' && $files['eArchivo'] == 'TITLE'){ #<--- Si es un ADD verificar que el arhcivo que enviemos sea TITLE
                                    $file_tmp = fopen('tmp/'.$files["sNombreArchivo"],"w") or die("Error when creating the file. Please check."); 
                                    fwrite($file_tmp,$files["hContenidoDocumentoDigitalizado"]); 
                                    fclose($file_tmp);     
                                    $archivo = "tmp/".$files["sNombreArchivo"];  
                                    $mail->AddAttachment($archivo);
                                    $delete_files .= "unlink(\"tmp/\".".$files["sNombreArchivo"].");";  
                                 }else if($endorsement["eAccion"] == 'D' && ($files['eArchivo'] == 'DA' || $files['eArchivo'] == 'BS' || $files['eArchivo'] == 'NOR' || $files['eArchivo'] == 'PTL')){
                                    $file_tmp = fopen('tmp/'.$files["sNombreArchivo"],"w") or die("Error when creating the file. Please check."); 
                                    fwrite($file_tmp,$files["hContenidoDocumentoDigitalizado"]); 
                                    fclose($file_tmp);     
                                    $archivo = "tmp/".$files["sNombreArchivo"];  
                                    $mail->AddAttachment($archivo);
                                    $delete_files .= "unlink(\"tmp/\".".$files["sNombreArchivo"].");"; 
                                 }
                               }
                               
                            }
                     } 
          
                    $mail_error = false;
                    if(!$mail->Send()){$mail_error = true; $mail->ClearAddresses();}
                    if(!$mail_error){
                        //$mensaje = "Mail confirmation to the user $usuario was successfully sent.";  
                    }else{
                        $mensaje = "Error: The e-mail cannot be sent.";
                        $error = "1";            
                    }
                    $mail->ClearAttachments();
                    #deleting files attachment:
                    eval($delete_files);
                        
                   }//END BROKERS WHILE... 
                 }else{
                     $error = '1';
                     $msj= "The data policy was not found, please try again.";
                 }
                 
                 
                 #VERIFICAR SI SE ENVIARON LOS CORREOS CORRECTAMENTE:
                 if($error == '0'){
                    #UPDATE ENDORSEMENT DETAILS:
                    array_push($valores_endoso ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
                    array_push($valores_endoso ,"sIP='".$_SERVER['REMOTE_ADDR']."'");
                    array_push($valores_endoso ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'"); 
                    $sql_endoso = "UPDATE cb_endoso SET eStatus = 'SB' WHERE iConsecutivo = '".$endorsement['iConsecutivo']."'"; 
                    $conexion->query($sql_endoso);
                       if($conexion->affected_rows < 1){
                            $transaccion_exitosa = false;
                            $msj = "The data of endorsement was not updated properly, please try again.";
                       }else{
                            $msj = "The Endorsement was sent successfully, please check your email (customerservice@solo-trucking.com) waiting for their response."; 
                       } 
  
                 }
  
             }else{
                 $error = '1';
                 $msj = "The data driver was not found, please try again.";
             } 
          } 
          
            
      }else{
          $error = '1';
          $msj = "The endorsement data was not found, please try again.";
      }
      
      if($transaccion_exitosa){
            $conexion->commit();
            $conexion->close();
      }else{
            $conexion->rollback();
            $conexion->close();
            $error = "1";
      }
      $response = array("error"=>"$error","msj"=>"$msj");
      echo json_encode($response);
            
  }*/ 
  
?>
