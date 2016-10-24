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
     $filtroQuery = " WHERE B.eStatus != 'E' AND iConsecutivoTipoEndoso = '1' ";
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
                  "LEFT JOIN ct_unidades F ON B.iConsecutivoUnidad = F.iConsecutivo ".
                  "LEFT JOIN ct_polizas P ON A.iConsecutivoPoliza = P.iConsecutivo ". 
                  "LEFT JOIN ct_brokers BR ON P.iConsecutivoBrokers = BR.iConsecutivo ".$filtroQuery;
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
      $sql = "SELECT B.iConsecutivo, D.sNombreCompania,  DATE_FORMAT(B.dFechaAplicacion,  '%m/%d/%Y %H:%i') AS dFechaIngreso, C.sDescripcion, A.eStatus, eAccion, D.iOnRedList, sVIN, sNumeroPoliza, sName AS broker, A.iConsecutivoPoliza ".
             "FROM cb_endoso_estatus A ".
             "LEFT JOIN cb_endoso B ON A.iConsecutivoEndoso = B.iConsecutivo ".
             "LEFT JOIN ct_tipo_endoso C ON B.iConsecutivoTipoEndoso = C.iConsecutivo  ".
             "LEFT JOIN ct_companias D ON B.iConsecutivoCompania = D.iConsecutivo  ".
             "LEFT JOIN ct_unidades F ON B.iConsecutivoUnidad = F.iConsecutivo ".
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
                                       "<td>".strtoupper($usuario['sVIN'])."</td>". 
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
                                       if($policies['TipoPoliza'] == '1' && $data['iPDApply'] == '1' && $data['eAccion'] == 'ADD' && $data['iConsecutivoTipoEndoso'] == '1'){
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
            
            if($data['iConsecutivoUnidad']!= '' && $data['iConsecutivoTipoEndoso']== '1'){
                $sql2 = "SELECT B.sAlias, B.sDescripcion, iConsecutivoRadio, C.sDescripcion AS Radius, iYear, iModelo, sVIN, sModelo, sTipo ".    
                        "FROM ct_unidades A ".
                        "LEFT JOIN ct_unidad_modelo B ON A.iModelo = B.iConsecutivo ".
                        "LEFT JOIN ct_unidad_radio C ON A.iConsecutivoRadio = C.iConsecutivo ". 
                        "WHERE A.iConsecutivo = '".$data['iConsecutivoUnidad']."'";
                $result = $conexion->query($sql2);
                $items2 = $result->num_rows;
                if ($items2 > 0) {
                     $data2 = $result->fetch_assoc();
                     
                     if($data2['iModelo'] != ""){
                        if($data2['sAlias'] != ""){$fields .= "\$('#$domroot :input[id=Modelo]').val('".$data2['sAlias']."');";}
                        else{$fields .= "\$('#$domroot :input[id=Modelo]').val('".$data2['sDescripcion']."');"; } 
                     }else{
                        $fields .= "\$('#$domroot :input[id=Modelo]').val('".$data2['sModelo']."');"; 
                     }
                     
                     #RADIUS:
                     $radius = explode('(',$data2['Radius']);
                     $data2['Radius'] = $radius[0]; 

                     $llaves2  = array_keys($data2);
                     $datos2   = $data2;
                     foreach($datos2 as $i => $b){     
                        if($i != 'iModelo' || $i != 'sModelo' || $i != 'sDescripcion' || $i != 'sAlias'){
                            $fields .= "\$('#$domroot :input[id=".$i."]').val('".$datos2[$i]."');";
                        }  
                     }
                     
                     //Get files from UNIT:    
                     $sql_files = "SELECT iConsecutivo, sNombreArchivo, eArchivo, sTipoArchivo FROM cb_unidad_files WHERE  iConsecutivoUnidad = '".$data['iConsecutivoUnidad']."'";
                     $result_files = $conexion->query($sql_files);
                     $file_rows  = $result_files->num_rows;
                         
                     if($file_rows > 0){ 
                         $htmlFiles = "<thead><tr>"."<td><label>File Name</label></td>"."<td><label>Preview</label></td>"."</tr><thead>"; 
                         while ($items_files = $result_files->fetch_assoc()){
                             //Define icono del boton:
                            $btnIcon = "";
                            if($items_files['sTipoArchivo'] == 'application/pdf'){$btnIcon = "fa-file-pdf-o";}else if($items_files['sTipoArchivo'] == 'image/jpeg'){$btnIcon = "fa-file-image-o";}
                            
                             if($data['eAccion'] == 'ADD' && $items_files["eArchivo"] == 'TITLE'){ 
                                $htmlFiles .= "<tr>".
                                              "<td>".$items_files['sNombreArchivo']."</td>".
                                              "<td><div onclick=\"window.open('open_pdf.php?idfile=".$items_files['iConsecutivo']."&type=unit');\" class=\"btn-icon pdf btn-left\" title=\"View PDF File\"><i class=\"fa $btnIcon\"></i><span></span></td>".
                                              "</tr>"; 
                                              
                             }else if($data['eAccion'] == 'DELETE' && $items_files["eArchivo"] != 'TITLE'){
                                $htmlFiles .= "<tr>".
                                              "<td>".$items_files['sNombreArchivo']."</td>".
                                              "<td><div onclick=\"window.open('open_pdf.php?idfile=".$items_files['iConsecutivo']."&type=unit');\" class=\"btn-icon pdf btn-left\" title=\"View PDF File\"><i class=\"fa $btnIcon\"></i><span></span></td>".
                                              "</tr>"; 
                             }
                         } 
                     }else{$htmlFiles = "";} 
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
  

  #FUNCIONES BROKERS:
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
      $endorsement_query = "SELECT A.iConsecutivo, A.iConsecutivoCompania, sNombreCompania,iConsecutivoTipoEndoso,iConsecutivoUnidad, eAccion, sNumeroPoliza, T.sDescripcion AS Tipo, sEmail AS Broker, iPDAmount, iPDApply, P.iTipoPoliza
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
      if($endorsement['iConsecutivo'] != "" && $endorsement['iConsecutivoTipoEndoso'] == '1' && $endorsement['iConsecutivoUnidad'] != ''){  

          
             #Unit: Consult the unit information:
             $unit_query = "SELECT A.iConsecutivo, iConsecutivoCompania, iYear, iModelo, sVIN, sModelo, B.sAlias, B.sDescripcion AS Make, C.sDescripcion AS Radius, sTipo ".  
                           "FROM ct_unidades A ".
                           "LEFT JOIN ct_unidad_modelo B ON A.iModelo = B.iConsecutivo ".
                           "LEFT JOIN ct_unidad_radio C ON A.iConsecutivoRadio = C.iConsecutivo ".
                           "WHERE A.iConsecutivo = '".$endorsement['iConsecutivoUnidad']."'";
             $result_d = $conexion->query($unit_query);
             $rows_d = $result_d->num_rows; 
             $rows_d > 0 ? $unit = $result_d->fetch_assoc() : $unit = "";
             
             $subject_policy = $endorsement['sNumeroPoliza'].'-'.$endorsement['Tipo'];
                 
                      
             #MAKE
             if($unit['sAlias'] != ''){$make = $unit['sAlias'];}else if($unit['Make'] != ''){$make = $unit['Make']; }
                              
             #action to define the subject an body email: 
             if($endorsement["eAccion"] == 'A'){
                    $action = 'Please add to my policy the following '.strtolower($unit['sTipo']).'.';
                    $subject = "Endorsement application - please add the following ".strtolower($unit['sTipo'])." from policy number: ".$endorsement['sNombreCompania'].", ".$subject_policy;
                    
                    #PDAmount
                    $endorsement['iTipoPoliza'] == '1' && $endorsement["iPDApply"] == '1' && $endorsement["iPDAmount"] != '' ? $PDAmount = number_format($endorsement["iPDAmount"]) : $PDAmount = '';
                    
                    #RADIUS:
                    $radius = explode('(',$unit['Radius']);
                    
                    $bodyData = "<p style=\"color:#000;margin:5px auto; text-align:left;\">".$unit['iYear']."&nbsp;".$make."&nbsp;".$unit['sVIN']."&nbsp;".$radius[0]."&nbsp;11-22&nbsp;TONS&nbsp;".$PDAmount."</p><br><br>";
                  
             }else if($endorsement["eAccion"] == 'D'){
                   $action = 'Please delete of my policy the following '.strtolower($unit['sTipo']).'.';                                                                   
                   $subject = "Endorsement application - please delete the following ".strtolower($unit['sTipo'])." from policy number: ".$endorsement['sNombreCompania'].", ".$subject_policy;
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
                                  "<tr><td><p style=\"color:#010101;margin:5px auto 10px; text-align:left;font-size:11px;\">Please reply this email to the account:<a href=\"mailto:customersolotrucking@gmail.com\"> customersolotrucking@gmail.com</a></p></td></tr>". 
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
                     $mail->AddReplyTo('customersolotrucking@gmail.com','Customer service Solo-Trucking');
                     $mail->Subject    = $subject;
                     $mail->AltBody    = "To view the message, please use an HTML compatible email viewer!";  // optional, comment out and test
                     $mail->MsgHTML($htmlEmail);
                     $mail->IsHTML(true); 
                     $email_destinatario = trim($endorsement['Broker']); 
                     $nombre_destinatario = trim($endorsement['NameBroker']);
                     $mail->AddAddress($email_destinatario,$nombre_destinatario);
                     //$mail->AddAddress('celina@websolutionsac.com','Celina,Magaly');
                     
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
                 
                 #VERIFICAR SI SE ENVIARON LOS CORREOS CORRECTAMENTE:
                 if($error == '0'){
                    #UPDATE ENDORSEMENT DETAILS:
                    $sql_endoso = "UPDATE cb_endoso_estatus SET eStatus = 'SB', dFechaActualizacion='".date("Y-m-d H:i:s")."', sIP='".$_SERVER['REMOTE_ADDR']."', sUsuarioActualizacion='".$_SESSION['usuario_actual']."' ".
                                  "WHERE iConsecutivoEndoso = '$id' AND iConsecutivoPoliza = '$idPoliza'"; 
                    $conexion->query($sql_endoso);
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

  
?>
