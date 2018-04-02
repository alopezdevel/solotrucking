<?php
  session_start();
  // Generic functions lib 
  include("functiones_genericas.php"); 
  $_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
  define('USER',$_SESSION['usuario_actual']); // Constante UserId 
  
  function get_company_certificate(){
    include("cn_usuarios.php");
    $company = $_SESSION['company'];
    $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
    $transaccion_exitosa = true;
    
    #VERIFICAR INFORMACION DEL CERTIFICADO:
    $sql    = "SELECT iConsecutivo, DATE_FORMAT(dFechaVencimiento,'%m/%d/%Y') AS dFechaVencimiento, IF(dFechaVencimiento != '' AND dFechaVencimiento >= CURDATE(), 'OK','VENCIDO') AS EstatusCert ".
              "FROM cb_certificate_file WHERE iConsecutivoCompania = '$company' ";
    $result = $conexion->query($sql);
    $rows   = $result->num_rows;
    
    $htmlCertificadoInfo  = "<span style=\"display: block;text-align: right;position: relative;top:-50px;font-size: 0.9em;margin-bottom:-30px;right: 15px;\">";
    
    if($rows > 0){ 
          $items = $result->fetch_assoc();
          if($items['EstatusCert'] != 'OK'){
              if($items['dFechaVencimiento'] != ""){
                  $htmlCertificadoInfo .= 'Your certificate has expired, please contact the system administrator to request it.';
                  $htmlCertificadoInfo .= "<br><span style=\"margin-right:10px;\"><b>Expired Date: </b>".$items['dFechaVencimiento']."</span>";
              }else{
                  $htmlCertificadoInfo .= 'Your certificate has not been successfully loaded, please verify with our system administrator: '.
                                          '<br><span style=\"margin-right:10px;\"><b><a href="mailto:systemsupport@solo-trucking.com">systemsupport@solo-trucking.com</a></b></span>';
              }
              $isVencido = true; 
          }else{
              $htmlCertificadoInfo .= 'Your certificate has been successfully loaded, please verify your information before sending it.';
              $htmlCertificadoInfo .= "<br><span style=\"margin-right:10px;\"><b>Expired Date: </b>".$items['dFechaVencimiento']."</span>";
              $isVencido = false;
          }
           
    }else{
        $htmlCertificadoInfo = "";
    }  
    
    $htmlCertificadoInfo .= "</span>"; 
    
    $registros_por_pagina = $_POST["registros_por_pagina"];
    $pagina_actual = (isset($_POST["pagina_actual"]) && $_POST["pagina_actual"] != '' ? $_POST["pagina_actual"] : 1);
    $registros_por_pagina == "" ? $registros_por_pagina = 15 : false;
        
    //Filtros de informacion //
    $filtroQuery = " WHERE A.iConsecutivoCompania = '".$company."'";
    $array_filtros = explode(",",$_POST["filtroInformacion"]);
    foreach($array_filtros as $key => $valor){
        if($array_filtros[$key] != ""){
            $campo_valor = explode("|",$array_filtros[$key]);
            $campo_valor[0] == 'A.iConsecutivo' ? $filtroQuery.= " AND  ".$campo_valor[0]."='".$campo_valor[1]."' " : $filtroQuery == "" ? $filtroQuery.= " AND  ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'": $filtroQuery.= " AND ".$campo_valor[0]." LIKE '%".$campo_valor[1]."%'";
        }
    }
    // ordenamiento//
    $ordenQuery = " ORDER BY ".$_POST["ordenInformacion"]." ".$_POST["sortInformacion"];

    //contando registros // 
    $query_rows = "SELECT COUNT(A.iConsecutivo) AS total FROM  cb_certificate A 
                   LEFT JOIN ct_companias B ON A.iConsecutivoCompania = B.iConsecutivo ".$filtroQuery;
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
        $sql = "SELECT A.iConsecutivo AS clave, email, sCholderA,sCholderB,sCholderC,sCholderD,sCholderE,
                DATE_FORMAT(A.dFechaIngreso,  '%m/%d/%Y')    as dFechaIngreso, 
                DATE_FORMAT(A.dFechaArchivo,  '%m/%d/%Y')    as dFechaArchivo,  sNombreCompania, sUsdot          
                FROM cb_certificate A
                LEFT JOIN ct_companias B ON A.iConsecutivoCompania = B.iConsecutivo ".$filtroQuery.$ordenQuery." LIMIT ".$limite_inferior.",".$limite_superior; 
        $result = $conexion->query($sql);
        $rows = $result->num_rows;    
        if ($rows > 0) {     
            while ($certificates = $result->fetch_assoc()) {
               if($certificates["clave"] != ""){  
                     $variables = "?id=".$company."&ca=".$certificates['sCholderA']."&cb=".$certificates['sCholderB']."&cc=".$certificates['sCholderC']."&cd=".$certificates['sCholderD']."&ce=".$certificates['sCholderE'];                    
                     $htmlTabla .= "<tr>
                                        <td>".$certificates['clave']."</td>".
                                       "<td>".$certificates['email']."</td>".  
                                       "<td>".$certificates['sCholderA']."</td>". 
                                       "<td>".$certificates['dFechaIngreso']."</td>". 
                                       "<td><div class=\"btn_edit btn-icon edit btn-left\" title=\"Edit Layout\"><i class=\"fa fa-pencil-square-o\"></i> <span></span></div>";
                     if(!($isVencido)){
                        $htmlTabla .= "<div class=\"btn-icon btn-left pdf\" title=\"View the PDF\" onclick=\"window.open('pdf_certificate.php".$variables."');\"><i class=\"fa fa-file-pdf-o\"></i> <span></span></div>".
                                      "<div class=\"btn_send_email btn-icon send-email btn-left\" title=\"Send certificate to the customer\"><i class=\"fa fa-envelope\"></i> <span></span></div>";
                     }
                     $htmlTabla .= "<div class=\"btn_delete btn-icon trash btn-left\" title=\"Delete certificate layout\"><i class=\"fa fa-trash\"></i> <span></span></div></td>".                                                                                                                                                                                                        
                                   "</tr>";
                 }else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";}    
            }
            $conexion->rollback();
            $conexion->close();                                                                                                                                                                       
        }else{$htmlTabla .="<tr><td style=\"text-align:center; font-weight: bold;\" colspan=\"100%\">No data available.</td></tr>";}
    }
    $response = array(
        "total"=>"$paginas_total",
        "pagina"=>"$pagina_actual",
        "tabla"=>"$htmlTabla",
        "mensaje"=>"$mensaje",
        "error"=>"$error",
        "tabla"=>"$htmlTabla",
        "certificate_info"=> "$htmlCertificadoInfo"
        );   
    echo json_encode($response);
  } 
  function find_certificate(){
      $error = '0';
      include("cn_usuarios.php");
      $company = $_SESSION['company'];
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      $sql    = "SELECT iConsecutivo FROM cb_certificate_file WHERE iConsecutivoCompania = '$company' AND dFechaVencimiento >= CURDATE() ";
      $result = $conexion->query($sql);
      $rows   = $result->num_rows;    
      if($rows > 0){ 
          $items = $result->fetch_assoc();
          if($items['iConsecutivo'] != ''){
              $msj = 'The Certificate has been found successfully!.'; 
              $certificate_id = $items['iConsecutivo'];
          }else{
              $error= '1';
              $msj = 'Error: Certificate data is not valid, please contact at the system Administrator.';
          } 
      }else{
          $error= '1';
          $msj = 'Error: Certificate data not found.';
      } 
     
      $conexion->rollback();
      $conexion->close(); 
      $response = array("msj"=>"$msj","error"=>"$error","certificate_id"=>"$certificate_id");   
      echo json_encode($response);   

  }
  function save_certificate(){
      $error = '0';  
      $msj = "";  
      //Conexion:
      include("cn_usuarios.php"); 
      $company = $_SESSION['company']; 
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      //arrays para campos de endoso:
      $valores = array();
      $campos  = array();
      
      $_POST['email'] != '' ? $_POST['email'] = strtolower($_POST['email']) : $_POST['email'] = '';  
      //convertir a mayusculas Certificate Holder:
      $_POST['sCholderA'] != '' ? $_POST['sCholderA'] = strtoupper($_POST['sCholderA']) : $_POST['sCholderA'] = '';
      $_POST['sCholderB'] != '' ? $_POST['sCholderB'] = strtoupper($_POST['sCholderB']) : $_POST['sCholderB'] = '';
      $_POST['sCholderC'] != '' ? $_POST['sCholderC'] = strtoupper($_POST['sCholderC']) : $_POST['sCholderC'] = '';
      $_POST['sCholderD'] != '' ? $_POST['sCholderD'] = strtoupper($_POST['sCholderD']) : $_POST['sCholderD'] = '';
      $_POST['sCholderE'] != '' ? $_POST['sCholderE'] = strtoupper($_POST['sCholderE']) : $_POST['sCholderE'] = '';
      
      //Convertir Texto del mensage a HTML:
      //$_POST['sDescription'] =  utf8_encode($_POST['sDescription']);
      
      
      //Verificar si ya existe un certificado para esa cuenta de correo:
      $sql = "SELECT iConsecutivo, email FROM cb_certificate 
              WHERE iConsecutivoCompania = '$company' AND email= '".trim($_POST['email'])."'";
      $result = $conexion->query($sql);
      $rows = $result->num_rows;
      if($rows > 0 ){ 
          #REVISAR SI ES UPDATE 
          $certificate = $result->fetch_assoc();
          if($_POST['edit_mode'] != 'true'){
              $error = '1';
              $mensaje = "Error: There is already a certificate assigned to the email:".trim($_POST['email']).", please check it.";
          }else{
              foreach($_POST as $campo => $valor){
                if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivoCertificate" and $campo != "iConsecutivo" and $campo !='sDescription'){ //Estos campos no se insertan a la tabla
                    array_push($valores,"$campo='".trim($valor)."'"); 
                }
              }  
          }
          
      }else{
         //Verificar si es el primer registro o no:
          $sql = "SELECT iConsecutivo FROM cb_certificate WHERE iConsecutivoCompania = '$company' ORDER BY iConsecutivo DESC LIMIT 1";
          $result = $conexion->query($sql);
          $rows = $result->num_rows; 
          if( $rows > 0){
              $last_certificate = $result->fetch_assoc();
              $iConsecutivo = $last_certificate['iConsecutivo'];
              $iConsecutivo ++;
          }else{
             $iConsecutivo = 1; 
          }
         #ADD NEW CERT:
         if($iConsecutivo != ''){
             foreach($_POST as $campo => $valor){
                if($campo != "accion" and $campo != "edit_mode" and $campo != "iConsecutivoCertificate"){ //Estos campos no se insertan a la tabla
                    if($campo != 'iConsecutivo'){
                        array_push($campos,$campo); 
                        array_push($valores,trim($valor));
                    }else{
                        array_push($campos,$campo); 
                        array_push($valores,trim($iConsecutivo));
                    } 
                }   
             }
         }else{
             $error = '1';
             $mensaje = "Error on data insert, please try again.";
         }  
      }
      
      if($error == '0'){
          if($_POST['edit_mode'] == 'true'){
             #ACTUALIZA DATOS: 
             array_push($valores ,"dFechaActualizacion='".date("Y-m-d H:i:s")."'");
             array_push($valores ,"sIP='".$_SERVER['REMOTE_ADDR']."'");
             array_push($valores ,"sUsuarioActualizacion='".$_SESSION['usuario_actual']."'");
             
             if($_POST['sDescription'] != ''){
               array_push($valores ,"sDescription='".utf8_encode(trim($_POST['sDescription']))."'");  
             } 
             
             $sql_update = "UPDATE cb_certificate SET ".implode(",",$valores)." WHERE iConsecutivo = '".trim($_POST['iConsecutivo'])."' AND iConsecutivoCompania = '$company'"; 
             
             //echo $sql_update;
             //exit;
             $conexion->query($sql_update);
             if($conexion->affected_rows < 1){
                    $transaccion_exitosa = false;
                    $mensaje = "The certificate data was not updated properly, please try again.";
             }else{
                   $mensaje = "The data was updated successfully."; 
             }
          }else{
              #INSERTAR DATOS:
              array_push($campos,"iConsecutivoCompania");
              array_push($valores,$company);
              array_push($campos,"dFechaIngreso");
              array_push($valores,date("Y-m-d H:i:s"));
              array_push($campos,"sIP");
              array_push($valores,$_SERVER['REMOTE_ADDR']);
              array_push($campos,"sUsuarioIngreso");
              array_push($valores,$_SESSION['usuario_actual']);
              $sql_insert = "INSERT INTO cb_certificate (".implode(",",$campos).") VALUES ('".implode("','",$valores)."')";
              
              $conexion->query($sql_insert);
              if($conexion->affected_rows < 1){
                   $transaccion_exitosa = false;
                   $mensaje = "The certificate data was not saved properly, please try again.";
              }else{
                   $mensaje = "The data was saved successfully."; 
              }
          }
      }
      
      if($transaccion_exitosa){
            $conexion->commit();
            $conexion->close();
      }else{
            $conexion->rollback();
            $conexion->close();
            $error = "1";
            $mensaje = "The certificate data was not saved properly, please try again."; 
      }
      $response = array("error"=>"$error","msj"=>"$mensaje");
      echo json_encode($response);
  }
  function get_certificate_data(){
      $error = '0';
      $msj = "";
      $fields = "";
      $clave = trim($_POST['clave']);
      $domroot = $_POST['domroot'];
      include("cn_usuarios.php");
      $company = $_SESSION['company'];
      $conexion->autocommit(FALSE);
      
      $sql = "SELECT iConsecutivo, iConsecutivoCompania, email, sDescription, sCholderA, sCholderB, sCholderC, sCholderD, sCholderE   
              FROM cb_certificate A
              WHERE iConsecutivo = '$clave' AND iConsecutivoCompania = '$company'";
      $result = $conexion->query($sql); 
      $rows = $result->num_rows; 
      
      if($rows > 0){ 
        $data = $result->fetch_assoc();
        $llaves  = array_keys($data);
        $datos   = $data;
        foreach($datos as $i => $b){ 
            if($i == 'sDescription'){
               $descripcion = utf8_decode($datos[$i]); 
            }else{
               $fields .= "\$('#$domroot :input[id=".$i."]').val('".htmlentities($datos[$i])."');\n"; 
            }
        }
      }
      $conexion->rollback();
      $conexion->close(); 
      $response = array("msj"=>"$msj","error"=>"$error","fields"=>"$fields","descripcion"=>"$descripcion");   
      echo json_encode($response);
                                                                                                                         
  }
  function delete_certificate(){
      $error = '0';  
      $msj = "";  
      //Conexion:
      include("cn_usuarios.php"); 
      $company = $_SESSION['company'];
      $conexion->autocommit(FALSE);                                                                                                                                                                                                                                      
      $transaccion_exitosa = true;
      
      $query = "DELETE FROM cb_certificate WHERE iConsecutivo = '".$_POST["clave"]."' AND iConsecutivoCompania = '$company'"; 
      $conexion->query($query);
      $conexion->affected_rows < 1 ? $transaccion_exitosa = false : $transaccion_exitosa = true;
      if($transaccion_exitosa){
        $conexion->commit();
        $conexion->close();
        $msj = '<p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>
                Data has been deleted succesfully!</p>';
      }else{
        $conexion->rollback();
        $conexion->close();
        $msj = "A general system error ocurred : internal error";
        $error = "1";
      }
        
      $response = array("msj"=>"$msj","error"=>"$error");   
      echo json_encode($response);
  }
  
  function send_email_gmail(){
      include("cn_usuarios.php");
      $error = '0';
      //variables:
      $clave= $_POST['clave'];
      $company = $_SESSION['company'];
      $conexion->autocommit(FALSE);
      $transaccion_exitosa = true;
      #1- First Step: Consult the general information from the Endorsement with the id.
      $sql = "SELECT A.iConsecutivo AS clave, email, sCholderA,sCholderB,sCholderC,sCholderD,sCholderE, DATE_FORMAT(A.dFechaIngreso,  '%m/%d/%Y')    as dFechaIngreso, 
                     DATE_FORMAT(A.dFechaArchivo,  '%m/%d/%Y')    as dFechaArchivo,  sNombreCompania, sUsdot, C.iConsecutivo AS iCertificadoPDF, sDescription          
              FROM cb_certificate A
              LEFT JOIN ct_companias B ON A.iConsecutivoCompania = B.iConsecutivo
              LEFT JOIN cb_certificate_file C ON A.iConsecutivoCompania = C.iConsecutivoCompania
              WHERE A.iConsecutivo = '$clave' AND A.iConsecutivoCompania = '$company'";
      $result = $conexion->query($sql);
      $rows = $result->num_rows; 
      $rows > 0 ? $certificate = $result->fetch_assoc() : $certificate = "";
      if($certificate != ''){
           $file = generate_pdf_certificate($company, $certificate['sCholderA'],$certificate['sCholderB'],$certificate['sCholderC'],$certificate['sCholderD'],$certificate['sCholderE']);
           if($file != ''){
               #ARMANDO EMAIL:
               $subject = "sending certificate - ".trim($certificate['sNombreCompania']);
               #Building Email Body:                                   
               require_once("./lib/phpmailer_master/class.phpmailer.php");
               require_once("./lib/phpmailer_master/class.smtp.php"); 
               //header
               $htmlEmail .= "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"\"http://www.w3.org/TR/html4/strict.dtd\"><html>
                              <head><meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\">
                                <title>Sending Certificate from Solo-Trucking Insurance System</title>
                              </head>";
               //Body
               $htmlEmail .= "<body>".
                              "<table style=\"font-size:12px;border:1px solid #6191df;border-radius:3px;padding:10px;width:95%; margin:5px auto;font-family: Arial, Helvetica, sans-serif;\">".
                              "<tr><td><h2 style=\"color:#313131;text-transform: uppercase; text-align:center;\">Certificate from ".trim($certificate['sNombreCompania'])."</h2></td></tr>".
                              "<tr><td><p style=\"color:#5c5c5c;margin:5px auto; text-align:left;\">".utf8_decode($certificate['sDescription'])."</p></tr></td>".
                              "<tr><td></tr></td>".
                              "<tr><td><p style=\"color:#858585;margin:5px auto; text-align:left;font-size:10px;\">E-mail sent from Solo-trucking System.</p></tr></td>".
                              "<tr><td></tr></td>". 
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
                $mail->AddReplyTo(trim($_SESSION["usuario_actual"]),trim($_SESSION["company_name"]));
                $mail->Subject    = $subject;
                $mail->AltBody    = "To view the message, please use an HTML compatible email viewer!";  // optional, comment out and test
                $mail->MsgHTML($htmlEmail);
                $mail->IsHTML(true);  
                $mail->AddAddress(trim($certificate['email']));                          
                $mail->AddAttachment($file);
                $mail_error = false;
                if(!$mail->Send()){
                    $mail_error = true; 
                    $mail->ClearAddresses();
                    //echo "Mailer Error: " . $mail->ErrorInfo;
                }
                if(!$mail_error){$msj = "The e-mail was successfully sent.";}
                else{
                    $msj = "Error: The e-mail cannot be sent.";
                    $error = "1";            
                }
                $mail->ClearAttachments();
                unlink($file); 
           }
          
      }else{
          $error = '1';
          $msj = "Error: Certificate data was not found.";
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
  function generate_pdf_certificate($folio, $ca,$cb,$cc,$cd,$ce){
    require_once('./lib/fpdf153/fpdf.php'); 
    require_once('./lib/merge_pdf/fpdi/fpdi.php');
    include("cn_usuarios.php"); 

    $folio = preg_replace("/%u([0-9a-f]{3,4})/i","&#x\\1;",urldecode($folio)); 
    $folio = html_entity_decode($folio,null,'UTF-8');
    $sql = "SELECT iConsecutivo, hContenidoDocumentoDigitalizado, sNombreArchivo, sTipoArchivo, iTamanioArchivo
            FROM   cb_certificate_file
            WHERE  iConsecutivoCompania = '".$folio."'";
    $result = $conexion->query($sql);
    $NUM_ROWs_Certificates = $result->num_rows;    
    if ($NUM_ROWs_Certificates > 0) {        
        while ($certificates = $result->fetch_assoc()) {
            $contenido = $certificates['hContenidoDocumentoDigitalizado'];
            $nombre = $certificates['sNombreArchivo'];
        }
    }
            
    //proceso de obtener PDF    
    $nombre_pdf = "tmp/$nombre";    
    $pdf_multiple[] = $nombre_pdf;
    $data = $contenido; 
    $error = false;
    $mensaje_error = "";
    if(file_put_contents($nombre_pdf, $data) == FALSE) {
        $mensaje_error = "No se pudo generar correctamente el archivo PDF.";
        $error = true;                   
    } 
    $pdf = new FPDI();
    $pdf->AddPage();
    $pdf->setSourceFile($nombre_pdf);
    $tplIdx = $pdf->importPage(1);
    $pdf->useTemplate($tplIdx,null,null,null,null,true);
    //modificando el PDF
    $pdf->SetFillColor(255,255,255);
    $pdf->SetFont('Arial','B', 15);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial','b',11); 
    //fecha
    $time = time();
    $fecha = date("m/d/Y", $time);
    //$fecha =  '05/15/2017';
    $pdf->SetXY(180, 12);
    $pdf->Cell(29,5,$fecha,0,0,'C',1);
    //Holder
    $holder =  '05/15/2017';
    $pdf->SetXY(10, 233);
    $pdf->Cell(90,24,'',0,0,'C',1);   
    $pdf->SetFont('Arial','B',9);
    //Ca
    $y_holder = 0;
    $y_holder = 235 + 4;
    $pdf->SetXY(12, $y_holder);
    $pdf->Cell(90,4,$ca,0,0,'L',1);   
    
    //Cb
    $y_holder = $y_holder + 4;
    $pdf->SetXY(12, $y_holder);
    $pdf->Cell(90,4,$cb,0,0,'L',1);
    
    //Cc
    $y_holder = $y_holder + 4;
    $pdf->SetXY(12, $y_holder);
    $pdf->Cell(90,4,$cd .' '. $cc.' '.$ce,0,0,'L',1); 
    
    $pdf->Output("tmp/certificate-".strtolower(str_replace(' ','_',$ca)).".pdf","F");
    
    $pdfnew = "tmp/certificate-".strtolower(str_replace(' ','_',$ca)).".pdf";
    unlink ($nombre_pdf);
    return $pdfnew;
    
  }
  
  function send_email(){
      include("cn_usuarios.php");
      $error = '0';
      //variables:
      $clave= $_POST['clave'];
      $company = $_SESSION['company'];
      $conexion->autocommit(FALSE);
      $transaccion_exitosa = true;
      #1- First Step: Consult the general information from the Endorsement with the id.
      $sql = "SELECT A.iConsecutivo AS clave, email, sCholderA,sCholderB,sCholderC,sCholderD,sCholderE, DATE_FORMAT(A.dFechaIngreso,  '%m/%d/%Y')    as dFechaIngreso, 
                     DATE_FORMAT(A.dFechaArchivo,  '%m/%d/%Y')    as dFechaArchivo,  sNombreCompania, sUsdot, C.iConsecutivo AS iCertificadoPDF, sDescription          
              FROM cb_certificate A
              LEFT JOIN ct_companias B ON A.iConsecutivoCompania = B.iConsecutivo
              LEFT JOIN cb_certificate_file C ON A.iConsecutivoCompania = C.iConsecutivoCompania
              WHERE A.iConsecutivo = '$clave' AND A.iConsecutivoCompania = '$company'";
      $result = $conexion->query($sql);
      $rows = $result->num_rows; 
      $rows > 0 ? $certificate = $result->fetch_assoc() : $certificate = "";
      if($certificate != ''){
           $file = generate_pdf_certificate($company, $certificate['sCholderA'],$certificate['sCholderB'],$certificate['sCholderC'],$certificate['sCholderD'],$certificate['sCholderE']);
           if($file != ''){
               #ARMANDO EMAIL:
               $subject = "sending certificate - ".trim($certificate['sNombreCompania']);
               #Building Email Body:                   
               require_once("./lib/mail.php");
               //header
               $htmlEmail .= "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"\"http://www.w3.org/TR/html4/strict.dtd\"><html>
                              <head><meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\">
                                <title>Sending Certificate from Solo-Trucking Insurance System</title>
                              </head>";
               //Body
               $htmlEmail .= "<body>".
                              "<table style=\"font-size:12px;border:1px solid #6191df;border-radius:3px;padding:10px;width:95%; margin:5px auto;font-family: Arial, Helvetica, sans-serif;\">".
                              "<tr><td><h2 style=\"color:#313131;text-transform: uppercase; text-align:center;\">Certificate from ".trim($certificate['sNombreCompania'])."</h2></td></tr>".
                              "<tr><td><p style=\"color:#5c5c5c;margin:5px auto; text-align:left;\">".utf8_decode($certificate['sDescription'])."</p></tr></td>".
                              "<tr><td></tr></td>".
                              "<tr><td><p style=\"color:#858585;margin:5px auto; text-align:left;font-size:10px;\">E-mail sent from Solo-trucking System.</p></tr></td>".
                              "<tr><td></tr></td>". 
                              "</table>".
                              "</body>";
               //footer              
               $htmlEmail .= "</html>";
               
               #TERMINA CUERPO DEL MENSAJE
                $mail = new Mail();                                    
                $mail->From = "customerservice@solo-trucking.com";
                $mail->FromName = "Solo-Trucking Insurance System";
                $mail->Host = "http://www.solotrucking.laredo2.net";
                $mail->Mailer = "sendmail";
                $mail->Subject = $subject; 
                $mail->Body  = $htmlEmail;
                $mail->ContentType ="Content-type: text/html; charset=iso-8859-1";
                $mail->IsHTML(true);
                $mail->WordWrap =150;
                $mail->AddAttachment($file);
                $mail_error = false;
                $mail->AddAddress(trim($certificate['email'])); 
                //termina cuerpo del correo.
                if(!$mail->Send()){$mail_error = true; $mail->ClearAddresses();}
                if(!$mail_error){$msj = "The e-mail was successfully sent.";}
                else{
                    $msj = "Error: The e-mail cannot be sent.";
                    $error = "1";            
                }
                $mail->ClearAttachments();
                unlink($file); 
           }
          
      }else{
          $error = '1';
          $msj = "Error: Certificate data was not found.";
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
