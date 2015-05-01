<script src="js/jquery.1.8.3.min.js" type="text/javascript"></script> 
<script src="/../../../code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<style>
    .mensaje_valido { border: .5px solid transparent; padding: 0.1em; }
</style>
<script type="text/javascript">
       
$(document).ready(inicio);
function inicio(){
    //Variables     
        mensaje = $( ".mensaje_valido" );        
    //eventos
        //focus
        $("#insuredname").focus(onFocus);
        $("#email-fax").focus(onFocus);
        $("#cholder").focus(onFocus);
        $("#description").focus(onFocus);
        //blur
        $("#insuredname").blur(onBlur);
        $("#email-fax").blur(onBlur);
        $("#cholder").blur(onBlur);
        $("#description").blur(onBlur);
    $('#btn_getcertificate').click(onSendMessage);       
}                        
function onSendMessage(){ 
     var emailRegex = /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
     //Inputs
     var insuredname = $("#insuredname");
     var email = $("#email-fax");
     var cholder = $("#cholder");
     var description = $("#description");     
     todosloscampos = $( [] ).add( insuredname ).add( email ).add( cholder ).add( description );
     todosloscampos.removeClass( "error" ); 
     $("#name").focus().css("background-color","#FFFFC0");
     actualizarMensajeAlerta( "" );                                                                                              
    var fn_UsersClients = {
        domroot:"#fn_getcertificate",
           send_email: function(){
               $.post("system/funciones.php", { accion:"get_certificate", insuredname: $('.insuredname').val(), emailfax: $('.email-fax').val(),cholder: $('.cholder').val(),
                                                description: $('.description').val()},
               function(data){
                   if(data.error == "0"){
                       $(fn_getcertificate).hide('slow');
                       $("#msg-thanks").show('slow');   
                       $("#insuredname").val("");
                       $("#email-fax").val("");
                       $("#cholder").val("");
                       $("#description").val("");
                   }else{
                       alert(data.mensaje);
                       $("#insuredname").focus();
                   }
                   
               },"json");
               
           }    
        }        
        //validaciones
        var valid = true;
        //validaciones expre y tamano
        valid = valid && checkLength( insuredname, "Insured name", 5, 25 );
        valid = valid && checkRegexp( insuredname, /^[a-z]([0-9a-z_\s])+$/i, "Insured name of a-z, 0-9, underscores, spaces and must begin with a letter." );
    
    
        valid = valid && checkLength( email, "E-mail or Fax", 6, 80 );
        valid = valid && checkRegexp( email, emailRegex, "eg. ui@solotrucking.com" );
    
        valid = valid && checkLength( cholder, "Certificate Holder", 5, 100 );
        //valid = valid && checkRegexp( cholder, /^[a-z]([0-9a-z_\s])+$/i, "Certificate Holder of a-z, 0-9, underscores, spaces and must begin with a letter." );

        valid = valid && checkLength( description, "Description of Operations / Locations / Vehicles / Additional Remarks", 6, 25 );
        //valid = valid && checkRegexp( description, /^([0-9a-zA-Z])+$/, "Description of Operations / Locations / Vehicles / Additional Remarks of a-z, 0-9, underscores, spaces and must begin with a letter." );
        if(valid){
            //invocando evento del correo
            fn_UsersClients.send_email();
        }
        
} 
function onFocus(){
     $(this).css("background-color","#FFFFC0");
 }
 function onBlur(){
    $(this).css("background-color","#FFFFFF");
 }
 function actualizarMensajeAlerta( t ) {
      mensaje
        .text( t )
        .addClass( "alertmessage" );
      setTimeout(function() {
        mensaje.removeClass( "alertmessage", 2500 );
      }, 700 );
 }
 function checkRegexp( o, regexp, n ) {
    if ( !( regexp.test( o.val() ) ) ) {
        actualizarMensajeAlerta( n );
        o.addClass( "error" );
        o.focus();
        return false;
    } else {                     
        return true;        
    }
 }
 function checkLength( o, n, min, max ) {
    if ( o.val().length > max || o.val().length < min ) {
        actualizarMensajeAlerta( "Length of " + n + " must be between " + min + " and " + max + "."  );
        o.addClass( "error" );
        o.focus();
        return false;    
    } else {             
        return true;                     
    }                    
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
            <p class="mensaje_valido">&nbsp;All form fields are required.</p>
            <input id = "insuredname" class="insuredname" name="insuredname" type="text" placeholder="Insured Name:">
            <input id = "email-fax" class="email-fax" name="text" type="email" placeholder="E-mail or Fax:">
            <textarea id = "cholder" class="cholder" name="cholder" cols="20" rows="4" placeholder="Certificate Holder:"></textarea>
            <textarea id = "description" class="description" name="description" cols="20" rows="4" placeholder="Description of Operations / Locations / Vehicles / Additional Remarks:" style="height:70px;"></textarea>
            <button id="btn_getcertificate" type="button" class="btn_2 right">SEND MESSAGE</button>
        </form>
    </div>
    <div id="msg-thanks">
        <p class="txt-center"><strong>Thank you for submitting your request.</strong></p>
        <p class="txt-center">Within a short time we will send the certificate you requested your email.</p>
    </div>

</body>

</html>
