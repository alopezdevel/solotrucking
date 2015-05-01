<!---- HEADER ----->
<?php include("header.php"); ?> 
<script src="/js/jquery.1.8.3.min.js" type="text/javascript"></script> 
<script src="/../../../code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<script>
$(document).ready(inicio);
function inicio(){
    //variable 
    mensaje = $( ".mensaje_valido" );
    $("#btn_register").click(onInsertarUsuario);
    //focus
    $("#name").focus(onFocus);
    $("#email").focus(onFocus);
    $("#password").focus(onFocus);
    $("#recapturapassword").focus(onFocus);
    //blur
    $("#name").blur(onBlur);
    $("#email").blur(onBlur);
    $("#password").blur(onBlur);
    $("#recapturapassword").blur(onBlur);
}
function onInsertarUsuario(){
    //Variables
    var emailRegex = /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
    var name = $("#name");
    var email = $("#email");
    var password = $("#password");
    var re_password = $("#recapturapassword");
    todosloscampos = $( [] ).add( name ).add( email ).add( password ).add( re_password );
    todosloscampos.removeClass( "error" );
    
    
    $("#name").focus().css("background-color","#FFFFC0");
    actualizarMensajeAlerta( "" );     
    
    //validaciones
    var valid = true;
    
    //tamano
    valid = valid && checkLength( name, "Company name", 5, 25 );
    valid = valid && checkRegexp( name, /^[a-z]([0-9a-z_\s])+$/i, "Company name of a-z, 0-9, underscores, spaces and must begin with a letter." );
    
    valid = valid && checkLength( email, "E-mail", 6, 80 );
    valid = valid && checkRegexp( email, emailRegex, "eg. ui@solotrucking.com" );
    
    valid = valid && checkLength( password, "password", 6, 25 );
    valid = valid && checkRegexp( password, /^([0-9a-zA-Z])+$/, "Password field only allow : a-z 0-9" );
    
    valid = valid && checkLength( re_password, "password", 6, 25 );
    valid = valid && checkRegexp( re_password, /^([0-9a-zA-Z])+$/, "Password field only allow : a-z 0-9" );
    
    if(password.val() != re_password.val() && valid){
        actualizarMensajeAlerta( "thats not the same password as the first one" );
        re_password.addClass( "error" );
        re_password.focus();
        valid = false;
    }
    
    //exp
    
    
    
    
 
    
    if ( valid ) {
        $.post("funciones.php", { accion: "alta_usuario", name: name.val() , email: email.val(), password: password.val(), nivel: "C"},
        function(data){ 
             switch(data.error){
             case "1":   actualizarMensajeAlerta( data.mensaje);
                         $("#email").focus();
                         email.addClass( "error" ); 
                    break;
             case "0":   actualizarMensajeAlerta("All form fields are required.");
                         $("#name").val("");
                         $("#email").val("");
                         $("#password").val("");
                         $("#recapturapassword").val("");
                         $("#name").focus();
                    break;  
             }
         }
         ,"json"); 
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
<div id="layer_content" class="main-section">
	<div class="container">
		<div class="page-title">
            <h1>Users</h1>
            <h2>New User</h2>
        </div>
		<form method="post" action="">
            <p class="mensaje_valido">&nbsp;All form fields are required.</p>
			<input  id = "name"   name="name" type="text" placeholder="Company Name:">
			<input  id = "email"name="email" type="email" placeholder="E-mail:">
			<input  id = "password"name="password" type="password" placeholder="Password:">
			<input  id = "recapturapassword"name="recapturapassword" type="password" placeholder="Repeat the Password:">
			<button id = "btn_register" class="btn_register btn_4" type="button">Register</button>
		</form>
	</div>
</div>
<!---- FOOTER ----->
<?php include("footer.php"); ?> 

</body>

</html>
