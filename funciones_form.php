<?php
	
	$_POST["accion"] and  $_POST["accion"]!= "" ? call_user_func_array($_POST["accion"],array()) : ""; 
	
	function get_certificate(){
		
		$insuredname = trim($_POST["insuredname"]);
		$email = trim($_POST["email"]);
		$fax = trim($_POST["fax"]);
		$cholder = trim($_POST["cholder"]);
		$description = trim($_POST["description"]);
		
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
             $mail->AddAddress('celina@globalpc.net'));
             if (!$mail->Send()) {
                $mail_error = true;
                $mail->ClearAddresses();
             }


            
            if(!$mail_error){
                $mensaje = "El usuario $usuario se registro con exito";
                $error = "0";
            }else{
                $mensaje = "Error e-mail.";
                $error = "1";            
            }
            $response = array("mensaje"=>"$mensaje","error"=>"$error");   
     		echo array2json($response);            			
	}
	

?>