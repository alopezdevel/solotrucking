<script src="js/jquery.1.8.3.min.js" type="text/javascript"></script> 
<script src="/../../../code.jquery.com/ui/1.11.4/jquery-ui.js"></script>

<script type="text/javascript">       
$(document).ready(inicio);
function inicio(){
    $('#btn_getcertificate').click(onSendMessage);       
    
}                        
function onSendMessage(){    
    var fn_UsersClients = {
        domroot:"#fn_getcertificate",
           send_email: function(){
               $.post("system/funciones.php", { accion:"get_certificate", insuredname: $('.insuredname').val(), emailfax: $('.email-fax').val(),cholder: $('.cholder').val(),
                                                description: $('.description').val()},
               function(data){
                   alert('1');
                   $(fn_getcertificate +" form").hide('slow');
                   $(fn_getcertificate +" #msg-thanks").show('slow');
               },"json");
               
           }    
        }
        fn_UsersClients.send_email();
} 
  

	
</script> 
<!DOCTYPE html>
<html>

<head>
<meta content="text/html; charset=utf-8" http-equiv="Content-Type">
<title>SoloTrucking Insurance - Commercial Truck Insurance</title>
<link rel="icon" href="images/favicon.png" type="image/png">
<link rel="shortcut icon" href="favicon.ico" type="img/x-icon">
<link href="css/dialogs.css" rel="stylesheet" type="text/css">

</head>

<body>
	<div id="fn_getcertificate" class="dialog">
       <form method="POST" action="http://nlaredo.globalpc.net/cgi-bin/mailform" onsubmit="return FrontPage_Form1_Validator(this)">                	
			<input class="insuredname" name="insuredname" type="text" placeholder="Insured Name:">
			<input class="email-fax" name="text" type="email" placeholder="E-mail or Fax:">
			<textarea class="cholder" name="cholder" cols="20" rows="4" placeholder="Certificate Holder:"></textarea>
			<textarea class="description" name="description" cols="20" rows="4" placeholder="Description of Operations / Locations / Vehicles / Additional Remarks:" style="height:70px;"></textarea>
			<button id="btn_getcertificate" type="button" class="btn_2 right">SEND MESSAGE</button>
		</form>
	</div>
	<div id="msg-thanks">
		<p class="txt-center"><strong>Thank you for submitting your request.</strong></p>
		<p class="txt-center">Within a short time we will send the certificate you requested your email.</p>
    </div>

</body>

</html>
